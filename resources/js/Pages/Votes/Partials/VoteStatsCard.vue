<script setup>
import { computed } from "vue";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import { Pie } from "vue-chartjs";

const props = defineProps({
    vote: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    },
});

// Chart.js data for overall vote distribution
const overallVoteChartData = computed(() => {
    return {
        labels: ["Pour", "Contre", "Abstention", "Non votant", "Absents"],
        datasets: [
            {
                data: [
                    props.stats.pour,
                    props.stats.contre,
                    props.stats.abstention,
                    props.stats.non_votant,
                    props.stats.absents,
                ],
                backgroundColor: [
                    "rgba(34, 197, 94, 0.8)",
                    "rgba(239, 68, 68, 0.8)",
                    "rgba(156, 163, 175, 0.8)",
                    "rgba(251, 146, 60, 0.8)",
                    "rgba(168, 85, 247, 0.8)",
                ],
                borderColor: [
                    "rgba(34, 197, 94, 1)",
                    "rgba(239, 68, 68, 1)",
                    "rgba(156, 163, 175, 1)",
                    "rgba(251, 146, 60, 1)",
                    "rgba(168, 85, 247, 1)",
                ],
                borderWidth: 2,
            },
        ],
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
        legend: {
            position: "bottom",
            labels: {
                padding: 15,
                font: {
                    size: 12,
                },
            },
        },
        tooltip: {
            callbacks: {
                label: function (context) {
                    const label = context.label || "";
                    const value = context.parsed || 0;
                    const total = context.dataset.data.reduce(
                        (a, b) => a + b,
                        0
                    );
                    const percentage = ((value / total) * 100).toFixed(1);
                    return `${label}: ${value} (${percentage}%)`;
                },
            },
        },
    },
};
</script>

<template>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Stats Grid -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <p
                    class="text-sm text-green-600 dark:text-green-400 font-medium"
                >
                    Pour
                </p>
                <p
                    class="text-3xl font-bold text-green-700 dark:text-green-300"
                >
                    {{ vote.pour }}
                </p>
            </div>
            <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                <p class="text-sm text-red-600 dark:text-red-400 font-medium">
                    Contre
                </p>
                <p class="text-3xl font-bold text-red-700 dark:text-red-300">
                    {{ vote.contre }}
                </p>
            </div>
            <div class="p-4 bg-gray-50 dark:bg-gray-700/20 rounded-lg">
                <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">
                    Abstentions
                </p>
                <p class="text-3xl font-bold text-gray-700 dark:text-gray-300">
                    {{ vote.abstention }}
                </p>
            </div>
            <div class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                <p
                    class="text-sm text-orange-600 dark:text-orange-400 font-medium"
                >
                    Non votants
                </p>
                <p
                    class="text-3xl font-bold text-orange-700 dark:text-orange-300"
                >
                    {{ stats.non_votant }}
                </p>
                <div
                    v-if="stats.non_votant > 0"
                    class="mt-2 text-xs text-muted-foreground space-y-1"
                >
                    <p v-if="stats.non_votant_pan > 0">
                        • Président AN: {{ stats.non_votant_pan }}
                    </p>
                    <p v-if="stats.non_votant_gov > 0">
                        • Gouvernement: {{ stats.non_votant_gov }}
                    </p>
                    <p v-if="stats.non_votant_autres > 0">
                        • Autres: {{ stats.non_votant_autres }}
                    </p>
                </div>
            </div>
            <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                <p
                    class="text-sm text-purple-600 dark:text-purple-400 font-medium"
                >
                    Absents
                </p>
                <p
                    class="text-3xl font-bold text-purple-700 dark:text-purple-300"
                >
                    {{ stats.absents }}
                </p>
            </div>
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <p class="text-sm text-blue-600 dark:text-blue-400 font-medium">
                    Total
                </p>
                <p class="text-3xl font-bold text-blue-700 dark:text-blue-300">
                    {{
                        stats.pour +
                        stats.contre +
                        stats.abstention +
                        stats.non_votant +
                        stats.absents
                    }}
                </p>
            </div>
        </div>

        <!-- Overall Vote Chart -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg border">
            <h3 class="font-semibold mb-4 text-center">
                Répartition des votes
            </h3>
            <div class="max-w-sm mx-auto">
                <Pie :data="overallVoteChartData" :options="chartOptions" />
            </div>
        </div>
    </div>
</template>
