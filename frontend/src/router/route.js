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
import LecturerAssessment from "@/views/lecturer/LecturerAssessment.vue";
import FinalExam from "@/views/lecturer/FinalExam.vue";
import TotalCalculation from "@/views/lecturer/TotalCalculation.vue";
import LecturerDashboard from "@/views/lecturer/LecturerDashboard.vue";

import AcademicAdvisorMenu from "@/views/advisor/AcademicAdvisorMenu.vue";
import AcademicAdvisorDashboard from "@/views/advisor/AcademicAdvisorDashboard.vue";

import StudentMenu from "@/views/student/StudentMenu.vue";
import StudentDashboard from "@/views/student/StudentDashboard.vue";

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
                { path: "students", component: StudentRecords },
                { path: "assessment", component: LecturerAssessment },
                { path: "final-exam", component: FinalExam },
                { path: "total-calculation", component: TotalCalculation },
            ],
        },
        {
            path: "/AdvisorMenu",
            component: AcademicAdvisorMenu,
            children: [
                // { path: "", redirect: "/academicAdvisorDashboard/dashboard" },
                { path: "dashboard", component: AcademicAdvisorDashboard },
                { path: "addperson", component: Addperson },
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
