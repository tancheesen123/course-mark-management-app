<template>
  <div class="student-records-detail">
    <div class="header">
      <h1>Course Accessment - {{ courseName }}</h1>
      <!-- <div class="actions">
        <button v-if="!isEditing" class="edit-btn" @click="toggleEditMode">Edit</button>
        <button v-else class="cancel-btn" @click="cancelEdit">Cancel</button>
        <button class="add-btn" @click="addStudentRecord">Add</button>
      </div> -->
    </div>

    <!-- <div v-if="errorMessage" class="error-message">{{ errorMessage }}</div>

    <div v-else class="student-record-table">
      <div class="table-header">
        <span class="column-name">Accessment Name</span>
        <span class="column-matric">Type</span>
        <span class="column-matric">mark</span>
        <span class="column-matric">Weight</span>
        <span class="column-matric">feedback</span>
      </div>

      <div v-for="accessment in accessments" :key="accessment.assessment_id" class="student-record">
        <span class="student-name">{{ accessment.name }}</span>
        <span class="student-matric">{{ accessment.type }}</span>
        <span class="student-matric">{{ accessment.mark }}/{{ accessment.weight }}</span>
        <span class="student-matric">{{ accessment.weight }}%</span>
        <span class="student-matric">{{ accessment.feedback }}</span>
      </div>

      <div v-if="isEditing" class="update-section">
        <button class="update-btn" @click="updateMarks">Update</button>
      </div>
    </div> -->

    <!-- Summary Table -->
    <div class="summary-table">
      <h2>Assessment Summary</h2>
      <div class="summary-row header">
        <span class="col-name">Component</span>
        <span class="col-weight">Weight</span>
        <span class="col-score">Student's Mark</span>
        <span class="col-calculated">Calculated Weight</span>
      </div>

      <div
        class="summary-row"
        v-for="item in accessments"
        :key="item.name"
      >
        <span class="col-name">{{ item.name }}</span>
        <span class="col-weight">{{ item.weight }}%</span>
        <span class="col-score">{{ item.mark }}</span>
        <span class="col-calculated">{{ item.mark }}/{{ item.weight }}</span>
      </div>

      <div class="summary-row total">
        <span class="col-name">CA Total</span>
        <span class="col-weight">{{totalAssessmentWeight}}%</span>
        <span class="col-score">—</span>
        <span class="col-calculated"><strong>{{ totalAssessmentMarks }}/{{totalAssessmentWeight}} (scaled)</strong></span>
      </div>
      <div class="summary-row total">
        <span class="col-name">Final Exam</span>
        <span class="col-weight">{{finalExamAssessment.weight}}%</span>
        <span class="col-score">{{ finalExamAssessment.mark }}</span>
        <span class="col-calculated">{{ finalExamAssessment.mark }}/{{finalExamAssessment.weight}}</span>
      </div>
      <div class="summary-row total final-total">
        <span class="col-name">Total</span>
        <span class="col-weight">{{totalWeight}}%</span>
        <span class="col-score">—</span>
        <span class="col-calculated"><strong>{{ totalMarks }}/{{totalWeight}}</strong></span>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'StudentMark',
  data() {
    return {
      courseId: this.$route.query.course_id,
      courseName: this.$route.query.course_name,
      students: [],
      accessments: [],
      finalExamAssessment: {},
      errorMessage: "",
      isEditing: false,
      originalStudents: [],
      totalMarks: 0,
      finalExamMarks: 0,
      totalAssessmentMarks: 0,
      totalAssessmentWeight: 0,
      totalWeight: 0,
    };
  },
  async mounted() {
    await this.fetchData();
  },

  methods: {
    async fetchData() {
      this.errorMessage = "";
      const token = localStorage.getItem("authToken");
      const student_id = localStorage.getItem("student_id");
      if (!token) {
        this.errorMessage = "User not authenticated";
        return;
      }
      try {
        const response = await fetch(`http://localhost:8085/api/studentCourseMark`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${token}`,
          },
          body: JSON.stringify({ course_id: this.courseId, student_id }),
        });
        const data = await response.json();
        if (response.ok) {
            console.log("Data fetched successfully:", data);
          this.accessments = data.assessments;
          this.totalMarks = data.total_marks || 0;
          this.finalExamMarks = data.final_exam_marks;
          this.finalExamAssessment = data.finalExamAssessments || {};
          this.totalAssessmentMarks = data.total_assessment_marks;
          this.totalAssessmentWeight = data.total_assessment_weight || 0;
          this.totalWeight = data.total_weight || 0;
        } else {
          this.errorMessage = data.error || "Failed to load student records";
        }
      } catch (error) {
        console.error("Error loading data: ", error);
        this.errorMessage = "Failed to load records due to network error.";
      }
    },
    toggleEditMode() {
      this.isEditing = !this.isEditing;
    },
    cancelEdit() {
      this.students = JSON.parse(JSON.stringify(this.originalStudents));
      this.isEditing = false;
      this.errorMessage = "";
    },
    updateMarks() {
      // Your mark update logic here
    },
    addStudentRecord() {
      // Your add record logic here
    },
  },
};
</script>

<style scoped>
/* Reuse your existing styles above this line */

.summary-table {
  background-color: #fff;
  margin-top: 40px;
  padding: 20px 30px;
  border-radius: 10px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.summary-table h2 {
  color: #770F20;
  font-size: 24px;
  margin-bottom: 20px;
}

.summary-row {
  display: flex;
  padding: 10px 0;
  border-bottom: 1px solid #F1EDE9;
  align-items: center;
}

.summary-row.header {
  font-weight: bold;
  background-color: #F1EDE9;
  border-radius: 6px;
  padding: 12px 0;
}

.summary-row.total {
  font-weight: bold;
  background-color: #FFF9EB;
}

.summary-row.final-total {
  background-color: #FDEBD0;
  color: #4CAF50;
}

.col-name {
  flex: 2;
  min-width: 150px;
}

.col-weight,
.col-score,
.col-calculated {
  flex: 1.5;
  min-width: 120px;
}
</style>
