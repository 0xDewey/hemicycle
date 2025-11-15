<script setup>
import { ref, computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import PublicLayout from "@/Layouts/PublicLayout.vue";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Button from "@/Components/ui/Button.vue";
import VoteStatsCard from "./Partials/VoteStatsCard.vue";
import PartyStatsView from "./Partials/PartyStatsView.vue";
import DepartmentStatsView from "./Partials/DepartmentStatsView.vue";
import DeputyVotesView from "./Partials/DeputyVotesView.vue";
import {
    Chart as ChartJS,
    ArcElement,
    BarElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
} from "chart.js";
import { ArrowLeft, Users } from "lucide-vue-next";

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
    departmentStats: {
        type: Object,
        required: true,
    },
    departments: {
        type: Array,
        required: true,
    },
});

const selectedPosition = ref("all");
const selectedView = ref("deputies");

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
</script>

<template>
    <PublicLayout :title="`Scrutin n°${vote.numero}`">
        <div class="container mx-auto px-4 max-w-7xl py-12">
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
                        <span
                            class="inline-block px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-300"
                        >
                            {{ vote.type }}
                        </span>
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
                    <VoteStatsCard :vote="vote" :stats="stats" />
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
                    <Button
                        :variant="
                            selectedView === 'departments'
                                ? 'default'
                                : 'outline'
                        "
                        @click="selectedView = 'departments'"
                    >
                        <Users class="h-4 w-4 mr-2" />
                        Par départements
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
            <PartyStatsView
                v-if="selectedView === 'parties'"
                :party-stats="partyStats"
                :selected-position="selectedPosition"
            />

            <!-- Department Stats View -->
            <DepartmentStatsView
                v-if="selectedView === 'departments'"
                :department-stats="departmentStats"
                :selected-position="selectedPosition"
                :departments="departments"
            />

            <!-- Deputy Votes by Position -->
            <DeputyVotesView
                v-if="selectedView === 'deputies'"
                :deputy-votes="filteredDeputyVotes"
            />

            <!-- Empty State -->
            <Card
                v-if="
                    Object.keys(filteredDeputyVotes).length === 0 &&
                    selectedView === 'deputies'
                "
                class="text-center py-12"
            >
                <CardContent>
                    <p class="text-muted-foreground">Aucun vote trouvé</p>
                </CardContent>
            </Card>
        </div>
    </PublicLayout>
</template>
