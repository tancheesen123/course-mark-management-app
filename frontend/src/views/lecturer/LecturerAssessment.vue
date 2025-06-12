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
          <button class="save-btn" @click="form.id ? updateAssessment() : saveAssessment()">
            {{ form.id ? 'Update' : 'Save' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Course and Assessment Widgets -->
    <div v-for="course in courses" :key="course.id" class="course-card">
      <h3>{{ course.course_code }} - {{ course.course_name }}</h3>

      <!-- Check if assessments exist for the course -->
      <div v-if="courseAssessments[course.course_id] && courseAssessments[course.course_id].length > 0"
        class="assessment-list">
        <div v-for="assessment in courseAssessments[course.course_id]" :key="assessment.id" class="assessment-card"
          @click="editAssessment(assessment)">
          <div class="delete-icon" @click="confirmDelete(assessment.id, $event)"></div>
          <img :src="getIcon(assessment.type)" class="type-icon" alt="icon" />
          <p class="assessment-title">{{ assessment.name }}</p>
          <p class="weight-mark">{{ assessment.weight }}%</p>
        </div>
      </div>
      <!-- Show message if no assessments are found -->
      <div v-else>
        <p>No assessments available for this course.</p>
      </div>

      <!-- Confirmation Popup (Initially hidden) -->
      <div v-if="showConfirmation" class="confirmation-overlay">
        <div class="confirmation-popup">
          <h2>Are you sure you want to delete the record of {{ selectedAssessment ? selectedAssessment.name : 'the selected assessment' }}?</h2>
          <div class="buttons">
            <button class="cancel" @click="cancelDelete">Cancel</button>
            <button class="delete" @click="deleteAssessment">Delete</button>
          </div>
        </div>
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
      showConfirmation: false,
      selectedAssessment: null,
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
      if (!this.showForm) {
        this.resetForm();
      }
    },
    resetForm() {
      this.form = { id: null, course_id: null, name: '', type: '', weight: null };
    },
    saveAssessment() {
      console.log(this.form); // debugging: check the form data before sending
      if (!this.form.course_id || !this.form.name || !this.form.type || this.form.weight === null) {
        alert("Please fill in all fields");
        return;
      }
      const assessmentData = {
        course_id: parseInt(this.form.course_id),
        name: this.form.name,
        type: this.form.type,
        weight: this.form.weight,
      };

      // send the assessment data to the backend via API
      fetch('http://localhost:8085/api/assessments', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
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
          console.log('API Response: ', data);
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
    updateAssessment() {
      if (!this.form.course_id || !this.form.name || !this.form.type || this.form.weight === null) {
        alert("Please fill in all fields");
        return;
      }

      const assessmentData = {
        course_id: parseInt(this.form.course_id),
        name: this.form.name,
        type: this.form.type,
        weight: this.form.weight,
      };

      fetch(`http://localhost:8085/api/assessments/${this.form.id}`, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(assessmentData),
      })
        .then((response) => {
          if (!response.ok) throw new Error('Network response was not ok');
          return response.json();
        })
        .then((data) => {
          if (data.message) {
            this.fetchAssessmentsForCourse(this.form.course_id);
            alert('Successfully updated the assessment');
            this.toggleForm();
          } else {
            console.error("Failed to update assessment");
          }
        })
    },
    fetchAssessmentsForCourse(courseId) {
      let url = `http://localhost:8085/api/assessments?course_id=${courseId}`;

      fetch(url)
        .then((res) => res.json())
        .then((data) => {
          console.log('Fetched assessments for course:', courseId, data);
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
    editAssessment(assessment) {
      this.form = {
        id: assessment.id,
        course_id: assessment.course_id,
        name: assessment.name,
        type: assessment.type,
        weight: assessment.weight,
      };
      this.showForm = true;
      this.selectedAssessmentId = assessment.id; // Track the assessment being edited
    },
    confirmDelete(assessmentId, event) {
      event.stopPropagation();  // Prevent event propagation to avoid other handlers

      console.log('Delete clicked', assessmentId);

      // Find the course id of the assessment based on the course
      let assessmentToDelete = null;
      for (let courseId in this.courseAssessments) {
        assessmentToDelete = this.courseAssessments[courseId].find(a => a.id === assessmentId);
        if (assessmentToDelete) {
          break; // Exit loop once assessment is found
        }
      }

      // If the assessment is found, show confirmation
      if (assessmentToDelete) {
        this.selectedAssessment = assessmentToDelete;
        this.showConfirmation = true;
      } else {
        console.error('No assessment found to delete');
      }
    },
    cancelDelete() {
      this.showConfirmation = false;
      this.selectedAssessment = null;
    },
    deleteAssessment() {
      // Check if selectedAssessment is valid before proceeding
      if (this.selectedAssessment) {
        fetch(`http://localhost:8085/api/assessments/${this.selectedAssessment.id}`, {
          method: 'DELETE',
          headers: { 'Content-Type': 'application/json' },
        })
          .then(response => response.json())
          .then(data => {
            if (data.message) {
              // Remove the assessment from the list after successful deletion
              this.assessments = this.assessments.filter(a => a.id !== this.selectedAssessment.id);
               this.fetchAssessmentsForCourse(this.selectedAssessment.course_id);
              this.showConfirmation = false; // Hide the confirmation modal
              this.selectedAssessment = null; // Reset the selected assessment
              alert('Assessment deleted successfully');
            }
          })
          .catch(error => {
            console.error('Error deleting assessment:', error);
          });
      }
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
  padding-left: 20px;
}

.add-assessment-btn button {
  background: #FFBF48;
  border-radius: 10px;
  font-size: 24px;
  color: #731329;
  padding: 16px 30px;
  border: none;
  cursor: pointer;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.add-assessment-btn button:hover {
  transform: translateY(-5px);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}


.add-assessment-form {
  margin-top: 10px;
  margin-left: 20px;
  padding-left: 20px;
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
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.save-btn:hover {
  transform: translateY(-5px);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.assessment-card {
  position: relative;
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
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.assessment-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
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

.assessment-title:hover {
  color: #731329;
  text-decoration: underline;
}

.delete-icon {
  position: absolute;
  top: 5px;
  right: 5px;
  width: 40px;
  height: 40px;
  background: url('@/assets/Cancel.png') no-repeat center center;
  background-size: contain;
  cursor: pointer;
  display: none;
  /* Initially hidden */
}

.confirmation-popup {
  background: #FFFFFF;
  border-radius: 15px;
  padding: 20px;
  width: 400px;
  text-align: center;
  box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
}

.confirmation-popup h2 {
  font-family: 'Inter';
  font-size: 20px;
  margin-bottom: 20px;
}

/* Button styling */
.confirmation-popup .buttons {
  display: flex;
  justify-content: space-between;
  gap: 20px;
  /* Add some gap between the buttons */
}

.confirmation-popup button {
  width: 120px;
  padding: 10px 20px;
  font-size: 18px;
  border-radius: 10px;
  cursor: pointer;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.confirmation-popup button.cancel {
  background: #D9D9D9;
  border: none;
}

.confirmation-popup button.delete {
  background: #FFBF48;
  color: #731329;
  border: none;
}

.confirmation-popup button.delete:hover {
  transform: translateY(-5px);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.confirmation-popup button.cancel:hover {
  transform: translateY(-5px);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.assessment-card:hover .delete-icon {
  display: block;
  /* Make delete icon visible on hover */
}

.confirmation-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.5);
  /* Dark background with opacity */
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  /* Make sure it's on top */
}
</style>