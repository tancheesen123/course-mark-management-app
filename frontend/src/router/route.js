import { createRouter, createWebHistory } from "vue-router";

import Addperson from "../components/AddPerson.vue";
import ListPerson from "../components/ListPerson.vue";
import ViewPerson from "../components/ViewPerson.vue";
import EditPerson from "../components/EditPerson.vue";
import DeletePerson from "../components/DeletePerson.vue";
import TestCallApi from "../components/TestCallApi.vue";
import LoginPage from "@/views/LoginPage.vue";

import LecturerMenu from "@/views/lecturer/LecturerMenu.vue";
import StudentRecords from "@/views/lecturer/StudentRecords.vue";
import StudentRecordAssessment from "@/views/lecturer/StudentRecordAssessment.vue";
import LecturerAssessment from "@/views/lecturer/LecturerAssessment.vue";
import TotalCalculation from "@/views/lecturer/TotalCalculation.vue";
import LecturerDashboard from "@/views/lecturer/LecturerDashboard.vue";

import AcademicAdvisorMenu from "@/views/advisor/AcademicAdvisorMenu.vue";
import AcademicAdvisorDashboard from "@/views/advisor/AcademicAdvisorDashboard.vue";
import CourseList from "@/views/advisor/CourseList.vue";
import AdviseeListView from "@/views/advisor/AdviseeList.vue";
import AdviseeDetailsView from "@/views/advisor/AdviseeDetailsView.vue";
import ReportList from "@/views/advisor/ReportList.vue";

import StudentMenu from "@/views/student/StudentMenu.vue";
import StudentDashboard from "@/views/student/StudentDashboard.vue";
import StudentRecordsMark from "@/views/lecturer/StudentRecordsMark.vue";
import AddStudentRecord from "@/views/lecturer/AddStudentRecord.vue";

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: "/", component: LoginPage },
        { path: "/login", component: LoginPage },
        { path: "/dashboard", component: StudentDashboard },
        // {path: '/lecturerDashboard', component:LecturerDashboard},
        // {path: '/academicAdvisorDashboard', component:AcademicAdvisorDashboard},
        { path: "/testCallApi", component: TestCallApi },
        // { path: "/addperson", component: Addperson },
        { path: "/viewall", component: ListPerson },
        { path: "/viewone", component: ViewPerson },
        { path: "/updateprofile", component: EditPerson },
        { path: "/delete", component: DeletePerson },

        {
            path: "/LecturerMenu",
            component: LecturerMenu,
            children: [
                // { path: '', redirect: '/lecturerDashboard' },
                { path: "dashboard", component: LecturerDashboard },
                { path: "student-record", component: StudentRecords },
                { path: "student-record/assessment", component: StudentRecordAssessment, props: route => ({ courseId: route.query.course_id }) },
                { path: "student-record/assessment/mark", component: StudentRecordsMark, props: route => ({ courseId: route.query.course_id }) },
                {
                    path: "student-record/assessment/add",
                    name: "AddStudentRecord",
                    component: AddStudentRecord,
                    props: (route) => ({ course_id: route.query.course_id, name: route.query.name })
                },
                { path: "assessment", component: LecturerAssessment },
                { path: "total-calculation", component: TotalCalculation },
            ],
        },
        {
            path: "/AdvisorMenu",
            component: AcademicAdvisorMenu,
            children: [
                { path: "dashboard", component: AcademicAdvisorDashboard },
                { path: "courses", component: CourseList },
                {
                    path: 'advisor/courses/:courseId/students',
                    name: 'AdviseeList',
                    component: AdviseeListView,
                    props: true
                },
                {
                    path: 'advisor/courses/:courseId/students/:studentId/details',
                    name: 'AdviseeDetailsView',
                    component: AdviseeDetailsView,
                    props: true
                },
                { path: 'reports', component: ReportList },
            ],
        },
        {
            path: "/StudentMenu",
            component: StudentMenu,
            children: [
                // { path: "", redirect: "/academicAdvisorDashboard/dashboard" },
                { path: "dashboard", component: StudentDashboard },
                { path: "addperson", component: Addperson },
            ],
        },
    ],
});

router.beforeEach((to, from, next) => {
    const userSession = localStorage.getItem("userSession") || localStorage.getItem("user"); // support either key
    if (to.path === "/dashboard" && !userSession) {
        next("/");
    } else {
        next();
    }
});

export default router;
