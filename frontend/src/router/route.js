import {createRouter, createWebHistory} from 'vue-router'


import Addperson from '../components/AddPerson.vue'
import ListPerson from '../components/ListPerson.vue'
import ViewPerson from '../components/ViewPerson.vue'
import EditPerson from '../components/EditPerson.vue'
import DeletePerson from '../components/DeletePerson.vue'
import TestCallApi from '../components/TestCallApi.vue'
import LoginPage from '@/components/LoginPage.vue'
import StudentDashboard from '@/views/student/StudentDashboard.vue'
import LecturerDashboard from '@/views/lecturer/LecturerDashboard.vue'
import AcademicAdvisorDashboard from '@/views/advisor/AcademicAdvisorDashboard.vue'

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {path: '/', component:LoginPage},
        {path: '/login', component:LoginPage},
        {path: '/dashboard', component:StudentDashboard},
        {path: '/lecturerDashboard', component:LecturerDashboard},
        {path: '/academicAdvisorDashboard', component:AcademicAdvisorDashboard},
        {path: '/testCallApi', component:TestCallApi},
        {path: '/addperson', component:Addperson},
        {path:'/viewall', component:ListPerson},
        {path:'/viewone', component:ViewPerson},
        {path:'/updateprofile', component:EditPerson},
        {path:'/delete', component:DeletePerson}
    ]
});

router.beforeEach((to, from, next) => {
  const userSession = localStorage.getItem("userSession") || localStorage.getItem("user"); // support either key
  if (to.path === "/dashboard" && !userSession) {
    next("/");
  } else {
    next();
  }
});


export default router


