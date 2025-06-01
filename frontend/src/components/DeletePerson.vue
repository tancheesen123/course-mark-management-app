<template>
  <div class="delete-section">
    <h3>Delete Person</h3>
    
    <!-- Display list of persons -->
    <div class="person-list">
      <h4>Current Persons:</h4>
      <div v-if="persons.length === 0" class="no-persons">
        No persons in the list
      </div>
      <div v-else class="person-items">
        <div v-for="(person, index) in persons" :key="index" class="person-item">
          <span class="index">[{{ index }}]</span>
          <span class="name">{{ person.name }}</span>
          <span class="details">(BMI: {{ person.bmi }})</span>
        </div>
      </div>
    </div>

    <!-- Delete form -->
    <div class="delete-form">
      <input
        type="number"
        v-model.number="deleteIndex"
        placeholder="Enter index to delete"
        class="delete-input"
      />
      <div v-if="feedback" class="feedback" :class="{ 'error': isError }">
        <em>{{ feedback }}</em>
      </div>
      <button @click="deletePerson" class="delete-button" :disabled="!persons.length">
        Delete Person
      </button>
    </div>
  </div>
</template>

<script>
export default {
  name: "DeletePerson",
  props: {
    persons: {
      type: Array,
      required: true
    }
  },
  data() {
    return {
      deleteIndex: null,
      feedback: "Enter index to delete",
      isError: false
    };
  },
  methods: {
    deletePerson() {
      if (this.deleteIndex === null) {
        this.feedback = "Please enter an index number";
        return;
      }

      if (this.deleteIndex < 0 || this.deleteIndex >= this.persons.length) {
        this.feedback = `Invalid index. Please enter a number between 0 and ${this.persons.length - 1}`;
        return;
      }

      const personToDelete = this.persons[this.deleteIndex];
      const confirmed = confirm(
        `Are you sure you want to delete ${personToDelete.name} at index ${this.deleteIndex}?`
      );
      
      if (confirmed) {
        this.$emit('delete-person', this.deleteIndex);
        this.feedback = `Successfully deleted ${personToDelete.name}`;
        this.deleteIndex = null;
      }
    }
  }
};
</script>

<style scoped>
.delete-section {
  margin: 20px 0;
  padding: 20px;
  background: #f9f9f9;
  border-radius: 8px;
}

h3 {
  border-bottom: 2px solid #42b983;
  padding-bottom: 10px;
  margin-bottom: 20px;
}

.person-list {
  margin-bottom: 20px;
  padding: 15px;
  background: white;
  border-radius: 6px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.person-items {
  max-height: 200px;
  overflow-y: auto;
}

.person-item {
  padding: 8px 12px;
  margin: 4px 0;
  background: #f0f9f5;
  border-radius: 4px;
  display: flex;
  gap: 10px;
  align-items: center;
}

.person-item .index {
  color: #666;
  font-weight: bold;
}

.person-item .name {
  font-weight: 500;
}

.person-item .details {
  color: #666;
  font-size: 0.9em;
}

.no-persons {
  color: #666;
  font-style: italic;
  text-align: center;
  padding: 10px;
}

.delete-form {
  margin-top: 20px;
}

.delete-input {
  display: block;
  width: 100%;
  padding: 10px;
  margin-bottom: 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 16px;
  box-sizing: border-box;
}

.delete-button {
  background-color: #42b983;
  color: white;
  padding: 10px 15px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  transition: background-color 0.3s;
  width: 100%;
}

.delete-button:hover:not(:disabled) {
  background-color: #3aa876;
}

.delete-button:disabled {
  background-color: #cccccc;
  cursor: not-allowed;
}

.feedback {
  margin-top: 10px;
  padding: 8px;
  border-radius: 4px;
  font-style: italic;
}

.feedback.error {
  color: #d32f2f;
  background-color: #ffebee;
}

.feedback:not(.error) {
  color: #2e7d32;
  background-color: #e8f5e9;
}
</style>
  
