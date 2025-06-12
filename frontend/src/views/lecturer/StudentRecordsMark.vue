<template>
  <div class="student-records-detail">
    <div class="header">
      <h1>Manage Student Records - {{ assessmentName }}</h1>
      <div class="actions">
        <button v-if="!isEditing" class="edit-btn" @click="toggleEditMode">Edit</button>
        <button v-else class="cancel-btn" @click="cancelEdit">Cancel</button>
        <button class="add-btn" @click="addStudentRecord">Add</button>
      </div>
    </div>

    <div v-if="errorMessage" class="error-message">{{ errorMessage }}</div>

    <div v-else class="student-record-table">
      <div class="table-header">
        <span class="column-name">Name</span>
        <span class="column-matric">Matric Number</span>
        <span class="column-mark">Mark</span>
      </div>

      <div v-for="student in students" :key="student.matric_number" class="student-record">
        <span class="student-name">{{ student.name }}</span>
        <span class="student-matric">{{ student.matric_number }}</span>
        <span class="student-mark">
          <span v-if="!isEditing">{{ student.mark }} / {{ assessmentWeight }}</span>
          <input
            v-else
            type="number"
            v-model.number="student.mark"
            :class="{ 'input-error': student.markError }"
            @input="validateMark(student)"
          />
          <span v-if="isEditing"> / {{ assessmentWeight }}</span>
        </span>
      </div>

      <div v-if="isEditing" class="update-section">
        <button class="update-btn" @click="updateMarks">Update</button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'StudentRecordsDetail',
  data() {
    return {
      courseId: this.$route.query.course_id, // Access course_id from query params
      assessmentName: this.$route.query.name, // Access assessment_name from query params
      students: [],
      assessmentWeight: null,
      errorMessage: "",
      isEditing: false, // Controls edit mode
      originalStudents: [], // To store a copy for cancellation
    };
  },
  async mounted() {
    await this.fetchData();
  },
  methods: {
    async fetchData() {
      this.errorMessage = ""; // Clear previous errors
      const token = localStorage.getItem("authToken");
      if (!token) {
        this.errorMessage = "User not authenticated";
        return;
      }

      try {
        // 1. Fetch assessment details (including weight)
        const assessmentResponse = await fetch(`http://localhost:8085/api/assessments?course_id=${this.courseId}&name=${encodeURIComponent(this.assessmentName)}`, {
          method: 'GET',
          headers: {
            Authorization: `Bearer ${token}`,
          },
        });
        const assessmentData = await assessmentResponse.json();

        if (assessmentResponse.ok && assessmentData.assessment_component && assessmentData.assessment_component.length > 0) {
          this.assessmentWeight = assessmentData.assessment_component[0].weight;
        } else {
          this.errorMessage = "Failed to load assessment details or assessment not found.";
          return;
        }

        // 2. Fetch student records
        const studentRecordsResponse = await fetch(`http://localhost:8085/api/student-records?course_id=${this.courseId}&assessment_name=${encodeURIComponent(this.assessmentName)}`, {
          method: 'GET',
          headers: {
            Authorization: `Bearer ${token}`,
          },
        });

        const studentRecordsData = await studentRecordsResponse.json();

        if (studentRecordsResponse.ok && studentRecordsData.students) {
          // Add a temporary 'markError' property to each student for validation feedback
          this.students = studentRecordsData.students.map(s => ({
            ...s,
            mark: parseFloat(s.mark), // Ensure mark is a number
            markError: false // For validation styling
          }));
          this.originalStudents = JSON.parse(JSON.stringify(this.students)); // Deep copy for cancel
        } else {
          this.errorMessage = studentRecordsData.error || "Failed to load student records or no records found";
        }
      } catch (error) {
        console.error("Error loading data: ", error);
        this.errorMessage = "Failed to load records due to network error.";
      }
    },
    toggleEditMode() {
      this.isEditing = !this.isEditing;
      if (this.isEditing) {
        // When entering edit mode, clear any previous errors on marks
        this.students.forEach(student => {
          student.markError = false;
        });
      }
    },
    cancelEdit() {
      // Revert to original student data and exit edit mode
      this.students = JSON.parse(JSON.stringify(this.originalStudents));
      this.isEditing = false;
      this.errorMessage = ""; // Clear any validation errors
    },
    validateMark(student) {
      student.markError = false; // Reset error
      let isValid = true;

      if (student.mark < 0) {
        student.markError = true;
        this.errorMessage = "Mark cannot be negative.";
        isValid = false;
      } else if (this.assessmentWeight !== null && student.mark > this.assessmentWeight) {
        student.markError = true;
        this.errorMessage = `Mark cannot exceed ${this.assessmentWeight}.`;
        isValid = false;
      } else {
        this.errorMessage = ""; // Clear specific error if valid
      }
      return isValid;
    },
    async updateMarks() {
      const token = localStorage.getItem("authToken");
      if (!token) {
        this.errorMessage = "User not authenticated";
        return;
      }

      // First, perform a full validation pass on all marks
      let allMarksValid = true;
      let validationErrorMessages = [];

      this.students.forEach(student => {
        student.markError = false; // Reset error for each student
        if (student.mark < 0) {
          student.markError = true;
          validationErrorMessages.push(`${student.name}'s mark cannot be negative.`);
          allMarksValid = false;
        } else if (this.assessmentWeight !== null && student.mark > this.assessmentWeight) {
          student.markError = true;
          validationErrorMessages.push(`${student.name}'s mark cannot exceed ${this.assessmentWeight}.`);
          allMarksValid = false;
        }
      });

      if (!allMarksValid) {
        // Display combined error messages
        this.errorMessage = validationErrorMessages.join(" ");
        return; // Stop the update process
      } else {
        this.errorMessage = ""; // Clear any previous error message
      }

      // Prepare data for the API call
      const marksToUpdate = this.students.map(student => ({
        student_id: student.student_id, // Ensure student_id is passed
        assessment_id: null, // This will be set by the backend
        mark: student.mark
      }));

      try {
        const response = await fetch('http://localhost:8085/api/student-marks/batch-update', {
          method: 'PATCH', // Using PATCH for partial updates (marks only)
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`,
          },
          body: JSON.stringify({
            course_id: this.courseId,
            assessment_name: this.assessmentName,
            marks: marksToUpdate,
          }),
        });

        const data = await response.json();

        if (response.ok) {
          alert(data.message); // Or use a more subtle notification
          this.isEditing = false; // Exit edit mode
          await this.fetchData(); // Re-fetch data to ensure UI is in sync
        } else {
          this.errorMessage = data.error || "Failed to update marks.";
        }
      } catch (error) {
        console.error("Error updating marks: ", error);
        this.errorMessage = "Network error or server unavailable during mark update.";
      }
    },
    addStudentRecord() {
    // Navigate to the new AddStudentRecord component, passing current course and assessment context
    this.$router.push({
      name: 'AddStudentRecord', // Use the name defined in your router
      query: {
        course_id: this.courseId,
        name: this.assessmentName // This is the assessment name (e.g., 'Quiz 1')
      }
    });
  },
  },
};
</script>

<style scoped>
/* Main container for the content area */
.student-records-detail {
  padding: 20px 40px; /* Increased padding to match the dashboard content area style*/
  background-color: #F8F5F1; /* Light background from dashboard content area*/
  min-height: 100vh;
  box-sizing: border-box;
}

/* Header Section (Manage Student Records - Quiz 1) */
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background-color: #FFF9EB; /* Matches the background of the dashboard header*/
  padding: 20px 30px; /* Adjusted padding to match the dashboard's header*/
  border-radius: 10px; /* Rounded corners similar to dashboard elements*/
  margin-bottom: 25px; /* Spacing below the header */
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); /* Subtle shadow for depth, similar to dashboard elements*/
}

.header h1 {
  font-size: 32px; /* Font size from dashboard headers*/
  color: #770F20; /* Dark red color from UTM branding/dashboard*/
  margin: 0;
  font-weight: 600; /* Slightly bolder font */
}

/* Action Buttons (Edit, Add, Cancel) */
.actions button {
  background-color: #FFBF48; /* Orange color for buttons*/
  padding: 10px 20px;
  border-radius: 8px; /* Slightly more rounded buttons*/
  border: none;
  cursor: pointer;
  color: #731329; /* Darker text color for contrast on orange*/
  font-weight: bold;
  margin-left: 10px;
  transition: background-color 0.3s ease, transform 0.2s ease;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Subtle shadow for buttons*/
}

.actions button:hover {
  background-color: #F0A643; /* Slightly darker orange on hover*/
  transform: translateY(-1px); /* Slight lift on hover */
}

.actions .cancel-btn {
    background-color: #A3A3A3; /* A neutral color for cancel */
    color: #FFFFFF;
}

.actions .cancel-btn:hover {
    background-color: #8C8C8C;
}


/* Student Record Table Container */
.student-record-table {
  background-color: #FFFFFF; /* Pure white background for the table area*/
  padding: 20px 30px; /* Padding inside the table container*/
  border-radius: 10px; /* Rounded corners for the table container*/
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); /* Subtle shadow for depth*/
}

/* Table Header Row */
.table-header {
  background-color: #F1EDE9; /* Light gray background for table header row*/
  display: flex;
  font-weight: bold;
  padding: 12px 0; /* Vertical padding for header cells */
  border-bottom: 2px solid #D6D0C5; /* Slightly darker border below header*/
  margin-bottom: 15px; /* Space between header and first data row */
  border-radius: 8px; /* Rounded corners for the header row */
  padding-left: 30px; /* Match internal padding of table container */
  padding-right: 30px; /* Match internal padding of table container */
}

/* Column Sizing and Alignment */
.column-name,
.student-name {
  flex: 2; /* Takes more flexible space */
  min-width: 150px; /* Minimum width to prevent squishing */
  text-align: left;
}

.column-matric,
.student-matric {
  flex: 1.5; /* Takes medium flexible space */
  min-width: 120px; /* Minimum width */
  text-align: left;
}

.column-mark,
.student-mark {
  flex: 0.5; /* Takes less flexible space */
  min-width: 80px; /* Minimum width */
  text-align: right; /* Right align for numerical data */
  display: flex; /* Use flexbox for mark column to align input/text */
  align-items: center; /* Vertically align items */
  justify-content: flex-end; /* Align content to the right */
  gap: 5px; /* Space between mark input/text and /weight */
}

/* Individual Student Record Row */
.student-record {
  display: flex;
  padding: 12px 30px; /* Consistent padding for data rows*/
  border-bottom: 1px solid #F1EDE9; /* Light border between rows*/
  align-items: center; /* Vertically center content */
  color: #333; /* Dark text color for readability*/
}

.student-record:last-child {
  border-bottom: none; /* Remove border from the last row */
}

/* Font Sizes for table content */
.student-name, .student-matric, .student-mark {
  font-size: 17px; /* Consistent font size for table content*/
}

/* Specific styling for Mark */
.student-mark {
  font-weight: bold;
  color: #770F20; /* Dark red for marks to make them stand out*/
}

/* Input field for marks */
.student-mark input[type="number"] {
  width: 70px; /* Adjust width as needed */
  padding: 5px 8px;
  border: 1px solid #ccc;
  border-radius: 4px;
  font-size: 16px;
  text-align: right;
  outline: none; /* Remove outline on focus */
}

.student-mark input[type="number"]:focus {
  border-color: #FFBF48; /* Highlight on focus */
  box-shadow: 0 0 0 2px rgba(255, 191, 72, 0.2);
}

.input-error {
  border-color: #D32F2F !important; /* Red border for errors */
  box-shadow: 0 0 0 2px rgba(211, 47, 47, 0.2) !important;
}


/* Error Message Styling */
.error-message {
  color: #D32F2F; /* More distinct red for error messages*/
  font-size: 18px;
  margin-top: 20px;
  margin-bottom: 20px; /* Added margin-bottom */
  font-weight: bold;
  background-color: #FFEBEE; /* Light red background for error message*/
  padding: 15px;
  border-radius: 8px;
  border: 1px solid #EF9A9A; /* Red border for error message*/
  text-align: center;
}

/* Update Button section */
.update-section {
    display: flex;
    justify-content: center;
    margin-top: 30px;
}

.update-btn {
    background-color: #4CAF50; /* Green for update button */
    padding: 12px 25px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    color: white;
    font-weight: bold;
    font-size: 18px;
    transition: background-color 0.3s ease, transform 0.2s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.update-btn:hover {
    background-color: #45a049;
    transform: translateY(-1px);
}

/* Sidebar styling (Assuming it's a separate component but keeping for context) */
.sidebar {
  width: 224px;
  background-color: #7C192E; /* Dark red sidebar background from dashboard*/
  color: white;
  padding: 20px;
}

.nav-links {
  display: flex;
  flex-direction: column;
}

.nav-links a {
  color: white;
  text-decoration: none;
  padding: 10px;
  border-radius: 4px;
  margin-bottom: 8px;
}

.nav-links a.active-link {
  background-color: #F5EFE9; /* Light background for active link*/
  color: #333; /* Dark text for active link*/
}
</style>