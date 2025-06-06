<template>
  <div>
    <h2>Total Calculation</h2>
    <p>Welcome back, {{ userName }}!</p>
    <button @click="logoutUser">Logout</button>
  </div>
</template>

<script>
export default {
  name: "totalCalculation",
  data() {
    return {
      userName: ''
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
    logoutUser() {
      localStorage.removeItem("authToken");
      localStorage.removeItem("user");
      this.$router.push("/"); // Adjust route if different
    }
  }
};
</script>