<script setup>
import { computed } from 'vue'
import { Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, Title, Tooltip, Legend, ArcElement } from 'chart.js'
import Card from '@/Components/ui/Card.vue'
import CardHeader from '@/Components/ui/CardHeader.vue'
import CardTitle from '@/Components/ui/CardTitle.vue'
import CardContent from '@/Components/ui/CardContent.vue'

ChartJS.register(Title, Tooltip, Legend, ArcElement)

const props = defineProps({
  deputies: {
    type: Array,
    required: true
  }
})

const chartData = computed(() => {
  const avgLoyalty = props.deputies.reduce((sum, d) => sum + d.loyalty_rate, 0) / props.deputies.length
  const avgMajority = props.deputies.reduce((sum, d) => sum + d.majority_proximity, 0) / props.deputies.length

  return {
    labels: ['Loyauté Groupe', 'Proximité Majorité', 'Indépendance'],
    datasets: [
      {
        data: [
          avgLoyalty.toFixed(1),
          avgMajority.toFixed(1),
          (100 - avgLoyalty).toFixed(1)
        ],
        backgroundColor: [
          'rgba(59, 130, 246, 0.8)',
          'rgba(16, 185, 129, 0.8)',
          'rgba(249, 115, 22, 0.8)'
        ],
        borderColor: [
          'rgba(59, 130, 246, 1)',
          'rgba(16, 185, 129, 1)',
          'rgba(249, 115, 22, 1)'
        ],
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
      position: 'bottom'
    },
    tooltip: {
      callbacks: {
        label: function(context) {
          return `${context.label}: ${context.parsed}%`
        }
      }
    }
  }
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Répartition Loyauté & Proximité</CardTitle>
    </CardHeader>
    <CardContent>
      <div class="h-[400px] flex items-center justify-center">
        <Doughnut :data="chartData" :options="chartOptions" />
      </div>
    </CardContent>
  </Card>
</template>
