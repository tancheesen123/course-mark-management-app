<template>
  <div class="content">
    <button class="back-btn" @click="goBack">Back</button>
    <div class="title-cover">
      <h1>Advisee List</h1>
    </div>

    <div class="top-controls">
      <div class="search-filter">
        <input
          type="text"
          v-model="searchQuery"
          placeholder="Search Advisees"
        />
        <select v-model="selectedSemester">
          <option value="">All Semesters</option>
          <option v-for="s in semesters" :key="s" :value="s">{{ s }}</option>
        </select>
      </div>
      <div class="export-section">
        <button class="export-btn" @click="exportToExcel" :disabled="exporting">
          {{ exporting ? "Exporting..." : "Export CSV" }}
        </button>
        <div class="success-wrapper" v-if="exportSuccess">
          ✔ Export successful!
        </div>
      </div>
    </div>

    <table class="advisee-table" v-if="filteredAdvisees.length">
      <thead>
        <tr>
          <th>Name</th>
          <th>Matric Number</th>
          <th>Total Mark</th>
          <th>GPA</th>
          <th>Risk</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="advisee in filteredAdvisees" :key="advisee.student_id">
          <td>{{ advisee.name }}</td>
          <td>{{ advisee.matric_number }}</td>
          <td>{{ advisee.total_mark }}</td>
          <td>{{ advisee.gpa }}</td>
          <td :class="getRiskClass(advisee.risk)">{{ advisee.risk }}</td>
          <td>
            <button class="view-btn" @click="viewAdvisee(advisee.student_id)">
              View
            </button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
import * as XLSX from "xlsx";
import { saveAs } from "file-saver";
export default {
  name: "AdviseeList",
  props: ["courseId"],
  data() {
    return {
      advisees: [],
      searchQuery: "",
      errorMessage: "",
      semesters: [],
      selectedSemester: "",
      exporting: false,
      exportSuccess: false,
    };
  },

  computed: {
    filteredAdvisees() {
      return this.advisees.filter((a) => {
        const q = this.searchQuery.toLowerCase();
        const matchSearch =
          a.name?.toLowerCase().includes(q) ||
          a.matric_number?.toLowerCase().includes(q) ||
          a.gpa?.toString().includes(q) ||
          a.risk?.toLowerCase().includes(q);

        const courseSem = `${a.year}${a.year + 1}/${a.semester}`;
        const matchSemester =
          !this.selectedSemester || this.selectedSemester === courseSem;

        return matchSearch && matchSemester;
      });
    },
  },
  methods: {
    async fetchAdvisees() {
      const token = localStorage.getItem("authToken");

      let advisorId = null;
      try {
        const payloadBase64 = token?.split(".")[1];
        if (payloadBase64) {
          const decoded = JSON.parse(atob(payloadBase64));
          advisorId = decoded.sub;
        }
      } catch (err) {
        console.error("Error decoding token", err);
      }

      const courseId =
        this.$route.params.courseId || this.$route.query.course_id;

      if (!advisorId || !courseId) {
        this.errorMessage = "Missing authentication info.";
        return;
      }

      try {
        const response = await fetch(
          `http://localhost:8085/api/public/advisor/courses/${courseId}/students?advisor_user_id=${advisorId}`
        );
        const data = await response.json();

        if (response.ok && data.success && Array.isArray(data.advisees)) {
          this.advisees = data.advisees;

          // 生成 semester 选项
          const uniqueSemesters = new Set(
            data.advisees.map((a) => `${a.year}${a.year + 1}/${a.semester}`)
          );
          this.semesters = Array.from(uniqueSemesters).sort().reverse();
        } else {
          this.errorMessage = data.message || "Failed to load advisees.";
        }
      } catch (error) {
        console.error("Error fetching advisees:", error);
        this.errorMessage = "Server error loading advisees.";
      }
    },

    getRiskClass(risk) {
      return risk === "High" ? "high-risk" : "low-risk";
    },
    viewAdvisee(studentId) {
      const courseId =
        this.courseId ||
        this.$route.params.courseId ||
        this.$route.query.course_id;

      if (!courseId || !studentId) {
        console.error("Missing courseId or studentId", courseId, studentId);
        return;
      }

      this.$router.push({
        name: "AdviseeDetailsView",
        params: { courseId, studentId },
      });
    },

    goBack() {
      if (window.history.length > 1) {
        this.$router.back();
      } else {
        this.$router.push("/advisorMenu/dashboard");
      }
    },

    async exportToExcel() {
      this.exporting = true;
      this.exportSuccess = false;

      const courseId =
        this.courseId ||
        this.$route.params.courseId ||
        this.$route.query.course_id;
      const exportData = [];
      let courseName = "";

      for (const advisee of this.advisees) {
        try {
          const res = await fetch(
            `http://localhost:8085/api/public/advisor/courses/${courseId}/students/${advisee.student_id}/details`
          );
          const detail = await res.json();

          if (res.ok && detail.success) {
            const d = detail.details;

            if (!courseName && d.course_name) {
              courseName = d.course_name.replace(/\s+/g, "_");
            }

            const row = {
              Name: d.name,
              "Matric Number": d.matric_number,
              Email: d.email,
              "Course Name": d.course_name,
              GPA: d.gpa,
              Risk: d.risk,
            };

            d.components.forEach((comp) => {
              const mark = parseFloat(comp.mark);
              if (!isNaN(mark)) {
                row[comp.name] = mark;
              }
            });

            row["Total"] = Math.round(d.total_mark * 100) / 100;

            row["Total"] = Math.round(d.total_mark * 100) / 100;

            exportData.push(row);
          }
        } catch (err) {
          console.error(`Error loading detail for ${advisee.name}`, err);
        }
      }

      if (!exportData.length) {
        this.exporting = false;
        return;
      }

      const worksheet = XLSX.utils.json_to_sheet(exportData);
      const workbook = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(workbook, worksheet, "Advisee Details");

      const excelBuffer = XLSX.write(workbook, {
        bookType: "xlsx",
        type: "array",
      });
      const blob = new Blob([excelBuffer], {
        type: "application/octet-stream",
      });

      const fileName = `Advisee_Details_${courseName || courseId}.xlsx`;
      saveAs(blob, fileName);

      this.exporting = false;
      this.exportSuccess = true;
      setTimeout(() => (this.exportSuccess = false), 3000);
    },
  },
  mounted() {
    this.fetchAdvisees();
  },
};
</script>

<style scoped>
.content {
  width: 100%;
}
.title-cover {
  background: #fff9eb;
  width: 100%;
  height: 100px;
  display: flex;
  align-items: center;
  padding-left: 40px;
  box-sizing: border-box;
}
.title-cover h1 {
  font-size: 36px;
  color: #7c192f;
  font-weight: bold;
  margin: 0;
}
.subtitle {
  margin-left: 40px;
  margin-top: 20px;
  font-size: 20px;
  color: #000;
}

.search-filter {
  display: flex;
  gap: 20px;
  margin: 0;
}

input,
select {
  padding: 10px;
  border-radius: 15px;
  border: 1px solid #ccc;
  width: 500px;
}

.advisee-table {
  width: 95%;
  margin: 20px auto 0 auto;
  border-collapse: collapse;
  background: white;
  border-radius: 10px;
  overflow: hidden;
}

th,
td {
  padding: 12px;
  text-align: center;
  border: 1px solid #ddd;
}

th {
  background-color: #f5efe9;
}

.low-risk {
  color: green;
  font-weight: bold;
}

.high-risk {
  color: red;
  font-weight: bold;
}

.view-btn {
  padding: 6px 14px;
  background-color: #7c192f;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}

.view-btn:hover {
  background-color: #5e1223;
}

.error-msg {
  color: red;
  margin-top: 20px;
  padding-left: 40px;
}

.back-btn {
  margin: 0 0 0 40px;
  background-color: #f4c04e;
  color: #7c192f;
  border: none;
  padding: 8px 25px;
  border-radius: 15px;
  cursor: pointer;
  font-size: 16px;
  font-weight: bold;
}
.back-btn:hover {
  background-color: #dcaa3f;
}

.top-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 40px;
  margin: 20px 0;
}

.export-section {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  margin: 10px 40px 0 auto;
}

.export-btn {
  background-color: #f4c04e;
  color: #7c192f;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 16px;
  font-weight: bold;
}

.export-btn:hover {
  background-color: #dcaa3f;
}

.success-wrapper {
  margin-top: 6px;
  color: green;
  font-weight: bold;
  font-size: 14px;
}

.search-filter select {
  width: 200px;
}
</style>