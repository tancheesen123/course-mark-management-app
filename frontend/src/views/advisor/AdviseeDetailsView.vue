<template>
  <div class="content">
    <button class="back-btn" @click="goBack">Back</button>
    <div class="title-cover">
      <h1>Advisee Detail</h1>
    </div>

    <div v-if="errorMessage" class="error-msg">{{ errorMessage }}</div>

    <div v-else-if="student" class="detail-body">
      <h2>
        {{ student.name }}
        <span :class="getRiskClass(student.risk)">{{ student.risk }}</span>
      </h2>
      <p><strong>Matric Number:</strong> {{ student.matric_number }}</p>
      <p><strong>Email:</strong> {{ student.email }}</p>
      <p><strong>Course:</strong> {{ student.course_name }}</p>
      <p><strong>GPA:</strong> {{ student.gpa }}</p>

      <table class="mark-table">
        <thead>
          <tr>
            <th v-for="c in componentHeaders" :key="c">{{ c }}</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td v-for="c in componentHeaders.slice(0, -1)" :key="c">
              {{ getMark(c) }}
            </td>
            <td>{{ student.total_mark }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
export default {
  name: "StudentDetail",
  props: ["courseId", "studentId"],

  data() {
    return {
      student: null,
      errorMessage: "",
      componentHeaders: [
        "Quiz",
        "Lab",
        "Exercise",
        "Test",
        "Assignment",
        "Final",
        "Total",
      ],
    };
  },
  methods: {
    async fetchStudentDetails() {
      try {
        const res = await fetch(
          `http://localhost:8085/api/public/advisor/courses/${this.courseId}/students/${this.studentId}/details`
        );
        const data = await res.json();

        if (res.ok && data.success) {
          this.student = data.details;
        } else {
          this.errorMessage = data.message || "Failed to load student details.";
        }
      } catch (err) {
        console.error(err);
        this.errorMessage = "Error loading student details.";
      }
    },
    getMark(name) {
      const component = this.student?.components?.find((c) => c.name === name);
      return component ? component.mark : "-";
    },
    getRiskClass(risk) {
      return risk === "High" ? "high-risk" : "low-risk";
    },
    goBack() {
      if (window.history.length > 1) {
        this.$router.back();
      } else {
        this.$router.push("/advisorMenu/courses");
      }
    },
  },
  mounted() {
    this.fetchStudentDetails();
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
.detail-body {
  padding: 0 40px;
}

h2 {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 25px;
  color: #000;
}

.low-risk {
  background: green;
  color: white;
  padding: 8px 20px;
  border-radius: 50px;
}

.high-risk {
  background: red;
  color: white;
  padding: 8px 20px;
  border-radius: 50px;
}

.mark-table {
  margin-top: 50px;
  border-collapse: collapse;
  width: 100%;
  background: white;
  border-radius: 8px;
  overflow: hidden;
}

.mark-table th,
.mark-table td {
  border: 1px solid #ddd;
  padding: 12px;
  text-align: center;
}

.mark-table th {
  background-color: #f5efe9;
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