<template>
    <div>
        <h1>Student Dashboard</h1>

        <!-- Advisees Statistics -->
        <div class="statistics">
            <div class="stat-card">
                <h3>Total Course</h3>
                <p>{{ totalAdvisees }}</p>
            </div>
            <div class="stat-card">
                <h3>Total Assessment</h3>
                <p>{{ atRiskAdvisees }}</p>
            </div>
        </div>

        <div class="chart-section">
            <div class="filters">
                <label for="courseSelect">Select Course:</label>
                <select id="courseSelect" v-model="selectedCourse" @change="updateChart" :disabled="chartLoading">
                    <option v-for="course in courses.course" :key="course.course_id" :value="course.course_id">{{ course.course_code }} - {{ course.course_name }}</option>
                </select>

                <label style="margin-left: 20px">
                    <input type="checkbox" v-model="showAverageOnly" @change="renderChart" />
                    Show Average Only
                </label>
            </div>

            <canvas ref="progressChartCanvas"></canvas>
        </div>
    </div>
</template>

<script>
import Chart from "chart.js/auto";
export default {
    name: "StudentDashboard",
    data() {
        return {
            userName: "",
            selectedCourse: null,
            chart: null,
            courses: {
                course: [],
                output: [],
            },
            chartLoading: false,
            showAverageOnly: false,
            totalAdvisees: 2,
            atRiskAdvisees: 10,
            student_name: "John Doe",
        };
    },
    mounted() {
        const userString = localStorage.getItem("user");
        if (userString) {
            try {
                const user = JSON.parse(userString);
                this.userName = user.email || user.name || "User";
            } catch (error) {
                console.warn("Invalid user JSON:", userString);
            }
        }
        console.log("User Name:", this.userName);
        this.loadAllCourses();
    },
    methods: {
        logoutUser() {
            localStorage.removeItem("authToken");
            localStorage.removeItem("user");
            this.$router.push("/");
        },

        async loadAllCourses() {
            const token = localStorage.getItem("authToken");
            const student_id = localStorage.getItem("student_id");
            if (!token) return;

            try {
                const totalRes = await fetch("http://localhost:8085/api/findChart", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Authorization: `Bearer ${token}`,
                    },
                    body: JSON.stringify({ student_id: student_id }),
                });

                const totalData = await totalRes.json();
                console.log("Total Data:", JSON.stringify(totalData, null, 2));
                this.courses = totalData || { course: [], output: [] };

                if (this.courses.course.length > 0) {
                    this.selectedCourse = this.courses.course[0].course_id;
                    this.renderChart();
                }
            } catch (err) {
                console.error("Error loading courses or total calculation:", err);
            }
        },

        renderChart() {
            this.chartLoading = true;

            const courseIndex = this.courses.course.findIndex((c) => c.course_id === this.selectedCourse);
            const course = this.courses.course[courseIndex];
            const output = this.courses.output[courseIndex];

            if (!output || !output.students || !output.assessments) {
                this.chartLoading = false;
                return;
            }

            const labels = output.assessments.map((a) => a.name);
            const assessmentIds = output.assessments.map((a) => a.id.toString());

            let datasets;

            if (this.showAverageOnly) {
                const total = Array(assessmentIds.length).fill(0);
                const count = output.students.length;

                output.students.forEach((student) => {
                    assessmentIds.forEach((id, index) => {
                        total[index] += student.marks[id] || 0;
                    });
                });

                const average = total.map((sum) => (count ? sum / count : 0));

                datasets = [
                    {
                        label: "Average Marks",
                        data: average,
                        borderColor: "#007bff",
                        backgroundColor: "#007bff",
                        fill: false,
                        tension: 0.3,
                        borderWidth: 3,
                    },
                ];
            } else {
                datasets = output.students.map((student) => {
                    let cumulative = 0;
                    const cumulativeMarks = assessmentIds.map((id) => {
                        cumulative += student.marks[id] || 0;
                        return cumulative;
                    });
                    this.student_name = student.name;
                    return {
                        label: student.name,
                        data: cumulativeMarks,
                        borderColor: this.getRandomColor(),
                        fill: false,
                        tension: 0.3,
                    };
                });
            }

            if (this.chart) this.chart.destroy();

            const ctx = this.$refs.progressChartCanvas;
            this.chart = new Chart(ctx, {
                type: "line",
                data: {
                    labels,
                    datasets,
                },
                options: {
                    responsive: true,
                    animation: {
                        duration: 1000,
                        onComplete: () => {
                            this.chartLoading = false;
                        },
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: "Assessments",
                                font: { size: 18 },
                            },
                            ticks: { font: { size: 16 } },
                        },
                        y: {
                            beginAtZero: true,
                            max: 100, // ✅ Set max Y to 70%
                            title: {
                                display: true,
                                text: "Marks (%)",
                                font: { size: 18 },
                            },
                            ticks: { font: { size: 16 } },
                        },
                    },
                    plugins: {
                        tooltip: {
                            padding: 12,
                            bodyFont: { size: 16 },
                            titleFont: { size: 18 },
                            boxPadding: 8,
                            callbacks: {
                                label: function (context) {
                                    const data = context.dataset.data;
                                    const index = context.dataIndex;
                                    const current = data[index];
                                    const previous = index > 0 ? data[index - 1] : 0;
                                    const delta = current - previous;
                                    const student_name = this.student_name || "Student";

                                    return `${student_name}:+${delta} → Total: ${current}`;
                                },
                            },
                        },
                        legend: {
                            position: "bottom",
                            labels: {
                                font: { size: 18 },
                            },
                        },
                        title: {
                            display: true,
                            text: course.course_code + " - " + course.course_name,
                            font: { size: 24, weight: "bold" },
                        },
                    },
                },
            });
        },

        updateChart() {
            this.renderChart();
        },

        getRandomColor() {
            const letters = "0123456789ABCDEF";
            return (
                "#" +
                Array.from({ length: 6 })
                    .map(() => letters[Math.floor(Math.random() * 16)])
                    .join("")
            );
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
    padding: 5px 20px;
    border-radius: 10px;
    margin-bottom: 25px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}
.dashboard-container {
    max-width: 1500px;
    margin: auto;
    padding: 20px;
    font-family: Arial, sans-serif;
}
.chart-section {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}
.filters {
    margin-bottom: 20px;
}
label {
    font-weight: bold;
    margin-right: 10px;
}
select {
    padding: 6px 12px;
    margin-right: 10px;
    border-radius: 5px;
}
canvas {
    width: 100% !important;
    height: auto !important;
}

h1 {
    font-size: 38px;
    color: #770f20;
}

/* Advisees Statistics */
.statistics {
    display: flex;
    justify-content: space-between;
    margin-top: 40px;
}

.stat-card {
    background-color: #f5efe9;
    border-radius: 20px;
    width: 48%;
    padding: 20px;
    text-align: center;
}

.stat-card h3 {
    font-size: 24px;
    color: #770f20;
}

.stat-card p {
    font-size: 36px;
    font-weight: bold;
    color: #770f20;
}
</style>
