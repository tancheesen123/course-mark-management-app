<template>
  <div>
    <!-- <h3>Person List</h3>
    <ul id="personList"></ul>
    <div v-for="(person, index) in personList" :key="index">
      <h4>{{ index + 1 }}. {{ person.name }}</h4>
      <img :src="person.photoUrl" alt="Person Photo" />
      <p>Year of Birth: {{ person.yob }}</p>
      <p>Age: {{ person.age }}</p>
      <p>Weight: {{ person.weight }} kg</p>
      <p>Height: {{ person.height }} cm</p>
      <p>BMI: {{ person.bmi }}</p>
      <p>Category: {{ person.category }}</p>
      </div> -->

      <div>
        <h3>Person List</h3>
        <ul id="personList">
            <li v-for="(person, index) in personList" :key="index">
                <div class="person-card">
                    <img v-if="person.photoUrl" :src="person.photoUrl" class="person-photo"/>
                    <div>
                        <strong>[{{ index }}] {{ person.name }}</strong> (Age: {{ person.age }}) <br>
                        Weight: {{ person.weight }} kg, Height: {{ person.height }} cm <br>
                        BMI: {{ person.bmi }}
                    </div>
                </div>
            </li>
        </ul>
    </div>
  </div>
</template>
<script>
// import ContactCard from './ContactCard.vue';

export default {
  name: "ListPerson",
  props: ["personList"],
  components: {
    // HelloWorld
  },
  data() {
    return {
      name: "Hassan",
      yob: 1995,
      age: new Date().getFullYear() - 1995,
      weight: 68,
      height: 175,
      bmi: (68 / (175 / 100) ** 2).toFixed(2),
      category: "t.b.c",
      photoUrl: "https://randomuser.me/api/portraits/men/44.jpg",
      index: 0,
    };
  },
};
</script>
