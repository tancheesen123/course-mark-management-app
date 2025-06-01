<template>
  <div>
    <h2>BMI Statistics</h2>

    <!-- Text Stats -->
    <div v-if="personList.length === 0">
      <p>No data yet.</p>
    </div>
    <div v-else>
      <div v-for="(count, cat) in categories" :key="cat">
        <strong>{{ formatLabel(cat) }}</strong>: {{ count }} ({{ categoryPercent(cat) }}%)
        <div class="bar-container">
          <div
            class="bar"
            :class="cat.toLowerCase()"
            :style="{ width: categoryPercent(cat) + '%' }"
          >
            {{ categoryPercent(cat) }}%
          </div>
        </div>
      </div>
    </div>

    <!-- Chart -->
    <div style="max-width: 400px; margin-top: 30px;">
      <Pie :data="chartData" :options="chartOptions" />
    </div>
  </div>
</template>

<script>
import { Pie } from 'vue-chartjs'
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  ArcElement,
} from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, ArcElement)

export default {
  name: 'BmiStats',
  components: {
    Pie
  },
  props: {
    personList: {
      type: Array,
      required: true
    }
  },
  computed: {
    categories() {
      const result = {
        underweight: 0,
        normal: 0,
        overweight: 0,
        obese: 0
      }
      this.personList.forEach(p => {
        const bmi = parseFloat(p.bmi)
        if (bmi < 18.5) result.underweight++
        else if (bmi < 24.9) result.normal++
        else if (bmi < 29.9) result.overweight++
        else result.obese++
      })
      return result
    },
    chartData() {
      return {
        labels: ['Underweight', 'Normal', 'Overweight', 'Obese'],
        datasets: [
          {
            data: [
              this.categories.underweight,
              this.categories.normal,
              this.categories.overweight,
              this.categories.obese
            ],
            backgroundColor: ['#2196f3', '#4caf50', '#ff9800', '#f44336']
          }
        ]
      }
    },
    chartOptions() {
      return {
        responsive: true,
        plugins: {
          legend: { position: 'bottom' },
          title: {
            display: true,
            text: 'BMI Category Distribution'
          }
        }
      }
    }
  },
  methods: {
    formatLabel(cat) {
      return cat.charAt(0).toUpperCase() + cat.slice(1)
    },
    categoryPercent(cat) {
      const total = this.personList.length
      const count = this.categories[cat]
      return total > 0 ? ((count / total) * 100).toFixed(1) : 0
    }
  }
}
</script>
