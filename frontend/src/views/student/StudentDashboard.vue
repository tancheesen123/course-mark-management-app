/* eslint-disable */
/* babel-disable */
/* @babel/disable */
/* @babel/disable-config-file */
/* @babel/disable-config-file-check */
/* @babel/disable-config-file-checking */
/* @babel/disable-config-file-checking-requireConfigFile */
/* @babel/disable-config-file-checking-requireConfigFile: false */
/* @babel/disable-config-file-checking-requireConfigFile: false */
/* @babel/disable-config-file-checking-requireConfigFile: false */
/* @babel/disable-config-file-checking-requireConfigFile: false */
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
                <select id="courseSelect" v-model="selectedCourse" @change="onCourseChange" :disabled="chartLoading">
                    <option v-for="course in courses.course" :key="course.course_id" :value="course.course_id">
                        {{ course.course_code }} - {{ course.course_name }}
                    </option>
                </select>
                <label style="margin-left: 20px">
                    <input type="checkbox" v-model="showAverageOnly" @change="renderChart" />
                    Show Average Only
                </label>
            </div>

            <canvas ref="progressChartCanvas"></canvas>

            <div v-if="selectedCourse && rankingChartData && chartData">
                <div class="compare-summary-card">
                    <h2>Compare with Coursemates</h2>
                    <p>
                        <strong>{{ student_name }}</strong>
                        ranks <strong>{{ studentRank }}</strong> out of <strong>{{ totalStudents }}</strong> in the course.
                    </p>
                </div>
                <div class="student-position-section">
                    <h2>Student Position in Class</h2>
                    <RankingChart :data="rankingChartData" :options="{ responsive: true, plugins: { legend: { display: false } } }" />
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Chart from "chart.js/auto";
import RankingChart from "@/views/advisor/RankingChart.vue";
export default {
    name: "StudentDashboard",
    components: {
        RankingChart
    },
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
            averageMarks: [],
            rankingMarks: [],
            studentRank: null,
            totalStudents: null,
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
        this.fetchRanking();
        this.fetchAverageMarks();
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
                    this.onCourseChange();
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

        async fetchRanking() {
            if (!this.selectedCourse) return;
            try {
                const res = await fetch(
                    `http://localhost:8085/api/public/advisor/courses/${this.selectedCourse}/ranking`
                );
                const data = await res.json();
                if (res.ok && data.success && Array.isArray(data.ranks)) {
                    const sorted = data.ranks.sort((a, b) => b.total_mark - a.total_mark);
                    this.totalStudents = sorted.length;
                    const student_id = localStorage.getItem("student_id");
                    const index = sorted.findIndex((s) => s.student_id == student_id);
                    if (index !== -1) this.studentRank = index + 1;

                    this.rankingMarks = sorted.map((s, i) => ({
                        label: `#${i + 1}`,
                        value: Math.round(s.total_mark * 100) / 100,
                        isTarget: s.student_id == student_id,
                    }));
                }
            } catch (err) {
                console.error("Error loading ranking", err);
            }
        },
        async fetchAverageMarks() {
            if (!this.selectedCourse) return;
            try {
                const res = await fetch(
                    `http://localhost:8085/api/public/advisor/courses/${this.selectedCourse}/average-marks`
                );
                const data = await res.json();
                if (res.ok && data.success) {
                    this.averageMarks = data.averages;
                }
            } catch (err) {
                console.error("Error fetching averages", err);
            }
        },
        onCourseChange() {
            this.fetchRanking();
            this.fetchAverageMarks();
            this.renderChart();
        },
    },
    computed: {
        chartData() {
            if (!this.averageMarks.length) return null;
            const categories = ["Quiz", "Lab", "Exercise", "Test", "Assignment", "Final"];
            const averageData = categories.map((type) => {
                const match = this.averageMarks.find((avg) =>
                    avg.name?.toLowerCase().startsWith(type.toLowerCase())
                );
                return match ? Math.round(match.average_mark * 100) / 100 : 0;
            });
            return {
                labels: categories,
                datasets: [
                    {
                        label: "Average Marks",
                        data: averageData,
                        borderColor: "#007bff",
                        backgroundColor: "#007bff",
                    },
                ],
            };
        },
        rankingChartData() {
            if (!this.rankingMarks.length) return null;
            return {
                labels: this.rankingMarks.map((r) => r.label),
                datasets: [
                    {
                        label: "Total Marks",
                        backgroundColor: this.rankingMarks.map((r) =>
                            r.isTarget ? "#7c192f" : "#ccc"
                        ),
                        data: this.rankingMarks.map((r) => r.value),
                    },
                ],
            };
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
    background: #fdf6ef;
    padding: 0;
    border-radius: 10px;
    box-shadow: none;
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
    margin-bottom: 40px;
}

.stat-card {
    background-color: #f5efe9;
    border-radius: 20px;
    width: 48%;
    padding: 4px 8px;
    text-align: center;
}

.stat-card h3 {
    font-size: 28px;
    color: #770f20;
}

.stat-card p {
    font-size: 28px;
    font-weight: bold;
    color: #770f20;
}

/* New sections */
.new-sections {
    display: flex;
    justify-content: space-between;
    margin-top: 40px;
}

.section {
    background-color: #f5efe9;
    border-radius: 20px;
    width: 48%;
    padding: 20px;
    text-align: center;
}

.section h2 {
    font-size: 24px;
    color: #770f20;
    margin-bottom: 20px;
}

.compare-summary-card {
    background: #f5efe9;
    border-radius: 12px;
    padding: 32px 32px 18px 32px;
    margin-bottom: 32px;
    margin-top: 48px;
    text-align: left;
}
.compare-summary-card h2 {
    color: #770f20;
    font-size: 2rem;
    margin-bottom: 10px;
    font-weight: bold;
}
.compare-summary-card p {
    font-size: 1.2rem;
    color: #770f20;
    margin: 0;
}
.student-position-section {
    background: transparent;
    padding: 0 16px 32px 16px;
}
.student-position-section h2 {
    color: #770f20;
    font-size: 1.5rem;
    margin-bottom: 18px;
    font-weight: bold;
}
</style>
