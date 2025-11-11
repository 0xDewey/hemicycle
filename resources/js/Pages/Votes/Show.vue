<script setup>
import { ref, computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Button from "@/Components/ui/Button.vue";
import Badge from "@/Components/ui/Badge.vue";
import { Pie, Bar } from "vue-chartjs";
import {
    Chart as ChartJS,
    ArcElement,
    BarElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
} from "chart.js";
import {
    ArrowLeft,
    Users,
    ThumbsUp,
    ThumbsDown,
    Minus,
    XCircle,
} from "lucide-vue-next";

// Register Chart.js components
ChartJS.register(
    ArcElement,
    BarElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend
);

const props = defineProps({
    vote: {
        type: Object,
        required: true,
    },
    deputyVotes: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    },
    partyStats: {
        type: Object,
        required: true,
    },
});

const selectedPosition = ref("all");
const selectedView = ref("deputies"); // 'deputies' ou 'parties'

const filteredDeputyVotes = computed(() => {
    if (selectedPosition.value === "all") {
        return props.deputyVotes;
    }
    return {
        [selectedPosition.value]:
            props.deputyVotes[selectedPosition.value] || [],
    };
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString("fr-FR", {
        day: "numeric",
        month: "long",
        year: "numeric",
    });
};

const getResultatBadge = (resultat) => {
    return resultat === "adopté"
        ? "bg-green-100 text-green-800"
        : "bg-red-100 text-red-800";
};

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
                    "rgba(34, 197, 94, 0.8)", // green-500
                    "rgba(239, 68, 68, 0.8)", // red-500
                    "rgba(156, 163, 175, 0.8)", // gray-400
                    "rgba(251, 146, 60, 0.8)", // orange-400
                    "rgba(168, 85, 247, 0.8)", // purple-500
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

// Chart data for party vote distribution
const getPartyChartData = (partyStats) => {
    // Collect all unique parties and their votes by position
    const partiesMap = new Map();

    Object.keys(partyStats).forEach((position) => {
        const stats = partyStats[position];
        // Vérifier que stats est bien un tableau
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

    // Sort parties by total votes (descending)
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
                backgroundColor: "rgba(168, 85, 247, 0.8)", // Purple
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

                    // Calculate total deputies for this party
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
    <Head :title="`Scrutin n°${vote.numero}`" />

    <div
        class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 dark:from-gray-900 dark:to-gray-800 py-12"
    >
        <div class="container mx-auto px-4 max-w-7xl">
            <!-- Back Button -->
            <Link
                href="/votes"
                class="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground mb-6 transition-colors"
            >
                <ArrowLeft class="h-4 w-4" />
                Retour aux scrutins
            </Link>

            <!-- Vote Details -->
            <Card class="mb-8">
                <CardHeader>
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-sm text-muted-foreground">
                                    Scrutin n°{{ vote.numero }}
                                </span>
                                <span class="text-sm text-muted-foreground"
                                    >•</span
                                >
                                <span class="text-sm text-muted-foreground">
                                    {{ formatDate(vote.date_scrutin) }}
                                </span>
                            </div>
                            <CardTitle class="text-2xl">{{
                                vote.titre
                            }}</CardTitle>
                        </div>
                        <span
                            :class="[
                                'px-4 py-2 rounded-full text-sm font-medium',
                                getResultatBadge(vote.resultat),
                            ]"
                        >
                            {{ vote.resultat }}
                        </span>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="vote.type" class="mb-6">
                        <h3 class="font-semibold mb-2">Type de vote</h3>
                        <p class="text-muted-foreground">{{ vote.type }}</p>
                    </div>

                    <div v-if="vote.demandeur" class="mb-6">
                        <h3 class="font-semibold mb-2">Demandé par</h3>
                        <span
                            class="inline-block px-3 py-1 rounded-full text-sm font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300"
                        >
                            {{ vote.demandeur }}
                        </span>
                    </div>

                    <!-- Vote Stats -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        <!-- Stats Grid -->
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <div
                                class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg"
                            >
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
                            <div
                                class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg"
                            >
                                <p
                                    class="text-sm text-red-600 dark:text-red-400 font-medium"
                                >
                                    Contre
                                </p>
                                <p
                                    class="text-3xl font-bold text-red-700 dark:text-red-300"
                                >
                                    {{ vote.contre }}
                                </p>
                            </div>
                            <div
                                class="p-4 bg-gray-50 dark:bg-gray-700/20 rounded-lg"
                            >
                                <p
                                    class="text-sm text-gray-600 dark:text-gray-400 font-medium"
                                >
                                    Abstentions
                                </p>
                                <p
                                    class="text-3xl font-bold text-gray-700 dark:text-gray-300"
                                >
                                    {{ vote.abstention }}
                                </p>
                            </div>
                            <div
                                class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg"
                            >
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
                                        • Président AN:
                                        {{ stats.non_votant_pan }}
                                    </p>
                                    <p v-if="stats.non_votant_gov > 0">
                                        • Gouvernement:
                                        {{ stats.non_votant_gov }}
                                    </p>
                                    <p v-if="stats.non_votant_autres > 0">
                                        • Autres: {{ stats.non_votant_autres }}
                                    </p>
                                </div>
                            </div>
                            <div
                                class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg"
                            >
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
                            <div
                                class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg"
                            >
                                <p
                                    class="text-sm text-blue-600 dark:text-blue-400 font-medium"
                                >
                                    Total
                                </p>
                                <p
                                    class="text-3xl font-bold text-blue-700 dark:text-blue-300"
                                >
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
                        <div
                            class="bg-white dark:bg-gray-800 p-6 rounded-lg border"
                        >
                            <h3 class="font-semibold mb-4 text-center">
                                Répartition des votes
                            </h3>
                            <div class="max-w-sm mx-auto">
                                <Pie
                                    :data="overallVoteChartData"
                                    :options="chartOptions"
                                />
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- View Toggle & Filter -->
            <div class="mb-6 flex flex-wrap gap-4">
                <div class="flex gap-2">
                    <Button
                        :variant="
                            selectedView === 'deputies' ? 'default' : 'outline'
                        "
                        @click="selectedView = 'deputies'"
                    >
                        <Users class="h-4 w-4 mr-2" />
                        Par députés
                    </Button>
                    <Button
                        :variant="
                            selectedView === 'parties' ? 'default' : 'outline'
                        "
                        @click="selectedView = 'parties'"
                    >
                        <Users class="h-4 w-4 mr-2" />
                        Par partis
                    </Button>
                </div>

                <select
                    v-model="selectedPosition"
                    class="px-4 py-2 rounded-md border border-input bg-background"
                >
                    <option value="all">Toutes les positions</option>
                    <option value="pour">Pour ({{ stats.pour }})</option>
                    <option value="contre">Contre ({{ stats.contre }})</option>
                    <option value="abstention">
                        Abstentions ({{ stats.abstention }})
                    </option>
                    <option value="non_votant">
                        Non votants ({{ stats.non_votant }})
                    </option>
                </select>
            </div>

            <!-- Party Stats View -->
            <div v-if="selectedView === 'parties'" class="space-y-6">
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
                    v-show="
                        selectedPosition === 'all' ||
                        selectedPosition === position
                    "
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
                                                backgroundColor:
                                                    party.party_color,
                                            }"
                                        ></div>
                                        <div>
                                            <p class="font-medium">
                                                {{ party.party_name }}
                                            </p>
                                            <p
                                                class="text-sm text-muted-foreground"
                                            >
                                                {{ party.party }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-2xl font-bold">
                                            {{ party.count }}
                                        </p>
                                        <p
                                            class="text-sm text-muted-foreground"
                                        >
                                            député{{
                                                party.count > 1 ? "s" : ""
                                            }}
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
                                            sur
                                            {{ party.total_deputies }} député{{
                                                party.total_deputies > 1
                                                    ? "s"
                                                    : ""
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Deputy Votes by Position -->
            <div v-else class="space-y-6">
                <div
                    v-for="(deputies, position) in filteredDeputyVotes"
                    :key="position"
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
                                <span class="text-sm text-muted-foreground">
                                    {{ deputies.length }} député{{
                                        deputies.length > 1 ? "s" : ""
                                    }}
                                </span>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div
                                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
                            >
                                <div
                                    v-for="deputyVote in deputies"
                                    :key="deputyVote.id"
                                    class="p-3 border rounded-lg hover:bg-accent/50 transition-colors"
                                >
                                    <Link
                                        :href="`/deputies/${deputyVote.deputy.id}/votes`"
                                        class="block"
                                    >
                                        <p class="font-medium">
                                            {{ deputyVote.deputy.prenom }}
                                            {{ deputyVote.deputy.nom }}
                                        </p>
                                        <p
                                            class="text-sm text-muted-foreground"
                                        >
                                            {{ deputyVote.deputy.departement }}
                                            -
                                            {{
                                                deputyVote.deputy
                                                    .circonscription
                                            }}e circ.
                                        </p>
                                        <div
                                            v-if="
                                                deputyVote.deputy
                                                    .political_group
                                            "
                                            class="flex items-center gap-2 mt-1 text-xs text-muted-foreground"
                                        >
                                            <div
                                                v-if="
                                                    deputyVote.deputy
                                                        .political_group
                                                        .couleur_associee
                                                "
                                                class="w-2 h-2 rounded-full"
                                                :style="{
                                                    backgroundColor:
                                                        deputyVote.deputy
                                                            .political_group
                                                            .couleur_associee,
                                                }"
                                            ></div>
                                            <span
                                                :title="
                                                    deputyVote.deputy
                                                        .political_group.libelle
                                                "
                                            >
                                                {{
                                                    deputyVote.deputy
                                                        .political_group
                                                        .libelle_abrege
                                                }}
                                            </span>
                                        </div>
                                        <div
                                            v-if="deputyVote.par_delegation"
                                            class="mt-2"
                                        >
                                            <span
                                                class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded"
                                            >
                                                Par délégation
                                            </span>
                                        </div>
                                        <div
                                            v-if="
                                                deputyVote.cause_position ===
                                                'MG'
                                            "
                                            class="mt-2"
                                        >
                                            <span
                                                class="text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded"
                                            >
                                                Membre du gouvernement
                                            </span>
                                        </div>
                                        <div
                                            v-if="
                                                deputyVote.cause_position ===
                                                'PAN'
                                            "
                                            class="mt-2"
                                        >
                                            <span
                                                class="text-xs bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200 px-2 py-1 rounded"
                                            >
                                                Président(e) de l'Assemblée
                                                nationale
                                            </span>
                                        </div>
                                    </Link>
                                </div>
                            </div>

                            <div
                                v-if="deputies.length === 0"
                                class="text-center py-8 text-muted-foreground"
                            >
                                Aucun député dans cette catégorie
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Empty State -->
            <Card
                v-if="Object.keys(filteredDeputyVotes).length === 0"
                class="text-center py-12"
            >
                <CardContent>
                    <p class="text-muted-foreground">Aucun vote trouvé</p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
