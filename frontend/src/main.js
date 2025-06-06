import { createApp } from 'vue'
import router from './router/route'
import App from './App.vue'

import PrimeVue from 'primevue/config'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import ToastService from 'primevue/toastservice'
import Toast from 'primevue/toast'

import 'primevue/resources/themes/lara-light-blue/theme.css' // ✅ Updated theme
import 'primevue/resources/primevue.min.css'
import 'primeicons/primeicons.css'
import 'primeflex/primeflex.css'

createApp(App)
.use(router)
.use(PrimeVue)
.use(ToastService)

.component('InputText', InputText)
.component('Password', Password)
.component('Button', Button)
.component('Toast', Toast)

.mount('#app')
