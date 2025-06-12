<template>
    <div class="content">
      <div class="title-cover">
        <h1>Manage Student Records</h1>
      </div>

      <p class="subtitle">Select one of the courses to key in assessment marks</p>

      <div v-if="errorMessage" class="subtitle" style="color: red">{{ error }}</div>

      <div v-else class="course-cards">
        <div
          v-for="course in courses"
          :key="course.course_id"
          class="course-card"
          @click="goToCourse(course.course_id)"
        >
          <p>{{ course.course_code }} - {{ course.course_name }}</p>
        </div>
      </div>
    </div>
</template>

<script>
export default {
  name: 'ManageStudentRecords',
  data() {
    return {
      courses: [],
      errorMessage: "",
    };
  },
  async mounted() {
    const token = localStorage.getItem('authToken');
    if(!token) {
      this.errorMessage = "User not authenticated";
      return;
    }

    try {
      const response = await fetch("http://localhost:8085/api/getAllCourses", {
        method: 'GET',
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });
      const data = await response.json();
      console.log("Course response:", data);

      if(response.ok && data.courses) {
        this.courses = data.courses;
      } else {
        this.errorMessage = "Failed to load courses or No courses found";
      }
    } catch (error) {
      console.error("Error loading courses: ", error);
      this.errorMessage = "Failed to load courses";
    }
  },
  methods: {
    goToCourse(courseId) {
      this.$router.push(`/student-records/${courseId}`);
    },
  },
};
</script>

<style scoped>
.content {
  width: 100%;
}

.title-cover {
  background: #FFF9EB;
  width: 100%;
  height: 100px;
  display: flex;
  align-items: center;
  padding-left: 40px;
  box-sizing: border-box;
}

.title-cover h1 {
  font-size: 36px;
  color: #770f20;
  font-weight: bold;
  margin: 0;
}

.subtitle {
  margin-left: 40px;
  margin-top: 20px;
  font-size: 20px;
  color: #000;
}

.course-cards {
  display: flex;
  gap: 40px;
  padding: 40px;
}

.course-card {
  width: 299px;
  height: 154px;
  background: white;
  border: 1px solid #c4bcbc;
  border-radius: 10px;
  display: flex;
  justify-content: center;
  align-items: center;
  font-size: 20px;
  text-align: center;
  cursor: pointer;
}

.course-card:hover {
  border: 2px solid #7C192E;
  color: #7C192E;
  font-weight: bold;
  transition: 0.3s ease;
}
</style>
