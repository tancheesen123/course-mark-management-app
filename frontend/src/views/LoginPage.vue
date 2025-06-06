<template>
  <div class="login-container">
    <div class="login-box">
      <div class="text-center mb-4">
        <img src="@/assets/utm-logo.png" alt="UTM Logo" class="logo" />
        <p class="subtitle">UNIVERSITI TEKNOLOGI MALAYSIA</p>
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
        <a class="forgot-password">Forgot password?</a>
      </div>
      <p class="error-message" id="errorMessage" style="text-align: center;"></p>
      <Button label="Sign In" class="login-button" @click="loginUser()" />
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
      errorMessage: "",
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
          this.$router.push("/lecturerMenu/dashboard");
        } else if (data.user.role == 2) {
          this.$router.push("/studentMenu/dashboard");
        } else if (data.user.role == 3) {
          this.$router.push("/advisorMenu/dashboard");
        } else {
          this.$router.push("/");
        }
      } else {
        //add error message
        document.getElementById("errorMessage").innerText = data.message || "Invalid credentials";
        this.$toast.add({
          severity: "error",
          summary: "Login Failed",
          detail: data.message || "Invalid credentials",
          life: 3000,
        });
      }
    },
  },
};
</script>

<style scoped>
.login-container {
  height: 100vh;
  width: 100vw;
  display: flex;
  justify-content: center;
  align-items: center;
  background-color: #7a0f27; /* UTM red */
  margin: 0;
  padding: 0;
}

.login-box {
  background: white;
  padding: 2.5rem;
  border-radius: 1rem;
  width: 100%;
  max-width: 400px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.logo {
  width: 250px;
  margin-bottom: 0.75rem;
}

.title {
  font-size: 1.75rem;
  color: #7a0f27;
  margin: 0;
}

.subtitle {
  color: #7a0f27;
  font-weight: bold;
  font-size: 0.95rem;
  margin-bottom: 1.5rem;
}

.error-message {
  color: black;
  font-weight: bold;
  font-size: 0.95rem;
  margin-bottom: 1.5rem;
}

.forgot-password {
  color: #7a0f27;
  font-weight: bold;
  font-size: 0.85rem;
  cursor: pointer;
  text-decoration: none;
}

.forgot-password:hover {
  text-decoration: underline;
}

.login-button {
  background-color: #7a0f27;
  border: none;
  color: white;
  padding: 0.75rem;
  width: 100%;
  border-radius: 0.5rem;
  font-weight: bold;
  font-size: 1rem;
  cursor: pointer;
}

.login-button:hover {
  background-color: #600c20;
}

</style>
