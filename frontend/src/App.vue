<template>
  <div id="app">
    <!-- <nav class="main-nav">
      <router-link to="/">Home</router-link> |
      <router-link to="/testCallApi">Test Call API</router-link> |
      <router-link to="/addperson">Add Person</router-link> |
      <router-link to="/viewall">View All</router-link> |
      <router-link to="/viewone">View One</router-link> |
      <router-link to="/updateprofile">Edit</router-link> |
      <router-link to="/delete">Delete</router-link>
    </nav> -->

    <router-view :personList="personList" :persons="personList" @save-person="savePerson" @person-added="addPerson"
      @edit-person="editPerson" @delete-person="deletePerson">
    </router-view>
    <!-- <LoginPage> </LoginPage> -->
    <!-- <add-person @save-person="savePerson"></add-person>
  <list-person :personList="personList"></list-person>
  <one-person :personList="personList"></one-person>
  <edit-person :personList="personList" @edit-person="editPerson"></edit-person>
  <delete-person :personList="personList" @delete-person="deletePerson"></delete-person>-->
    <!-- <bmi-stats :personList="personList"></bmi-stats> -->
    <!-- <h3>Footer</h3> -->
  </div>
</template>

<script>
// import BmiStats from './components/BmiStats.vue';
// import LoginPage from './components/LoginPage.vue';
// import StateGraph from './components/StateGraph.vue';
// import AddPerson from "./components/AddPerson.vue";
// import DeletePerson from "./components/DeletePerson.vue";
// import EditPerson from "./components/EditPerson.vue";
// import ListPerson from "./components/ListPerson.vue";
// import OnePerson from "./components/OnePerson.vue";
// import StateGraph from "./components/StateGraph.vue";
// import HelloWorld from './components/HelloWorld.vue'

export default {
    name: "App",
    components: {
        // StateGraph,
        // BmiStats,
        // LoginPage
        // // HelloWorld,
        // EditPerson,
        // AddPerson,
        // ListPerson,
        // DeletePerson,
        // StateGraph,
        // OnePerson,
    },
    data() {
        return {
            // name: "Cheesen",
            // gender: "",
            // yob: 2002,
            // weight: 3,
            // height: 3,
            // bmi: 190,
            // category: "",
            personList: [],
            // bmiList: []
        };
    },
    created() {
        this.checkSession(); // ✅ Run session check when app is created
    },
    methods: {
        checkSession() {
            // Check if session exists
            const userString = localStorage.getItem("user");
            const user = JSON.parse(userString); // Convert string to object
            console.log("User session object:", user);
            // console.log("User session:", user.role);
            if (!userString) {
                this.$router.push("/");
            } else {
                if (user.role == 1) {
                    this.$router.push("/lecturerMenu/dashboard");
                } else if (user.role == 2) {
                    this.$router.push("/studentMenu/dashboard");
                } else if (user.role == 3) {
                    this.$router.push("/advisorMenu/dashboard");
                } else {
                    this.$router.push("/");
                }
            }
        },
    },
};
</script>
<style>
html, body {
  margin: 0;
  padding: 0;
  height: 100%;
}
#app {
  height: 100%;
}
</style>