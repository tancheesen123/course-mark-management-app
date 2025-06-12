<template>
  <div class="add-student-record">
    <div class="title-cover">
      <div class="header-content">
        <h1>Manage Student Records - {{ assessmentName }}</h1>
        <button class="back-btn" @click="goBack">Back</button>
      </div>
    </div>

    <div class="add-record-form-container">
      <div class="add-record-form">
        <h2>Add Record</h2>

        <form @submit.prevent="saveRecord">
          <div class="input-group">
            <label for="studentName">Name<span class="required">*</span></label>
            <input type="text" id="studentName" v-model="newRecord.name" required>
          </div>

          <div class="input-group">
            <label for="matricNumber">Matric No<span class="required">*</span></label>
            <input type="text" id="matricNumber" v-model="newRecord.matric_number" required>
          </div>

          <div class="input-group">
            <label for="mark">Mark<span class="required">*</span></label>
            <div class="mark-input-wrapper">
              <input
                type="number"
                id="mark"
                v-model.number="newRecord.mark"
                :class="{ 'input-error': markError }"
                @input="validateMark"
                required
              >
              <span> / {{ assessmentWeight }}</span>
            </div>
            <p v-if="markError" class="error-text">{{ markErrorMessage }}</p>
          </div>

          <p v-if="errorMessage" class="error-message">{{ errorMessage }}</p>

          <div class="form-actions">
            <button type="button" class="cancel-btn" @click="goBack">Cancel</button>
            <button type="submit" class="save-btn">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AddStudentRecord',
  data() {
    return {
      courseId: this.$route.query.course_id,
      assessmentName: this.$route.query.name,
      assessmentWeight: null,
      newRecord: {
        name: '',
        matric_number: '',
        mark: null,
      },
      markError: false,
      markErrorMessage: '',
      errorMessage: '',
    };
  },
  async mounted() {
    await this.fetchAssessmentWeight();
  },
  methods: {
    async fetchAssessmentWeight() {
      const token = localStorage.getItem("authToken");
      if (!token) {
        this.errorMessage = "User not authenticated";
        return;
      }
      try {
        const response = await fetch(`http://localhost:8085/api/assessments?course_id=${this.courseId}&name=${encodeURIComponent(this.assessmentName)}`, {
          method: 'GET',
          headers: {
            Authorization: `Bearer ${token}`,
          },
        });
        const data = await response.json();
        if (response.ok && data.assessment_component && data.assessment_component.length > 0) {
          this.assessmentWeight = data.assessment_component[0].weight;
        } else {
          this.errorMessage = "Failed to load assessment details.";
        }
      } catch (error) {
        console.error("Error fetching assessment weight:", error);
        this.errorMessage = "Network error while fetching assessment details.";
      }
    },
    validateMark() {
      this.markError = false;
      this.markErrorMessage = '';
      if (this.newRecord.mark === null || this.newRecord.mark === '') {
        // Mark can be empty when input is cleared, don't show error immediately but it's "required"
        return true;
      }
      const mark = parseFloat(this.newRecord.mark);

      if (isNaN(mark)) {
        this.markError = true;
        this.markErrorMessage = "Mark must be a number.";
        return false;
      }
      if (mark < 0) {
        this.markError = true;
        this.markErrorMessage = "Mark cannot be negative.";
        return false;
      }
      if (this.assessmentWeight !== null && mark > this.assessmentWeight) {
        this.markError = true;
        this.markErrorMessage = `Mark cannot exceed ${this.assessmentWeight}.`;
        return false;
      }
      return true;
    },
    async saveRecord() {
      this.errorMessage = "";
      if (!this.validateMark()) {
        return; // Stop if mark is invalid
      }

      if (!this.newRecord.name || !this.newRecord.matric_number || this.newRecord.mark === null) {
        this.errorMessage = "All fields are required.";
        return;
      }

      const token = localStorage.getItem("authToken");
      if (!token) {
        this.errorMessage = "User not authenticated";
        return;
      }

      try {
        const response = await fetch('http://localhost:8085/api/student-records/add', { // New API endpoint
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`,
          },
          body: JSON.stringify({
            name: this.newRecord.name,
            matric_number: this.newRecord.matric_number,
            course_id: this.courseId,
            assessment_name: this.assessmentName,
            mark: this.newRecord.mark,
          }),
        });

        const data = await response.json();

        if (response.ok) {
          alert(data.message); // Use a notification system in a real app
          this.goBack(); // Go back to the previous page on success
        } else {
          this.errorMessage = data.error || "Failed to add student record.";
        }
      } catch (error) {
        console.error("Error saving record:", error);
        this.errorMessage = "Network error or server unavailable.";
      }
    },
    goBack() {
      this.$router.go(-1); // Go back to the previous page
    },
  },
};
</script>

<style scoped>
/* Base layout styles based on Figma CSS */
.add-student-record {
  position: relative;
  width: 100%; /* Adjust to fill parent */
  min-height: 100vh;
  background: #FFFBF7; /* Adjusted to fill the content area */
  padding-bottom: 50px; /* Add some padding at the bottom */
  box-sizing: border-box;
}

.title-cover {
  width: 100%; /* Adjust to fill parent */
  height: 140px;
  background: #FFF9EB;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); /* Subtle shadow for depth */
  display: flex;
  align-items: center;
  padding: 0 40px; /* Padding from the edge */
  margin-bottom: 25px; /* Spacing below the header */
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%; /* Make sure content takes full width of title-cover padding */
}

.title-cover h1 {
  font-family: 'Inter', sans-serif;
  font-weight: 700;
  font-size: 32px; /* Adjusted from 48px to better fit common headers */
  color: #770F20;
  margin: 0;
}

.back-btn {
  background-color: #FFBF48;
  padding: 10px 20px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  color: #731329;
  font-weight: bold;
  transition: background-color 0.3s ease, transform 0.2s ease;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.back-btn:hover {
  background-color: #F0A643;
  transform: translateY(-1px);
}

/* Add Record Form Container */
.add-record-form-container {
  display: flex;
  justify-content: center; /* Center the form horizontally */
  padding: 0 40px; /* Maintain padding from the sides */
}

.add-record-form {
  background: #FFFFFF;
  border-radius: 10px; /* Adjusted to 10px for consistency */
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  padding: 30px 40px; /* Increased padding inside the form */
  width: 100%;
  max-width: 800px; /* Set a max-width for better readability on large screens */
  box-sizing: border-box;
}

.add-record-form h2 {
  font-family: 'Inter', sans-serif;
  font-weight: 700;
  font-size: 30px; /* Adjusted from 36px */
  color: #6E1317;
  text-align: center;
  margin-bottom: 30px; /* Spacing below the title */
}

.input-group {
  margin-bottom: 25px; /* Spacing between input groups */
}

.input-group label {
  font-family: 'Inter', sans-serif;
  font-weight: 500;
  font-size: 20px; /* Adjusted from 24px */
  color: #000000;
  display: block; /* Make label take full width */
  margin-bottom: 8px; /* Space between label and input */
}

.input-group .required {
  color: #D32F2F; /* Red asterisk for required fields */
  margin-left: 5px;
}

.input-group input[type="text"],
.input-group input[type="number"] {
  width: 100%;
  padding: 12px 15px; /* Consistent padding */
  border: 1px solid rgba(0, 0, 0, 0.26);
  border-radius: 5px;
  font-family: 'Inter', sans-serif;
  font-size: 18px; /* Consistent font size */
  color: #000000;
  box-sizing: border-box; /* Include padding in width */
}

.input-group input[type="text"]:focus,
.input-group input[type="number"]:focus {
  outline: none;
  border-color: #FFBF48; /* Highlight on focus */
  box-shadow: 0 0 0 2px rgba(255, 191, 72, 0.2);
}

.mark-input-wrapper {
  display: flex;
  align-items: center;
  gap: 10px; /* Space between input and /weight */
}

.mark-input-wrapper input {
  width: 40px; /* Specific width for mark input */
  text-align: right; /* Align mark to the right */
  padding: 12px 5px;
  
}

.mark-input-wrapper span {
  font-family: 'Inter', sans-serif;
  font-weight: 500;
  font-size: 18px; /* Consistent font size */
  color: #000000;
}

.input-error {
  border-color: #D32F2F !important;
  box-shadow: 0 0 0 2px rgba(211, 47, 47, 0.2) !important;
}

.error-text {
  color: #D32F2F;
  font-size: 14px;
  margin-top: 5px;
}

.error-message {
  color: #D32F2F;
  font-size: 16px;
  margin-top: 15px;
  margin-bottom: 20px;
  font-weight: bold;
  background-color: #FFEBEE;
  padding: 10px;
  border-radius: 8px;
  border: 1px solid #EF9A9A;
  text-align: center;
}

.form-actions {
  display: flex;
  justify-content: flex-end; /* Align buttons to the right */
  gap: 20px; /* Space between buttons */
  margin-top: 30px; /* Space above buttons */
}

.cancel-btn, .save-btn {
  padding: 12px 30px; /* Larger padding for buttons */
  border-radius: 10px;
  border: none;
  cursor: pointer;
  font-family: 'Inter', sans-serif;
  font-weight: 500;
  font-size: 20px; /* Larger font size */
  transition: background-color 0.3s ease, transform 0.2s ease;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.cancel-btn {
  background: #D9D9D9;
  color: #000000;
}

.cancel-btn:hover {
  background: #C0C0C0;
  transform: translateY(-1px);
}

.save-btn {
  background: #FFBF48;
  color: #000000;
}

.save-btn:hover {
  background: #F0A643;
  transform: translateY(-1px);
}

</style>