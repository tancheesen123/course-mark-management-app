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

      <div class="chart-container" v-if="chartData">
        <h2>Class Average per Component</h2>
        <LineChart :chartData="chartData" />
      </div>
      <div class="comparison-container">
        <h2>Compare with Coursemates</h2>
        <p>
          <strong>{{ student.name }} </strong> ranks
          <strong>{{ studentRank }}</strong> out of
          <strong>{{ totalStudents }}</strong> in the course.
        </p>
      </div>

      <div v-if="rankingChartData" class="ranking-chart">
        <h2>Student Position in Class</h2>
        <BarChart2
          :data="rankingChartData"
          :options="{
            responsive: true,
            plugins: { legend: { display: false } },
          }"
        />
      </div>
    </div>
  </div>
</template>

<script>
import LineChart from "@/views/advisor/AveragePerComponentChart.vue";

import BarChart2 from "@/views/advisor/RankingChart.vue";

export default {
  name: "StudentDetail",
  components: {
    LineChart,
    BarChart2,
  },

  props: ["courseId", "studentId"],

  data() {
    return {
      averageMarks: [],
      rankingMarks: [],
      student: null,
      errorMessage: "",
      studentRank: null,
      totalStudents: null,
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

  computed: {
    chartData() {
      if (!this.student || !this.averageMarks.length) return null;

      const categories = [
        "Quiz",
        "Lab",
        "Exercise",
        "Test",
        "Assignment",
        "Final",
      ];

      const studentData = categories.map((type) => {
        const total = this.student.components
          .filter((c) => c.name?.toLowerCase().startsWith(type.toLowerCase()))
          .reduce((sum, c) => sum + (parseFloat(c.mark) || 0), 0);
        return Math.round(total * 100) / 100;
      });

      const averageData = categories.map((type) => {
        const match = this.averageMarks.find((avg) =>
          avg.name?.toLowerCase().startsWith(type.toLowerCase())
        );
        return match ? Math.round(match.average_mark * 100) / 100 : 0;
      });

      return {
        labels: categories,
        datasets: [
          {
            label: "Advisee's Marks",
            data: studentData,
            borderColor: "#7c192f",
            backgroundColor: "#7c192f",
          },
          {
            label: "Average Marks",
            data: averageData,
            borderColor: "#ccc",
            backgroundColor: "#ccc",
          },
        ],
      };
    },

    rankingChartData() {
      if (!this.rankingMarks.length) return null;

      return {
        labels: this.rankingMarks.map((r) => r.label),
        datasets: [
          {
            label: "Total Marks",
            backgroundColor: this.rankingMarks.map((r) =>
              r.isTarget ? "#7c192f" : "#ccc"
            ),
            data: this.rankingMarks.map((r) => r.value),
          },
        ],
      };
    },
  },

  methods: {
    async fetchRanking() {
      try {
        const res = await fetch(
          `http://localhost:8085/api/public/advisor/courses/${this.courseId}/ranking`
        );
        const data = await res.json();
        if (res.ok && data.success && Array.isArray(data.ranks)) {
          const sorted = data.ranks.sort((a, b) => b.total_mark - a.total_mark);
          this.totalStudents = sorted.length;
          const index = sorted.findIndex((s) => s.student_id == this.studentId);
          if (index !== -1) this.studentRank = index + 1;

          this.rankingMarks = sorted.map((s, i) => ({
            label: `#${i + 1}`,
            value: Math.round(s.total_mark * 100) / 100,
            isTarget: s.student_id == this.studentId,
          }));
        }
      } catch (err) {
        console.error("Error loading ranking", err);
      }
    },
    async fetchAverageMarks() {
      try {
        const res = await fetch(
          `http://localhost:8085/api/public/advisor/courses/${this.courseId}/average-marks`
        );
        const data = await res.json();
        if (res.ok && data.success) {
          this.averageMarks = data.averages;
        }
      } catch (err) {
        console.error("Error fetching averages", err);
      }
    },

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
      if (!this.student || !Array.isArray(this.student.components)) return "-";

      const filtered = this.student.components.filter((c) => {
        return c.name?.toLowerCase().startsWith(name.toLowerCase());
      });

      if (!filtered.length) return "-";

      const total = filtered.reduce((sum, comp) => {
        const mark = parseFloat(comp.mark);
        return sum + (isNaN(mark) ? 0 : mark);
      }, 0);

      return Math.round(total * 100) / 100;
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
    this.fetchAverageMarks();
    this.fetchRanking();
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
  gap: 20px;
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
  margin: 30px auto 50px auto;
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
.chart-container {
  margin-top: 40px;
  width: 85%;
}
.chart-container h2 {
  color: #7c192f;
}

.comparison-container {
  margin-top: 60px;
  background: #f5efe9;
  padding: 20px 30px;
  border-radius: 10px;
  width: 85%;
}

.comparison-container h2 {
  color: #7c192f;
  margin-bottom: 10px;
}

.ranking-chart {
  margin-top: 0px;
  padding: 20px;
  border-radius: 10px;
  width: 85%;
}
.ranking-chart h2 {
  color: #7c192f;
  margin-bottom: 15px;
}
</style>
