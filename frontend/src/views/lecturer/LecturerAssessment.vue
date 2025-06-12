<template>
  <div class="add-assessment-page">
    <!-- Title Banner -->
    <div class="title-cover">
      <h1 class="title">Assessment</h1>
    </div>

    <!-- Add Button -->
    <div class="add-assessment-btn">
      <button @click="toggleForm">+ Add Assessment</button>
    </div>

    <!-- Add/Edit Form -->
    <div v-show="showForm" class="add-assessment-form">
      <div class="form-card">
        <div class="form-row">
          <div class="form-group">
            <label>Course</label>
            <select v-model="form.course_id">
              <option disabled value="">Select Course</option>
              <option v-for="course in courses" :key="course.id" :value="course.course_id">
                {{ course.course_code }} - {{ course.course_name }}
              </option>
            </select>
          </div>

          <div class="form-group">
            <label>Assessment Name</label>
            <input v-model="form.name" type="text" />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Assessment Type</label>
            <select v-model="form.type">
              <option disabled value="">Select Type</option>
              <option value="quiz">Quiz</option>
              <option value="assignment">Assignment</option>
              <option value="lab">Lab</option>
              <option value="exercise">Exercise</option>
              <option value="test">Test</option>
              <option value="final">Final</option>
            </select>
          </div>

          <div class="form-group">
            <label>Weight (%)</label>
            <input v-model.number="form.weight" type="number" />
          </div>
        </div>

        <div class="form-group right-align">
          <button class="save-btn" @click="saveAssessment">
            {{ form.id ? 'Update' : 'Save' }}
          </button>
        </div>
      </div>
    </div>

     <!-- Course and Assessment Widgets -->
    <div v-for="course in courses" :key="course.id" class="course-card">
      <h3>{{ course.course_code }} - {{ course.course_name }}</h3>

      <!-- Check if assessments exist for the course -->
      <div v-if="courseAssessments[course.course_id] && courseAssessments[course.course_id].length > 0" class="assessment-list">
        <div v-for="assessment in courseAssessments[course.course_id]" :key="assessment.id" class="assessment-card">
          <img :src="getIcon(assessment.type)" class="type-icon" alt="icon" />
          <p class="assessment-title">{{ assessment.name }}</p>
          <p class="weight-mark">{{ assessment.weight }}%</p>
        </div>
      </div>

      <!-- Show message if no assessments are found -->
      <div v-else>
        <p>No assessments available for this course.</p>
      </div>
    </div>
  </div>
</template>


<script>
export default {
  data() {
    return {
      showForm: false,
      courses: [],
      assessments: [],
      courseAssessments: {},
      form: {
        id: null,
        course_id: '',
        name: '',
        type: '',
        weight: null,
      },
    };
  },
  mounted() {
    fetch('http://localhost:8085/api/getAllCourses')
      .then(res => res.json())
      .then(data => {
        this.courses = data.courses || [];

      this.courses.forEach(course => {
        this.fetchAssessmentsForCourse(course.course_id);
      });
      });
  },
  methods: {
    toggleForm() {
      this.showForm = !this.showForm;
      if(!this.showForm){
        this.resetForm();
      }
    },
    resetForm() {
      this.form = { id: null, course_id: null, name: '', type: '', weight: null };
    },
    saveAssessment() {
      console.log(this.form); // debugging: check the form data before sending
      if (!this.form.course_id|| !this.form.name || !this.form.type || this.form.weight === null) {
        alert("Please fill in all fields");
        return;
      }
      const assessmentData = {
        course_id: parseInt(this.form.course_id),
        name: this.form.name,
        type: this.form.type,
        weight: this.form.weight,
      };

      console.log('sending data to backend:', assessmentData);

      // send the assessment data to the backend via API
      fetch('http://localhost:8085/api/assessments', {
        method: 'POST',
        headers: {
          'Content-Type' : 'application/json',
        },
        body: JSON.stringify(assessmentData),
      })
        .then((response) => {
         if (!response.ok) {
             throw new Error('Network response was not ok');
         }
         return response.json();
     })
        .then((data) => {
          console.log('API Response: ',data);
          if (data.message) {
            // Successfully added
            this.assessments.push({
              ...assessmentData,
              id: Date.now(),
            });
            this.fetchAssessmentsForCourse(this.form.course_id);
            alert('Successfully save the assessment');
            this.toggleForm();
          } else {
            console.error('Failed to save assessment, response without message:', data);
          }
        })
    },
   fetchAssessmentsForCourse(courseId) {
      let url = `http://localhost:8085/api/assessments?course_id=${courseId}`;
      
      fetch(url)
        .then((res) => res.json())
        .then((data) => {
          if (data.assessment_component && Array.isArray(data.assessment_component)) {
            // Update the courseAssessments object with fetched assessments
            this.courseAssessments[courseId] = data.assessment_component;
          } else {
            this.courseAssessments[courseId] = [];
          }
        })
        .catch((error) => {
          console.error('Error fetching assessments: ', error);
        });
    },
    filteredAssessments(courseId) {
      // Return the filtered assessments for a specific course
      return this.courseAssessments[courseId] || [];
    },
    getIcon(type) {
      return require(`@/assets/icons/${type}-icon.png`);
    },
  },
};
</script>

<style scoped>
.title-cover {
  background: #FFF9EB;
  width: 100%;
  height: 100px;
  display: flex;
  align-items: center;
  padding-left: 40px;
  box-sizing: border-box;
}
.title {
  font-size: 36px;
  font-weight: 700;
  color: #770F20;
  margin: 0;
}
.add-assessment-btn {
  margin-left: 20px;
  margin-top: 30px;
}
.add-assessment-btn button {
  background: #FFBF48;
  border-radius: 10px;
  font-size: 24px;
  color: #731329;
  padding: 16px 30px;
  border: none;
  cursor: pointer;
}
.add-assessment-form {
  margin-top: 10px;  /* Adjusted margin-top to position the form below */
  margin-left: 20px; /* Align with the other elements */
  width: 974px;
}
.form-card {
  width: 974px;
  min-height: 279px;
  background: #ffffff;
  border: 1px solid #AEADAD;
  border-radius: 10px;
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}
.form-row {
  display: flex;
  gap: 300px;
  flex-wrap: wrap;
  align-items: flex-end;
}
.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.form-group label {
  font-size: 20px;
  font-weight: 500;
}
.form-group input,
.form-group select {
  height: 52px;
  padding: 8px;
  font-size: 16px;
  border: 1px solid #000;
  border-radius: 5px;
  width: 280px;
}
.right-align {
  align-self: center;
}
.save-btn {
  height: 57px;
  width: 109px;
  background: #FFBF48;
  color: #731329;
  font-size: 24px;
  font-weight: 500;
  border-radius: 10px;
  border: none;
  cursor: pointer;
}
.assessment-card {
  background: #ffffff;
  border: 1px solid #c4bcbc;
  border-radius: 10px;
  display: flex;
  align-items: center;
  padding: 0 30px;
  gap: 20px;
  margin-top: 20px;
  width: 100%;
  max-width: 980px;
  box-sizing: border-box;
}
.type-icon {
  width: 91px;
  height: 101px;
}
.assessment-title {
  font-size: 20px;
  flex-grow: 1;
}
.weight-mark {
  font-size: 32px;
  font-weight: 700;
}

.course-card {
  padding-left: 40px;
  margin-top: 30px;
  display: flex;
  flex-direction: column;
  gap: 20px;
  width: 100%;
}

.assessment-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
</style>