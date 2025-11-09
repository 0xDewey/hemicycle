<script setup>
import { computed } from 'vue'
import { Bar } from 'vue-chartjs'
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js'
import Card from '@/Components/ui/Card.vue'
import CardHeader from '@/Components/ui/CardHeader.vue'
import CardTitle from '@/Components/ui/CardTitle.vue'
import CardContent from '@/Components/ui/CardContent.vue'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

const props = defineProps({
  deputies: {
    type: Array,
    required: true
  }
})

const chartData = computed(() => {
  // Trier par taux de participation décroissant
  const sortedDeputies = [...props.deputies].sort((a, b) => b.participation_rate - a.participation_rate)

  return {
    labels: sortedDeputies.map(d => `${d.firstname} ${d.name}`),
    datasets: [
      {
        label: 'Taux de Participation (%)',
        data: sortedDeputies.map(d => d.participation_rate),
        backgroundColor: 'rgba(59, 130, 246, 0.8)',
        borderColor: 'rgba(59, 130, 246, 1)',
        borderWidth: 1
      }
    ]
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: true,
      position: 'top'
    },
    title: {
      display: false
    },
    tooltip: {
      callbacks: {
        label: function(context) {
          return `${context.dataset.label}: ${context.parsed.y.toFixed(1)}%`
        }
      }
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      max: 100,
      ticks: {
        callback: function(value) {
          return value + '%'
        }
      }
    },
    x: {
      ticks: {
        autoSkip: false,
        maxRotation: 45,
        minRotation: 45
      }
    }
  }
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Taux de Participation aux Votes</CardTitle>
    </CardHeader>
    <CardContent>
      <div class="h-[400px]">
        <Bar :data="chartData" :options="chartOptions" />
      </div>
    </CardContent>
  </Card>
</template>
