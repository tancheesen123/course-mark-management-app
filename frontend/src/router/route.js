import {createRouter, createWebHistory} from 'vue-router'


import Addperson from '../components/AddPerson.vue'
import ListPerson from '../components/ListPerson.vue'
import ViewPerson from '../components/ViewPerson.vue'
import EditPerson from '../components/EditPerson.vue'
import DeletePerson from '../components/DeletePerson.vue'
import TestCallApi from '../components/TestCallApi.vue'

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {path: '/', component:ListPerson},
        {path: '/testCallApi', component:TestCallApi},
        {path: '/addperson', component:Addperson},
        {path:'/viewall', component:ListPerson},
        {path:'/viewone', component:ViewPerson},
        {path:'/updateprofile', component:EditPerson},
        {path:'/delete', component:DeletePerson}
    ]
});


export default router


