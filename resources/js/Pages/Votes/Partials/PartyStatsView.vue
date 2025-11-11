<script setup>
import { computed } from "vue";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import { Bar } from "vue-chartjs";

const props = defineProps({
    partyStats: {
        type: Object,
        required: true,
    },
    selectedPosition: {
        type: String,
        default: "all",
    },
});

const getPositionBadge = (position) => {
    const badges = {
        pour: "bg-green-100 text-green-800",
        contre: "bg-red-100 text-red-800",
        abstention: "bg-gray-100 text-gray-800",
        non_votant: "bg-orange-100 text-orange-800",
        absents: "bg-purple-100 text-purple-800",
    };
    return badges[position] || "bg-gray-100 text-gray-800";
};

const getPositionLabel = (position) => {
    const labels = {
        pour: "Pour",
        contre: "Contre",
        abstention: "Abstention",
        non_votant: "Non votant",
        absents: "Absents",
    };
    return labels[position] || position;
};

const getPartyChartData = (partyStats) => {
    const partiesMap = new Map();

    Object.keys(partyStats).forEach((position) => {
        const stats = partyStats[position];
        if (!Array.isArray(stats)) {
            console.warn(`partyStats[${position}] is not an array:`, stats);
            return;
        }

        stats.forEach((stat) => {
            if (!stat || !stat.party) {
                console.warn(`Invalid stat for position ${position}:`, stat);
                return;
            }

            if (!partiesMap.has(stat.party)) {
                partiesMap.set(stat.party, {
                    name: stat.party_name || stat.party,
                    color: stat.party_color || "#808080",
                    sigle: stat.party,
                    pour: 0,
                    contre: 0,
                    abstention: 0,
                    non_votant: 0,
                    absents: 0,
                });
            }
            partiesMap.get(stat.party)[position] = stat.count || 0;
        });
    });

    const sortedParties = Array.from(partiesMap.values()).sort((a, b) => {
        const totalA =
            a.pour + a.contre + a.abstention + a.non_votant + a.absents;
        const totalB =
            b.pour + b.contre + b.abstention + b.non_votant + b.absents;
        return totalB - totalA;
    });

    const labels = sortedParties.map((p) => p.sigle);

    return {
        labels,
        datasets: [
            {
                label: "Pour",
                data: sortedParties.map((p) => p.pour),
                backgroundColor: "rgba(34, 197, 94, 0.8)",
                borderColor: "rgba(34, 197, 94, 1)",
                borderWidth: 1,
            },
            {
                label: "Contre",
                data: sortedParties.map((p) => p.contre),
                backgroundColor: "rgba(239, 68, 68, 0.8)",
                borderColor: "rgba(239, 68, 68, 1)",
                borderWidth: 1,
            },
            {
                label: "Abstention",
                data: sortedParties.map((p) => p.abstention),
                backgroundColor: "rgba(156, 163, 175, 0.8)",
                borderColor: "rgba(156, 163, 175, 1)",
                borderWidth: 1,
            },
            {
                label: "Non votant",
                data: sortedParties.map((p) => p.non_votant),
                backgroundColor: "rgba(251, 146, 60, 0.8)",
                borderColor: "rgba(251, 146, 60, 1)",
                borderWidth: 1,
            },
            {
                label: "Absents",
                data: sortedParties.map((p) => p.absents),
                backgroundColor: "rgba(168, 85, 247, 0.8)",
                borderColor: "rgba(168, 85, 247, 1)",
                borderWidth: 1,
            },
        ],
    };
};

const partyVoteChartData = computed(() => getPartyChartData(props.partyStats));

const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        x: {
            stacked: true,
            grid: {
                display: false,
            },
            ticks: {
                font: {
                    size: 11,
                },
            },
        },
        y: {
            stacked: true,
            beginAtZero: true,
            ticks: {
                stepSize: 1,
            },
        },
    },
    plugins: {
        legend: {
            position: "top",
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
                    const label = context.dataset.label || "";
                    const value = context.parsed.y || 0;
                    return `${label}: ${value} député${value > 1 ? "s" : ""}`;
                },
                afterBody: function (items) {
                    if (items.length === 0) return;
                    const dataIndex = items[0].dataIndex;
                    let total = 0;
                    items[0].chart.data.datasets.forEach((dataset) => {
                        total += dataset.data[dataIndex] || 0;
                    });
                    return `\nTotal du parti: ${total} député${
                        total > 1 ? "s" : ""
                    }`;
                },
            },
        },
    },
};
</script>

<template>
    <div class="space-y-6">
        <!-- Party Vote Distribution Chart -->
        <Card>
            <CardHeader>
                <CardTitle class="text-center">
                    Répartition des votes par parti politique
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="h-96">
                    <Bar
                        :data="partyVoteChartData"
                        :options="barChartOptions"
                    />
                </div>
            </CardContent>
        </Card>

        <!-- Party Stats by Position -->
        <div
            v-for="(stats, position) in partyStats"
            :key="position"
            v-show="selectedPosition === 'all' || selectedPosition === position"
        >
            <Card>
                <CardHeader>
                    <div class="flex items-center gap-3">
                        <span
                            :class="[
                                'px-3 py-1 rounded-full text-sm font-medium',
                                getPositionBadge(position),
                            ]"
                        >
                            {{ getPositionLabel(position) }}
                        </span>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div
                            v-for="party in stats"
                            :key="party.party"
                            class="flex items-center justify-between p-4 border rounded-lg hover:bg-accent/50 transition-colors"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-4 h-4 rounded-full"
                                    :style="{
                                        backgroundColor: party.party_color,
                                    }"
                                ></div>
                                <div>
                                    <p class="font-medium">
                                        {{ party.party_name }}
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ party.party }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold">
                                    {{ party.count }}
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    député{{ party.count > 1 ? "s" : "" }}
                                </p>
                                <!-- Détails pour les non-votants -->
                                <div
                                    v-if="
                                        position === 'non_votant' &&
                                        party.count > 0
                                    "
                                    class="mt-2 text-xs text-muted-foreground space-y-1"
                                >
                                    <p v-if="party.pan > 0">
                                        Président AN: {{ party.pan }}
                                    </p>
                                    <p v-if="party.gov > 0">
                                        Gouvernement: {{ party.gov }}
                                    </p>
                                    <p v-if="party.autres > 0">
                                        Autres: {{ party.autres }}
                                    </p>
                                </div>
                                <!-- Afficher le total des députés du parti pour les absents -->
                                <p
                                    v-if="position === 'absents'"
                                    class="text-xs text-muted-foreground mt-1"
                                >
                                    sur {{ party.total_deputies }} député{{
                                        party.total_deputies > 1 ? "s" : ""
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
