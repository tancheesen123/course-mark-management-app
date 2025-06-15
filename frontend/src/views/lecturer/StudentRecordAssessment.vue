<template>
  <div class="content">
    <div class="title-cover">
      <div class="header-content">
        <h1>Manage Student Records - Assessments</h1>
        <button class="back-btn" @click="goBack">Back</button>
      </div>
    </div>

    <p class="subtitle">Select one of the assessments to key in students' marks</p>

    <div v-if="errorMessage" class="subtitle" style="color: red">{{ errorMessage }}</div>

    <div v-else class="assessment-cards">
      <div
        v-for="assessment in assessments"
        :key="assessment.id"
        class="assessment-card"
        @click="goToAssessment(assessment)"
      >
        <img :src="getIcon(assessment.type)" class="type-icon" alt="icon" />
        <p>{{ assessment.name }}</p>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "StudentRecordDetails",
  data() {
    return {
      courseId: this.$route.query.course_id, // Access course_id from query params
      assessments: [],
      errorMessage: "",
    };
  },
  async mounted() {
    const token = localStorage.getItem("authToken");
    if (!token) {
      this.errorMessage = "User not authenticated";
      return;
    }

    try {
      const response = await fetch(`http://localhost:8085/api/assessments?course_id=${this.courseId}`, {
        method: 'GET',
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });
      const data = await response.json();

      if (response.ok && data.assessment_component) {
        this.assessments = data.assessment_component;
      } else {
        this.errorMessage = "Failed to load assessments or No assessments found";
      }
    } catch (error) {
      console.error("Error loading assessments: ", error);
      this.errorMessage = "Failed to load assessments";
    }
  },
  methods: {
    goToAssessment(assessment) {
     const assessmentName = encodeURIComponent(assessment.name);
      // Here you can route to a detailed page where the lecturer can enter marks for the selected assessment
      this.$router.push(`/lecturerMenu/student-record/assessment/mark?course_id=${this.courseId}&name=${assessmentName}`);
    },
    getIcon(type) {
      return require(`@/assets/icons/${type}-icon.png`);
    },
    goBack() {
      this.$router.go(-1); // Go back to the previous page
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

.assessment-cards {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
  padding: 40px;
}

.assessment-card {
  width: 299px;
  height: 154px;
  background: white;
  border: 1px solid #c4bcbc;
  border-radius: 10px;
  display: flex;
  gap: 20px;
  justify-content: center;
  align-items: center;
  font-size: 20px;
  text-align: center;
  cursor: pointer;
}

.assessment-card:hover {
  border: 2px solid #7C192E;
  color: #7C192E;
  font-weight: bold;
  transition: 0.3s ease;
}

.type-icon {
  width: 91px;
  height: 101px;
}

.back-btn {
  background-color: #FFBF48;
  padding: 15px 25px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  color: #731329;
  font-weight: bold;
  font-size: 20px;
  transition: background-color 0.3s ease, transform 0.2s ease;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.back-btn:hover {
  background-color: #F0A643;
  transform: translateY(-1px);
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%; /* Make sure content takes full width of title-cover padding */
    padding-right: 40px;
}
</style>
