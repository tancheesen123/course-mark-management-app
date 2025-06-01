<template>
  <div>
    <h3>Edit Person</h3>
    <input
      type="number"
      v-model.number="index"
      placeholder="Enter index to edit"
    />
    <div v-if="selectedPerson" class="edit-form-section">
      <input type="text" v-model="editName" placeholder="Name" />
      <input type="number" v-model="editWeight" placeholder="Weight (kg)" />
      <input type="number" v-model="editHeight" placeholder="Height (cm)" />
      <input type="text" v-model="editPhotoUrl" placeholder="Image URL" />
      <button @click="editPerson()">Update</button>
    </div>
    <div v-else class="feedback">
      <em>Enter a valid index to load person data</em>
    </div>
  </div>
</template>

<script>
export default {
  name: "EditPerson",
  props: {
    personList: {
      type: Array,
      required: true,
    },
  },
  emits: ["edit-person"],
  data() {
    return {
      index: null,
      editName: "",
      editWeight: 0,
      editHeight: 0,
      editPhotoUrl: "",
      yob:0,
      age:0,
    };
  },
  computed: {
    selectedPerson() {
      return this.index !== null &&
        this.index >= 0 &&
        this.index < this.personList.length
        ? this.personList[this.index]
        : null;
    },
  },
  watch: {
    selectedPerson(newPerson) {
      if (newPerson) {
        this.editName = newPerson.name;
        this.editWeight = newPerson.weight;
        this.editHeight = newPerson.height;
        this.editPhotoUrl = newPerson.photoUrl;
        this.yob = newPerson.yob;
        this.age = new Date().getFullYear() - this.yob;
      } else {
        this.editName = "";
        this.editWeight = null;
        this.editHeight = null;
        this.editPhotoUrl = "";
        this.yob = 0;
        this.age = 0;
      }
    },
  },
  methods: {
    editPerson() {
      const bmi = (this.editWeight / ((this.editHeight / 100) ** 2)).toFixed(2);
      const category = this.getCategory(parseFloat(bmi));
      const updatedPerson = {
        name: this.editName,
        yob: this.yob,
        age: this.age,
        weight: this.editWeight,
        height: this.editHeight,
        bmi: bmi,
        category: category,
        photoUrl: this.editPhotoUrl,
      };
      console.log("Updated Person", updatedPerson);
      this.$emit("edit-person", updatedPerson, this.index);
      this.resetForm();
    },
    getCategory(bmi) {
      if (bmi < 18.5) return 'underweight';
      if (bmi < 24.9) return 'normal';
      if (bmi < 29.9) return 'overweight';
      return 'obese';
    },
    resetForm() {
      this.editName = "";
      this.editWeight = null;
      this.editHeight = null;
      this.editPhotoUrl = "";
    },
  },
};
</script>