<template>
    <div>
        <h3>View One Person</h3>
        <input v-model.number="inputIndex" type="number" placeholder="Enter index"/>
        
        <div v-if="selectedPerson" class="detail-box">
            <div class="person-detail">
            <img v-if="selectedPerson.photoUrl" :src="selectedPerson.photoUrl" class="person-photo" />
            <div class="person-info">
                <p><strong>Name: </strong>{{ selectedPerson.name }}</p>
                <p><strong>Weight: </strong>{{ selectedPerson.weight }}</p>
                <p><strong>Height: </strong> {{ selectedPerson.height }}cm</p>
                <p><strong>BMI: </strong>{{ selectedPerson.bmi }}</p>
            </div>
          </div>  
        </div>

        <div v-else class="detail-box">
            <em>No person at that index</em>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ViewPerson',
    props: {
        personList: {
            type: Array,
            required: true
        }
    },
    data() {
        return {
            inputIndex: null
        }
    },
    computed: {
        // Get the person at the entered index, or return null if invalid
        selectedPerson() {
            return this.inputIndex !== null &&
            this.inputIndex >= 0 &&
            this.inputIndex < this.personList.length
            ? this.personList[this.inputIndex]: null;
        }
    }
}
</script>
