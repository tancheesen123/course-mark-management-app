<template>
  <div class="dashboard-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
      <img src="@/assets/Logo-UTM-white.png" alt="UTM Logo" class="logo" />
      <nav class="nav-links">
        <router-link to="/lecturerMenu/dashboard" exact-active-class="active-link">Dashboard</router-link>
        <router-link to="/lecturerMenu/students" exact-active-class="active-link">Student Records</router-link>
        <router-link to="/lecturerMenu/assessment" exact-active-class="active-link">Assessment</router-link>
        <router-link to="/lecturerMenu/final-exam" exact-active-class="active-link">Final Exam</router-link>
        <router-link to="/lecturerMenu/total-calculation" exact-active-class="active-link">Total Calculation</router-link>
      </nav>
      <button class="logout-btn" @click="logoutUser">Log Out</button>
    </aside>

    <!-- Main Content Area -->
    <main class="content-area">
      <router-view />
    </main>
  </div>
</template>

<script>
export default {
  name: 'LecturerDashboard',
  methods: {
    logoutUser() {
      localStorage.removeItem('authToken');
      localStorage.removeItem('user');
      this.$router.push('/');
    }
  }
};
</script>

<style scoped>
.dashboard-layout {
  display: flex;
  height: 100vh;
  font-family: 'Segoe UI', sans-serif;
}

/* Sidebar */
.sidebar {
  width: 220px;
  background-color: #7d1629;
  color: white;
  padding: 20px;
  display: flex;
  flex-direction: column;
  justify-content: flex-start; /* Make content stick to the top */
  gap: 12px; /* Add spacing between links */
}

.logo {
  font-size: 24px;
  font-weight: bold;
  margin-bottom: 40px;
}

.nav-links {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.nav-links a {
  color: white;
  text-decoration: none;
  padding: 10px;
  border-radius: 4px;
}

.nav-links a.active-link {
  background-color: #f4c04e;
  color: #333;
}

.logout-btn {
  margin-top: auto;
  background-color: #e74c3c;
  color: white;
  border: none;
  padding: 10px 15px;
  border-radius: 5px;
  cursor: pointer;
}

.logout-btn:hover {
  background-color: #d9363e;
}

/* Main Content */
.content-area {
  flex: 1;
  padding: 30px;
  background-color: #fff8f1;
  overflow-y: auto;
}
</style>
