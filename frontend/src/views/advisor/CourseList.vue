<template>
  <div class="content">
    <div class="title-cover">
      <h1>Course List</h1>
    </div>

    <p class="subtitle">
      Select one of your advised courses to view student details
    </p>

    <div v-if="errorMessage" class="subtitle" style="color: red">
      {{ errorMessage }}
    </div>

    <div v-else class="course-cards">
      <div
        v-for="course in courses"
        :key="course.course_id"
        class="course-card"
        @click="goToAdvisedStudents(course.course_id)"
      >
        <p>{{ course.course_code }} - {{ course.course_name }}</p>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "AdvisorCourseList",
  data() {
    return {
      courses: [],
      errorMessage: "",
    };
  },
  async mounted() {
    const token = localStorage.getItem("authToken");

    if (!token) {
      this.errorMessage =
        "User not authenticated. Please log in as an advisor.";
      return;
    }

    function parseJwt(token) {
      try {
        const base64Url = token.split(".")[1];
        const base64 = decodeURIComponent(
          atob(base64Url)
            .split("")
            .map(function (c) {
              return "%" + ("00" + c.charCodeAt(0).toString(16)).slice(-2);
            })
            .join("")
        );
        return JSON.parse(base64);
      } catch (e) {
        console.error("Failed to parse token:", e);
        return null;
      }
    }

    const decoded = parseJwt(token);
    const advisorUserId = decoded?.sub;

    if (!advisorUserId) {
      this.errorMessage = "Invalid token. Cannot extract advisor ID.";
      return;
    }

    try {
      const response = await fetch(
        `http://localhost:8085/api/advisor/courses?advisor_user_id=${advisorUserId}`,
        {
          method: "GET",
          headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${token}`,
          },
        }
      );

      const data = await response.json();
      console.log("Advisor Course response:", data);

      if (response.ok && data.courses) {
        this.courses = data.courses;
        if (this.courses.length === 0) {
          this.errorMessage = "No courses found for this advisor.";
        }
      } else {
        this.errorMessage = data.message || "Failed to load advised courses.";
      }
    } catch (error) {
      console.error("Error loading advised courses: ", error);
      this.errorMessage = "Failed to connect to the server or load courses.";
    }
  },
  methods: {
    goToAdvisedStudents(courseId) {
      this.$router.push({
        name: "AdviseeList",
        params: { courseId },
      });
    },
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
.course-cards {
  display: flex;
  flex-wrap: wrap;
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
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}
.course-card:hover {
  border: 2px solid #7c192f;
  color: #7c192f;
  font-weight: bold;
  transform: translateY(-5px);
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}
</style>
