<template>
  <div class="content">
    <div class="title-cover">
      <h1>Advisor Dashboard</h1>
    </div>

    <div class="course-stats-section">
      <div class="course-cards">
        <div
          class="stat-card course"
          v-for="course in courseWiseStats"
          :key="course.course_id"
        >
          <h3>{{ course.course_name }}</h3>
          <div class="course-numbers">
            <div class="course-metric">
              <span>Total</span>
              <strong>{{ course.total_advisees }}</strong>
            </div>
            <div class="course-metric risk">
              <span>At-Risk</span>
              <strong>{{ course.at_risk_advisees }}</strong>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="chart-section">
      <h2>Performance Analytics</h2>
      <BarChart v-if="chartData" :data="chartData" />
    </div>
  </div>
</template>

<script>
import BarChart from "@/views/advisor/BarChart.vue";

export default {
  name: "AcademicAdvisorDashboard",
  components: {
    BarChart,
  },
  data() {
    return {
      userName: "",
      totalAdvisees: 0,
      atRiskAdvisees: 0,
      chartData: null,
      courseWiseStats: [],
    };
  },
  mounted() {
    const userString = localStorage.getItem("user");
    if (userString) {
      try {
        const user = JSON.parse(userString);
        this.userName = user.email || user.name || "User";
        this.fetchAdviseesData(user.advisor_id);
        this.fetchPerformanceStats(user.advisor_id);
        this.fetchCourseWiseStats(user.advisor_id);
      } catch (error) {
        console.warn("Invalid user JSON:", userString);
      }
    }
  },
  methods: {
    fetchAdviseesData(advisorId) {
      fetch(`http://localhost:8085/api/advisees/${advisorId}`)
        .then((res) => res.json())
        .then((data) => {
          this.totalAdvisees = data.total_advisees;
          this.atRiskAdvisees = data.at_risk_advisees;
        })
        .catch((err) => console.error("Error fetching advisee stats", err));
    },
    fetchPerformanceStats(advisorId) {
      fetch(`http://localhost:8085/api/advisee-performance/${advisorId}`)
        .then((res) => res.json())
        .then((data) => {
          const labels = [];
          const atRisk = [];
          const notAtRisk = [];

          data.forEach((course) => {
            labels.push(course.course_name);
            atRisk.push(course.at_risk);
            notAtRisk.push(course.total - course.at_risk);
          });

          this.chartData = {
            labels,
            datasets: [
              {
                label: "Non At-Risk Advisees",
                backgroundColor: "#ccc",
                data: notAtRisk,
              },
              {
                label: "At-Risk Advisees",
                backgroundColor: "#7c192f",
                data: atRisk,
              },
            ],
          };
        })
        .catch((err) => console.error("Error fetching chart data", err));
    },
    fetchCourseWiseStats(advisorId) {
      fetch(`http://localhost:8085/api/advisor/course-wise-stats/${advisorId}`)
        .then((res) => res.json())
        .then((data) => {
          this.courseWiseStats = data;
        })
        .catch((err) => console.error("Error fetching course-wise stats", err));
    },
    logoutUser() {
      localStorage.removeItem("authToken");
      localStorage.removeItem("user");
      this.$router.push("/");
    },
  },
};
</script>

<style scoped>
.content {
  width: 100%;
  font-family: "Segoe UI", sans-serif;
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

/* Top-level stats */
.stats-section {
  display: flex;
  flex-wrap: wrap;
  gap: 40px;
  margin-top: 40px;
  padding-left: 40px;
  padding-right: 40px;
}

.stat-card {
  background-color: #f5efe9;
  border-radius: 20px;
  padding: 30px;
  width: 300px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
  text-align: center;
}
.stat-card.highlight {
  width: 45%;
}
.stat-card h3 {
  font-size: 22px;
  color: #770f20;
  margin-bottom: 10px;
}
.stat-card p {
  font-size: 38px;
  font-weight: bold;
  color: #770f20;
  margin: 0;
}

/* Course-wise section */
.course-stats-section {
  margin-top: 40px;
  padding: 0 40px;
}
.course-stats-section h2 {
  margin-bottom: 20px;
  font-size: 24px;
  color: #770f20;
}
.course-cards {
  display: flex;
  flex-wrap: wrap;
  gap: 100px;
}
.stat-card.course {
  width: 500px;
  padding: 20px;
}
.course-numbers {
  display: flex;
  justify-content: space-between;
  margin-top: 15px;
}
.course-metric {
  text-align: center;
}
.course-metric span {
  font-size: 25px;
  color: #770f20;
}
.course-metric strong {
  font-size: 24px;
  color: #333;
  margin-left: 10px;
}
.course-metric.risk strong {
  color: #b60026;
  margin-left: 10px;
}

/* Chart Section */
.chart-section {
  width: 90%;
  margin-top: 50px;
  padding: 30px 40px;
  margin-left: 35px;
  background-color: #f5efe9;
  border-radius: 16px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
}
.chart-section h2 {
  font-size: 24px;
  margin-bottom: 20px;
  color: #770f20;
}
</style>
