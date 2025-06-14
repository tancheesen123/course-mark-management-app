<template>
  <div class="advisee-list-page">
    <!-- Main Content -->
    <main class="content-area">
      <div class="title-cover">
        <h1>Advisee List</h1>
        <div class="search-filter">
          <div class="search-bar">
            <input type="text" v-model="searchQuery" placeholder="Search Advisees" />
          </div>
          <div class="semester-filter">
            <select v-model="selectedSemester">
              <option v-for="semester in semesters" :key="semester" :value="semester">{{ semester }}</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Advisee Table -->
      <table class="advisee-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Matric Number</th>
            <th>GPA</th>
            <th>Risk Indicator</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="advisee in filteredAdvisees" :key="advisee.matric_number">
            <td>{{ advisee.student_name }}</td>
            <td>{{ advisee.matric_number }}</td>
            <td>{{ advisee.gpa }}</td>
            <td :class="getRiskClass(advisee.risk)">{{ advisee.risk }}</td>
            <td><button class="view-btn" @click="viewAdvisee(advisee)">View</button></td>
          </tr>
        </tbody>
      </table>
    </main>
  </div>
</template>

<script>
export default {
  name: 'AdviseeList',
  data() {
    return {
      searchQuery: '',
      selectedSemester: '20242025/1',
      semesters: ['20242025/1', '20232024/2', '20222023/1', '20222023/2'],
      advisees: [],  
    };
  },
  computed: {
    filteredAdvisees() {
      if (Array.isArray(this.advisees)) {
        return this.advisees
          .filter(advisee => {
            const query = this.searchQuery.toLowerCase();
            return (
              advisee.student_name.toLowerCase().includes(query) ||
              advisee.matric_number.toLowerCase().includes(query) ||
              advisee.gpa.toString().includes(query) ||
              advisee.semester.toLowerCase().includes(query) ||
              advisee.risk.toLowerCase().includes(query)
            );
          })
          .filter(advisee => advisee.semester === this.selectedSemester);
      }
      return [];
    }
  },
  methods: {
    // Fetch the advisees based on advisor's ID from localStorage
    fetchAdvisees() {
      const advisorId = this.getAdvisorId();
      if (!advisorId) {
        console.error('No advisor ID found!');
        return;
      }

      fetch(`http://localhost:8085/api/advisees/${advisorId}`)
        .then(response => response.json())
        .then(data => {
          console.log(data);
          if (Array.isArray(data.students)) {
            this.advisees = data.students;
          } else {
            console.error("Invalid data format:", data);
          }
        })
        .catch(error => {
          console.error("There was an error fetching advisees!", error);
        });
    },

    getAdvisorId() {
      const userString = localStorage.getItem("user");
      if (userString) {
        try {
          const user = JSON.parse(userString);
          if (user.role === 3) { 
            return user.id; 
          } else {
            console.warn("Current user is not an advisor.");
          }
        } catch (error) {
          console.warn("Invalid user JSON:", userString);
        }
      }
      return null;
    },

    getRiskClass(risk) {
      if (risk === 'LOW') return 'low-risk';
      if (risk === 'MEDIUM') return 'medium-risk';
      return 'high-risk';
    },

    viewAdvisee(advisee) {
      this.$router.push(`/advisorMenu/advisees/${advisee.matric_number}`);
    }
  },
  mounted() {
    this.fetchAdvisees(); 
  }
};
</script>

<style scoped>
.advisee-list-page {
  display: flex;
  height: 100vh;
  justify-content: center;
}

.content-area {
  flex: 1;
  padding: 30px;
  background-color: #fff8f1;
  width: 100%;
}

.title-cover h1 {
  font-size: 48px;
  color: #770f20;
}

.search-filter {
  display: flex;
  gap: 20px;
  margin-top: 20px;
}

.search-bar input {
  width: 300px;
  padding: 10px;
  border-radius: 15px;
  border: 1px solid #ccc;
}

.semester-filter select {
  padding: 10px;
  border-radius: 15px;
  border: 1px solid #ccc;
}

.advisee-table {
  width: 100%;
  margin-top: 30px;
  border-collapse: collapse;
}

.advisee-table th,
.advisee-table td {
  padding: 10px;
  text-align: center;
  border: 1px solid #ddd;
}

.advisee-table th {
  background-color: #f5efe9;
}

.low-risk {
  color: green;
}

.medium-risk {
  color: orange;
}

.high-risk {
  color: red;
}

.view-btn {
  padding: 10px 20px;
  background-color: #7c192f;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}

.view-btn:hover {
  background-color: #ffbf48;
}
</style>
