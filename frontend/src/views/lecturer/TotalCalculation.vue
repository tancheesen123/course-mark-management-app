<template>
    <div class="total-calculation-page">
        <div v-for="(course, index) in courses" :key="course.course_id" class="course-section">
            <div class="header">
                <h2>Section {{ course.section }} - {{ course.course_name }}</h2>
                <div class="actions">
                    <button v-if="!isEditing" class="edit-btn" @click="toggleExportCsv(index, course)">Export CSV</button>
                </div>
            </div>
            <div class="student-marks-table" v-if="course.students && course.assessments">
                <div class="table-row header">
                    <span class="col col-name">Name</span>
                    <span class="col col-matric">Matric Number</span>
                    <span class="col" v-for="a in course.assessments" :key="a.id">{{ a.name }}</span>
                    <span class="col col-total">Total (%)</span>
                    <span class="col col-grade">Grade</span>
                </div>

                <div class="table-row" v-for="student in course.students" :key="student.id">
                    <span class="col col-name">{{ student.name }}</span>
                    <span class="col col-matric">{{ student.matric_number }}</span>
                    <span class="col" v-for="a in course.assessments" :key="a.id">{{ formatMark(student.marks[a.id]) }}</span>
                    <span class="col col-total">
                        <strong>{{ calculateTotal(student, course.assessments) }}</strong>
                    </span>
                    <span class="col col-grade">
                        <strong :class="getGradeClass(calculateTotal(student, course.assessments))">
                            {{ calculateGrade(calculateTotal(student, course.assessments)) }}
                        </strong>
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "TotalCalculationPage",
    data() {
        return {
            courses: [],
            data: []
        };
    },
    async mounted() {
        await this.loadAllCourses();
    },
    methods: {
        async loadAllCourses() {
            const token = localStorage.getItem("authToken");
            if (!token) return;

            try {
                const res = await fetch("http://localhost:8085/api/lecturer-courses", {
                    method: "GET",
                    headers: {
                        Authorization: `Bearer ${token}`,
                    },
                });

                const courseList = await res.json();
                console.log("This is course list".courseList);

                for (const course of courseList) {
                    const totalRes = await fetch("http://localhost:8085/api/total-calculation", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            Authorization: `Bearer ${token}`,
                        },
                        body: JSON.stringify({ course_id: course.course_id }),
                    });

                    const totalData = await totalRes.json();

                    this.courses.push({
                        ...course,
                        assessments: totalData.assessments,
                        students: totalData.students,
                    });
                    console.log("Course with assessments and students:", JSON.stringify(this.courses));
                }
            } catch (err) {
                console.error("Error loading courses or total calculation:", err);
            }
        },
        async toggleExportCsv(index) {
          const token = localStorage.getItem("authToken");
            this.isEditing = !this.isEditing;
            console.log("This is index", index);

            try {
                const res = await fetch(`http://localhost:8085/api/exportCSV/${index}`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Authorization: `Bearer ${token}`,
                    },
                    body: JSON.stringify(this.courses),
                });

                if (!res.ok) {
                    throw new Error("Export failed");
                }

                const blob = await res.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement("a");
                const disposition = res.headers.get("Content-Disposition");
                const match = disposition && disposition.match(/filename="(.+)"/);
                const filename = match ? match[1] : `${this.courses[index].course_name}_section_${this.courses[index].section}.csv`;
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(url);
            } catch (err) {
                console.error("CSV export error:", err);
                alert("Failed to export CSV");
            }
        },
        formatMark(mark) {
            return typeof mark === "number" ? mark.toFixed(1) : "-";
        },
        calculateTotal(student, assessments) {
            let total = 0;
            assessments.forEach((a) => {
                const mark = student.marks[a.id];
                if (typeof mark === "number") {
                    total += mark;
                }
            });
            return total.toFixed(1);
        },
        calculateGrade(totalPercentageString) {
            const totalPercentage = parseFloat(totalPercentageString); // Convert to number for comparison

            if (isNaN(totalPercentage)) {
                return "-";
            }

            // Based on image_bc5c36.png
            if (totalPercentage >= 90) {
                return "A+";
            } else if (totalPercentage >= 80) {
                return "A";
            } else if (totalPercentage >= 75) {
                return "A-";
            } else if (totalPercentage >= 70) {
                return "B+";
            } else if (totalPercentage >= 65) {
                return "B";
            } else if (totalPercentage >= 60) {
                return "B-";
            } else if (totalPercentage >= 55) {
                return "C+";
            } else if (totalPercentage >= 50) {
                return "C";
            } else if (totalPercentage >= 45) {
                return "C-";
            } else if (totalPercentage >= 40) {
                return "D+";
            } else if (totalPercentage >= 35) {
                return "D";
            } else if (totalPercentage >= 30) {
                return "D-";
            } else {
                return "E";
            }
        },
        getGradeClass(totalPercentageString) {
            const totalPercentage = parseFloat(totalPercentageString);
            if (isNaN(totalPercentage)) {
                return "";
            }
            if (totalPercentage >= 70) {
                // B+ and above are green
                return "grade-green";
            } else if (totalPercentage >= 40) {
                // D+ to C- are yellow
                return "grade-yellow";
            } else {
                // D, D-, E are red
                return "grade-red";
            }
        },
    },
};
</script>

<style scoped>
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #fff9eb;
    /* Matches the background of the dashboard header*/
    padding: 5px 20px;
    /* Adjusted padding to match the dashboard's header*/
    border-radius: 10px;
    /* Rounded corners similar to dashboard elements*/
    margin-bottom: 25px;
    /* Spacing below the header */
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    /* Subtle shadow for depth, similar to dashboard elements*/
}

.total-calculation-page {
    padding: 30px;
    background: #fffaf7;
}

.course-section {
    margin-bottom: 40px;
}

.student-marks-table {
    margin-top: 20px;
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
}

.table-row {
    display: flex;
    padding: 12px 16px;
    border-bottom: 1px solid #eee;
    align-items: center;
}

.table-row.header {
    background: #f1ede9;
    font-weight: bold;
}

.col {
    flex: 1;
    text-align: center;
    min-width: 80px;
}

.col-name {
    flex: 2;
    text-align: left;
}

.col-matric {
    flex: 2;
    text-align: center;
}

.col-total {
    font-weight: bold;
    color: #4caf50;
    flex: 1;
}

/* Grade specific colors */
.grade-green {
    color: #4caf50; /* Green for A+, A, A-, B+, B, B- */
}

.grade-yellow {
    color: #ffc107; /* Yellow for C+, C, C- */
}

.grade-red {
    color: #d32f2f; /* Red for D+, D, D-, E */
}

.actions button {
    background-color: #ffbf48;
    /* Orange color for buttons*/
    padding: 10px 20px;
    border-radius: 8px;
    /* Slightly more rounded buttons*/
    border: none;
    cursor: pointer;
    color: #731329;
    /* Darker text color for contrast on orange*/
    font-weight: bold;
    margin-left: 10px;
    transition: background-color 0.3s ease, transform 0.2s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    /* Subtle shadow for buttons*/
}

.actions button:hover {
    background-color: #f0a643;
    /* Slightly darker orange on hover*/
    transform: translateY(-1px);
    /* Slight lift on hover */
}

.actions .cancel-btn {
    background-color: #a3a3a3;
    /* A neutral color for cancel */
    color: #ffffff;
}

.actions .cancel-btn:hover {
    background-color: #8c8c8c;
}
</style>
