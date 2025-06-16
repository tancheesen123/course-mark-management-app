<template>
  <Bar :data="chartData" :options="chartOptions" />
</template>

<script>
import { Bar } from 'vue-chartjs'
import {
  Chart as ChartJS,
  BarElement,
  CategoryScale,
  LinearScale,
  Tooltip,
  Legend
} from 'chart.js'

ChartJS.register(BarElement, CategoryScale, LinearScale, Tooltip, Legend)

export default {
  name: "RankingChart",
  components: { Bar },
  props: {
    data: {
      type: Object,
      required: true
    },
    options: {
      type: Object,
      required: false,
      default: () => ({
        responsive: true,
        plugins: {
          legend: { display: false }
        }
      })
    }
  },
  computed: {
    chartData() {
      return this.data
    },
    chartOptions() {
      return this.options
    }
  }
}
</script>
