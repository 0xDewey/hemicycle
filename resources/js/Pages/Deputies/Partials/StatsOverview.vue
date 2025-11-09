<script setup>
import Card from '@/Components/ui/Card.vue'
import CardHeader from '@/Components/ui/CardHeader.vue'
import CardTitle from '@/Components/ui/CardTitle.vue'
import CardContent from '@/Components/ui/CardContent.vue'

defineProps({
  stats: {
    type: Object,
    required: true
  }
})

const formatPercent = (value) => {
  return `${value.toFixed(1)}%`
}
</script>

<template>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    <Card>
      <CardHeader class="pb-2">
        <CardTitle class="text-sm font-medium text-muted-foreground">
          Nombre de Députés
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div class="text-3xl font-bold">{{ stats.total_deputies }}</div>
      </CardContent>
    </Card>

    <Card>
      <CardHeader class="pb-2">
        <CardTitle class="text-sm font-medium text-muted-foreground">
          Taux de Participation Moyen
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div class="text-3xl font-bold text-primary">
          {{ formatPercent(stats.avg_participation) }}
        </div>
      </CardContent>
    </Card>

    <Card>
      <CardHeader class="pb-2">
        <CardTitle class="text-sm font-medium text-muted-foreground">
          Loyauté Moyenne
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div class="text-3xl font-bold text-secondary">
          {{ formatPercent(stats.avg_loyalty) }}
        </div>
      </CardContent>
    </Card>

    <Card>
      <CardHeader class="pb-2">
        <CardTitle class="text-sm font-medium text-muted-foreground">
          Proximité Majorité Moyenne
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div class="text-3xl font-bold text-accent">
          {{ formatPercent(stats.avg_majority_proximity) }}
        </div>
      </CardContent>
    </Card>
  </div>
</template>
