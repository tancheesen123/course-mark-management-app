<template>
    <div class="flex justify-content-center align-items-center min-h-screen surface-100">
        <div class="surface-card shadow-2 border-round-xl p-5" style="width: 100%; max-width: 400px">
            <div class="text-center mb-4">
                <i class="pi pi-graduation" style="font-size: 2.5rem; color: #10b981"></i>
                <h2 class="mt-2 mb-1 text-900 font-bold">Welcome to PrimeLand!</h2>
                <p class="text-600 text-sm">Sign in to continue</p>
            </div>

            <div class="mb-3">
                <label for="email" class="block text-900 font-medium mb-2">Email</label>
                <InputText id="email" v-model="email" type="email" placeholder="Email address" class="w-full" />
            </div>

            <div class="mb-3">
                <label for="password" class="block text-900 font-medium mb-2">Password</label>
                <Password id="password" v-model="password" placeholder="Password" :feedback="false" class="w-full" toggleMask />
            </div>

            <div class="flex align-items-center justify-content-between mb-4">
                <div>
                    <Checkbox id="remember" v-model="rememberMe" :binary="true" />
                    <label for="remember" class="ml-2 text-sm text-600">Remember me</label>
                </div>
                <a class="text-sm text-green-500 cursor-pointer hover:underline">Forgot password?</a>
            </div>

            <Button label="Sign In" class="w-full" @click="loginUser()" />
        </div>
    </div>
</template>

<script>
import InputText from "primevue/inputtext";
import Password from "primevue/password";
import Checkbox from "primevue/checkbox";
import Button from "primevue/button";

export default {
    name: "LoginPage",
    components: {
        InputText,
        Password,
        Checkbox,
        Button,
    },
    data() {
        return {
            email: "",
            password: "",
            rememberMe: false,
        };
    },
    methods: {
        async loginUser() {
            const response = await fetch("http://localhost:8085/api/login", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    email: this.email,
                    password: this.password,
                }),
            });

            const data = await response.json();
            console.log("response status:", response);

            if (response.status === 200) {
                console.log("Login successful:", data);
                localStorage.setItem("authToken", data.token);
                localStorage.setItem("user", JSON.stringify(data.user));
                console.log("this is user role:", data.user.role);
                if (data.user.role == 1) {
                    this.$router.push("/lecturerDashboard");
                } else if (data.user.role == 2) {
                    this.$router.push("/dashboard");
                } else if (data.user.role == 3) {
                    this.$router.push("/academicAdvisorDashboard");
                } else {
                    this.$router.push("/");
                }
            } else {
                this.$toast.add({ severity: "error", summary: "Login Failed", detail: data.message || "Invalid credentials", life: 3000 });
            }
        },
    },
};
</script>

<style scoped>
.surface-100 {
    background-color: #f9fbfd;
}
</style>
