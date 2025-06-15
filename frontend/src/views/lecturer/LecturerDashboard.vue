<template>
    <div class="dashboard-container">
        <div class="header">
            <h2>Lecturer Dashboard</h2>
            <p>
                Welcome back, <strong>{{ userName }}</strong
                >!
            </p>
        </div>

        <div class="chart-section">
            <div class="filters">
                <label for="courseSelect">Select Course:</label>
                <select id="courseSelect" v-model="selectedCourse" @change="updateChart" :disabled="chartLoading">
                    <option v-for="course in courses" :key="course.course_id" :value="course.course_id">{{ course.course_code }} - {{ course.course_name }}</option>
                </select>

                <label style="margin-left: 20px">
                    <input :disabled="chartLoading" type="checkbox" v-model="showAverageOnly" @change="renderChart" />
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
    name: "LecturerDashboard",
    data() {
        return {
            userName: "",
            selectedCourse: null,
            chart: null,
            courses: [],
            chartLoading: false,
            showAverageOnly: false, // <-- New flag
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
            if (!token) return;

            try {
                const res = await fetch("http://localhost:8085/api/lecturer-courses", {
                    method: "GET",
                    headers: { Authorization: `Bearer ${token}` },
                });

                const courseList = await res.json();

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
                }

                if (this.courses.length > 0) {
                    this.selectedCourse = this.courses[0].course_id;
                    this.renderChart();
                }
            } catch (err) {
                console.error("Error loading courses or total calculation:", err);
            }
        },

        renderChart() {
            this.chartLoading = true;

            const course = this.courses.find((c) => c.course_id === this.selectedCourse);
            if (!course || !course.students || !course.assessments) {
                this.chartLoading = false;
                return;
            }

            const labels = course.assessments.map((a) => a.name);
            const assessmentIds = course.assessments.map((a) => a.id.toString());

            let datasets;

            if (this.showAverageOnly) {
                // Calculate average per assessment
                const total = Array(assessmentIds.length).fill(0);
                const count = course.students.length;

                course.students.forEach((student) => {
                    assessmentIds.forEach((id, index) => {
                        total[index] += student.marks[id] || 0;
                    });
                });

                const average = total.map((sum) => (count ? sum / count : 0));

                datasets = [
                    {
                        label: "Average Marks",
                        data: average,
                        fill: false,
                        borderColor: "#007bff",
                        backgroundColor: "#007bff",
                        tension: 0.3,
                        borderWidth: 3,
                    },
                ];
            } else {
                datasets = course.students.map((student) => {
                    const studentMarks = assessmentIds.map((id) => student.marks[id] || 0);
                    return {
                        label: student.name,
                        data: studentMarks,
                        fill: false,
                        borderColor: this.getRandomColor(),
                        tension: 0.3,
                    };
                });
            }

            if (this.chart) this.chart.destroy();

            const ctx = this.$refs.progressChartCanvas;
            this.chart = new Chart(ctx, {
                type: "line",
                data: {
                    labels: labels,
                    datasets: datasets,
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
                            max: 70,
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
                            padding: 12, // 👈 Increase padding inside the tooltip box
                            bodyFont: {
                                size: 16, // 👈 Font size for content
                            },
                            titleFont: {
                                size: 18, // 👈 Font size for title
                            },
                            boxPadding: 8, // 👈 Space between color box and text
                        },
                        legend: {
                            position: "bottom",
                            labels: {
                                font: { size: 18 },
                            },
                        },
                        title: {
                            display: true,
                            text: `Student Progress (${course.course_code} - ${course.course_name})`,
                            font: { size: 18 },
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
            let color = "#";
            for (let i = 0; i < 6; i++) {
                color += letters[Math.floor(Math.random() * 16)];
            }
            return color;
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
</style>
