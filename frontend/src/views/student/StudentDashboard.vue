<template>
  <div>
    <h1>Student Dashboard</h1>

    <!-- Advisees Statistics -->
    <div class="statistics">
      <div class="stat-card">
        <h3>Total Advisees</h3>
        <p>{{ totalAdvisees }}</p>
      </div>
      <div class="stat-card">
        <h3>At-Risk Advisees</h3>
        <p>{{ atRiskAdvisees }}</p>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "StudentDashboard",
  data() {
    return {
      userName: '',
      totalAdvisees: 0,
      atRiskAdvisees: 0
    };
  },
  mounted() {
    const userString = localStorage.getItem("user");
    if (userString) {
      try {
        const user = JSON.parse(userString);
        this.userName = user.email || user.name || "User";
      } catch (error) {
        console.warn("Invalid user JSON:", userString);
      }
    }
  },
  methods: {
    fetchAdviseesData(advisorId) {
      if (!advisorId) {
        console.error("No advisor ID found!");
        return;
      }

      fetch(`http://localhost:8085/api/advisees/${advisorId}`)
        .then((response) => response.json())
        .then((data) => {
          console.log(data);
          this.totalAdvisees = data.total_advisees;
          this.atRiskAdvisees = data.at_risk_advisees;
        })
        .catch((error) => {
          console.error("Error fetching advisees:", error);
        });
    },
    logoutUser() {
      localStorage.removeItem("authToken");
      localStorage.removeItem("user");
      this.$router.push("/"); // Adjust route if different
    },
  },
};
</script>

<style scoped>

h1 {
  font-size: 38px;
  color: #770f20;
}

/* Advisees Statistics */
.statistics {
  display: flex;
  justify-content: space-between;
  margin-top: 40px;
}

.stat-card {
  background-color: #F5EFE9;
  border-radius: 20px;
  width: 48%;
  padding: 20px;
  text-align: center;
}

.stat-card h3 {
  font-size: 24px;
  color: #770F20;
}

.stat-card p {
  font-size: 36px;
  font-weight: bold;
  color: #770F20;
}
</style>
