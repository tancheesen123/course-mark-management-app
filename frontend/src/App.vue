<template>
  <div id="app">
    <div class="container">
      <h1>Person BMI Web App</h1>

  <nav class="main-nav">
    <router-link to="/">Home</router-link> |
    <router-link to="/testCallApi">Test Call API</router-link> |
    <router-link to="/addperson">Add Person</router-link> |
    <router-link to="/viewall">View All</router-link> |
    <router-link to="/viewone">View One</router-link> |
    <router-link to="/updateprofile">Edit</router-link> |
    <router-link to="/delete">Delete</router-link>
    <!-- <a href="/addperson">Add Person</a> | <a href="/viewall">View All</a> |
    <a href="/viewone">View One</a> | <a href="/updateprofile">Edit</a> |
    <a href="/delete">Delete</a> -->
  </nav>
  <router-view 
  :personList="personList" 
  :persons="personList"
  @save-person="savePerson" 
  @person-added="addPerson"
  @edit-person="editPerson" 
  @delete-person="deletePerson"
  >
</router-view>
  <!-- <add-person @save-person="savePerson"></add-person>
  <list-person :personList="personList"></list-person>
  <one-person :personList="personList"></one-person>
  <edit-person :personList="personList" @edit-person="editPerson"></edit-person>
  <delete-person :personList="personList" @delete-person="deletePerson"></delete-person>-->
  <bmi-stats :personList="personList"></bmi-stats>
  <h3>Footer</h3>
  </div>
  </div>
</template>


<script>
import BmiStats from './components/BmiStats.vue';
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
    BmiStats
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
      personList: [
      ],
      // bmiList: []
    };
  },
  methods: {
    viewPerson(p) {
      this.personList.push(p);
      console.log(this.personList);
    },
    // savePerson(p) {
    //   this.personList.push(p);
    //   console.log(this.personList);
    // },
    addPerson(p) {
      console.log("Add Person"+p);
      this.personList.push(p);
      console.log(this.personList);
    },
    editPerson(updatedPerson, index) {
      console.log("this is index"+index);
      console.log("Edit Person", updatedPerson);
      if (index >= 0 && index < this.personList.length) {
        this.personList[index] = { ...updatedPerson };
        console.log("Person updated at index", index, this.personList[index]);
        alert("Update successful");
      } else {
        console.warn("Invalid index for editing");
        alert("failed to update");
      }
    },
    deletePerson(index) {
      if (index >= 0 && index < this.personList.length) {
        this.personList.splice(index, 1);
        console.log("Person deleted at index", index);
        console.log("Updated personList:", this.personList);
      } else {
        console.warn("Invalid index for deletion");
        alert("Failed to delete person");
      }
    }
  },
};
</script>
<style>
body {
  font-family: "Segoe UI", sans-serif;
  background-color: #f4f7f9;
  padding: 20px;
}
.container {
  max-width: 700px;
  margin: auto;
  background: white;
  padding: 30px;
  border-radius: 10px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}
h2,
h3 {
  border-bottom: 2px solid #42b983;
  padding-bottom: 10px;
}
form {
  margin-bottom: 20px;
}
input {
  display: block;
  width: 100%;
  padding: 10px;
  margin-bottom: 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 16px;
  box-sizing: border-box;
}
button {
  background-color: #42b983;
  color: white;
  padding: 10px 15px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}
ul {
  padding: 0;
  list-style: none;
}
li {
  background: #f0f9f5;
  padding: 15px;
  margin-bottom: 10px;
  border-left: 5px solid #42b983;
  border-radius: 5px;
}
.bar-container {
  background-color: #e0e0e0;
  border-radius: 5px;
  margin: 4px 0 15px 0;
}
.bar {
  height: 20px;
  color: white;
  text-align: center;
  line-height: 20px;
  border-radius: 5px;
}
.bar.underweight {
  background-color: #2196f3;
}
.bar.normal {
  background-color: #4caf50;
}
.bar.overweight {
  background-color: #ff9800;
}
.bar.obese {
  background-color: #f44336;
}
.main-nav {
  margin-bottom: 20px;
  text-align: center;
}
nav a {
  margin: 0 10px;
  text-decoration: none;
  color: #42b983;
  font-weight: bold;
  padding: 6px 12px;
  border-radius: 5px;
  transition: 0.3s;
}
nav a:hover {
  background-color: #42b983;
  color: white;
}
.person-card {
  display: flex;
  align-items: center;
  gap: 15px;
}
.person-photo {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  object-fit: cover;
}
.detail-box {
  margin-top: 10px;
  padding: 15px;
  background: #f0f9f5;
  border-radius: 8px;
}
.edit-form-section {
  margin-top: 15px;
  padding: 15px;
  border-radius: 8px;
  background-color: #f9f9f9;
  /* display:  */
}
.feedback {
  margin-top: 10px;
  color: #d32f2f;
  font-style: italic;
}
.chart-canvas {
  margin-top: 20px;
}
</style>

