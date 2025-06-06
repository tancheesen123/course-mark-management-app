<template>
  <div>
    <h3>Add Person</h3>
    <form id="bmiForm">
      Fullname :
      <input type="text" v-model="name" placeholder="Name" required /> Year Born
      : <input type="text" v-model="yob" placeholder="yyyy" required /> Weight :
      <input
        type="number"
        v-model="weight"
        placeholder="Weight (kg)"
        required
      />
      Height :
      <input
        type="number"
        v-model="height"
        placeholder="Height (cm)"
        required
      />
      Photo URL :
      <input type="text" v-model="photoUrl" placeholder="Image URL" />
      <button type="submit" v-on:click.prevent="addPerson()">Add Person</button>
    </form>
  </div>
</template>
<script>
// import ContactCard from './ContactCard.vue';

export default {
  
  name: "AddPerson",
  components: {
    // HelloWorld
  },
  data() {
    return {
      name: "Hassan",
      yob: 0,
      age: new Date().getFullYear() - 1995,
      weight: 0,
      height: 0,
      bmi: 0,
      category: "t.b.c",
      photoUrl: "https://randomuser.me/api/portraits/men/44.jpg",
      
    };
  },
  // emits: ["save-person"],
  methods: {
    $emits: ["person-added"],
    addPerson() {
      const age = new Date().getFullYear() - this.yob;
      const bmi = (this.weight / (this.height / 100) ** 2).toFixed(2);
      const category = this.getCategory(parseFloat(bmi));

      const person = {
        name: this.name,
        yob: this.yob,
        age: age,
        weight: this.weight,
        height: this.height,
        bmi: bmi,
        category: category,
        photoUrl: this.photoUrl,
      };

      this.$emit("person-added", person);

      fetch("http://localhost:8085/person", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(person),
      })
        .then((res) => res.json())
        .then(() => alert("Person added successfully."))
        .catch((err) => console.log("Error adding person:", err));

      
      this.resetForm();
    },
    getCategory(bmi) {
      if (bmi < 18.5) return "underweight";
      if (bmi < 24.9) return "normal";
      if (bmi < 29.9) return "overweight";
      return "obese";
    },
    resetForm() {
      this.name = "";
      this.yob = null;
      this.weight = null;
      this.height = null;
      this.photoUrl = "";
    },
  },
};
</script>
