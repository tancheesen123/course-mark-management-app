<template>
  <div>
    <h3>Test Call API</h3>

    <!-- loop personList  -->
    <div v-for="(person, index) in personList" :key="index" class="person-item">
      <span class="index">{{ index }}==></span>
      <span class="name">Name: {{ person.name }}</span>
      <span class="email"> Email: {{ person.email }}</span>
      <span class="matric">Matric: {{ person.matricNum }}</span>
      </div>
    <!-- <div v-if="selectedPerson" class="detail-box">
      <div class="person-detail">
        <img v-if="selectedPerson.photoUrl" :src="selectedPerson.photoUrl" class="person-photo" />
        <div class="person-info">
          <p><strong>Name: </strong>{{ selectedPerson.name }}</p>
          <p><strong>Weight: </strong>{{ selectedPerson.weight }}</p>
          <p><strong>Height: </strong>{{ selectedPerson.height }} cm</p>
          <p><strong>BMI: </strong>{{ selectedPerson.bmi }}</p>
        </div>
      </div>
    </div> -->

  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'TestCallApi',
  data() {
    return {
      inputIndex: null,
      personList: [],
    };
  },
  mounted() {
    axios.get('http://localhost:8085/students')
      .then((response) => {
        console.log('Fetched student list:', response.data);
        this.personList = response.data;
      })
      .catch((error) => {
        console.error('Failed to fetch student list:', error);
      });
  },
};
</script>

<style scoped>
.person-detail {
  display: flex;
  gap: 16px;
}

.person-photo {
  width: 100px;
  height: 100px;
  object-fit: cover;
}

.detail-box {
  margin-top: 20px;
  padding: 10px;
  border: 1px solid #ddd;
}
</style>