<template>
  <div class="content">
    <button class="back-btn" @click="goBack">Back</button>
    <div class="title-cover">
      <h1>Advisee List</h1>
    </div>

    <div class="search-filter">
      <input type="text" v-model="searchQuery" placeholder="Search Advisees" />
      <select v-model="selectedSemester">
        <option v-for="s in semesters" :key="s" :value="s">{{ s }}</option>
      </select>
    </div>

    <div v-if="errorMessage" class="error-msg">{{ errorMessage }}</div>

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
export default {
  name: "AdviseeList",
  props: ["courseId"],
  data() {
    return {
      advisees: [],
      searchQuery: "",
      errorMessage: "",
      semesters: ["20242025/1", "20232024/2", "20222023/2"],
      selectedSemester: "20242025/1",
    };
  },
  computed: {
    filteredAdvisees() {
      return this.advisees.filter((a) => {
        const q = this.searchQuery.toLowerCase();
        return (
          a.name?.toLowerCase().includes(q) ||
          a.matric_number?.toLowerCase().includes(q) ||
          a.gpa?.toString().includes(q) ||
          a.risk?.toLowerCase().includes(q)
        );
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
  margin: 20px 0;
  padding-left: 40px;
}

input,
select {
  padding: 10px;
  border-radius: 15px;
  border: 1px solid #ccc;
}

.advisee-table {
  width: 100%;
  margin-top: 20px;
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
  background-color: #7c192f;
  color: white;
  border: none;
  padding: 8px 25px;
  border-radius: 15px;
  cursor: pointer;
  font-size: 16px;
}
.back-btn:hover {
  background-color: #5e1223;
}
</style>