<script setup>
import { ref, computed, onMounted } from "vue";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardContent from "@/Components/ui/CardContent.vue";

const props = defineProps({
    deputyId: {
        type: Number,
        required: true,
    },
    days: {
        type: Number,
        default: 30,
    },
});

const data = ref(null);
const loading = ref(true);

const stats = computed(() => {
    if (!data.value) return null;

    const totalDays = data.value.length;
    const daysWithVotes = data.value.filter((d) => d.total_votes > 0).length;
    const daysWithoutVotes = totalDays - daysWithVotes;
    const participationRate =
        totalDays > 0 ? ((daysWithVotes / totalDays) * 100).toFixed(1) : 0;

    return {
        totalDays,
        daysWithVotes,
        daysWithoutVotes,
        participationRate,
    };
});

const maxVotes = computed(() => {
    if (!data.value || data.value.length === 0) return 0;
    return Math.max(...data.value.map((d) => d.total_votes));
});

const chartHeight = 200;
const minBarHeightNoVote = 12; // Hauteur pour les jours sans vote
const minBarHeightWithVote = 40; // Hauteur minimum pour les jours avec vote

const getBarHeight = (votes) => {
    if (votes === 0) return minBarHeightNoVote;
    if (maxVotes.value === 0) return minBarHeightNoVote;
    const calculatedHeight = (votes / maxVotes.value) * chartHeight;
    return Math.max(minBarHeightWithVote, calculatedHeight);
};

const getBarColor = (votes) => {
    if (votes === 0) return "bg-red-400 dark:bg-red-600";
    if (votes >= maxVotes.value * 0.7) return "bg-green-500 dark:bg-green-600";
    if (votes >= maxVotes.value * 0.4)
        return "bg-yellow-500 dark:bg-yellow-600";
    return "bg-orange-500 dark:bg-orange-600";
};

const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString("fr-FR", {
        day: "2-digit",
        month: "2-digit",
    });
};

onMounted(async () => {
    try {
        const response = await fetch(
            `/deputies/api/daily-participation/${props.deputyId}?days=${props.days}`
        );
        data.value = await response.json();
    } catch (error) {
        console.error(
            "Erreur lors du chargement des données de participation:",
            error
        );
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle
                >Participation quotidienne ({{ days }} derniers
                jours)</CardTitle
            >
        </CardHeader>
        <CardContent>
            <div v-if="loading" class="text-center py-8">
                <div
                    class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary"
                ></div>
            </div>

            <div v-else-if="data && data.length > 0">
                <!-- Statistiques -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div
                        class="text-center p-4 bg-slate-50 dark:bg-slate-800 rounded-lg"
                    >
                        <div
                            class="text-2xl font-bold text-green-600 dark:text-green-400"
                        >
                            {{ stats.daysWithVotes }}
                        </div>
                        <div class="text-sm text-muted-foreground">
                            Jours avec vote
                        </div>
                    </div>
                    <div
                        class="text-center p-4 bg-slate-50 dark:bg-slate-800 rounded-lg"
                    >
                        <div
                            class="text-2xl font-bold text-red-600 dark:text-red-400"
                        >
                            {{ stats.daysWithoutVotes }}
                        </div>
                        <div class="text-sm text-muted-foreground">
                            Jours sans vote
                        </div>
                    </div>
                    <div
                        class="text-center p-4 bg-slate-50 dark:bg-slate-800 rounded-lg"
                    >
                        <div
                            class="text-2xl font-bold text-blue-600 dark:text-blue-400"
                        >
                            {{ stats.participationRate }}%
                        </div>
                        <div class="text-sm text-muted-foreground">
                            Taux de participation
                        </div>
                    </div>
                    <div
                        class="text-center p-4 bg-slate-50 dark:bg-slate-800 rounded-lg"
                    >
                        <div
                            class="text-2xl font-bold text-purple-600 dark:text-purple-400"
                        >
                            {{ maxVotes }}
                        </div>
                        <div class="text-sm text-muted-foreground">
                            Max votes/jour
                        </div>
                    </div>
                </div>

                <!-- Graphique -->
                <div class="overflow-x-auto flex justify-center">
                    <div class="min-w-[600px] w-11/12">
                        <div
                            class="flex items-end justify-between gap-1 h-64 mb-2"
                        >
                            <div
                                v-for="day in data"
                                :key="day.date"
                                class="flex-1 flex flex-col items-center group relative"
                            >
                                <div class="flex-1 flex items-end w-full">
                                    <div
                                        :class="[
                                            'w-full rounded-t transition-all duration-200 group-hover:opacity-80',
                                            getBarColor(day.total_votes),
                                        ]"
                                        :style="{
                                            height:
                                                getBarHeight(day.total_votes) +
                                                'px',
                                        }"
                                    ></div>
                                </div>

                                <!-- Tooltip -->
                                <div
                                    class="absolute bottom-full mb-2 hidden group-hover:block z-10"
                                >
                                    <div
                                        class="bg-gray-900 text-white text-xs rounded py-2 px-3 whitespace-nowrap"
                                    >
                                        <div class="font-semibold">
                                            {{ formatDate(day.date) }}
                                        </div>
                                        <div class="mt-1">
                                            {{ day.total_votes }} vote{{
                                                day.total_votes > 1 ? "s" : ""
                                            }}
                                        </div>
                                        <div
                                            v-if="day.total_votes > 0"
                                            class="mt-1 text-gray-300"
                                        >
                                            <div v-if="day.pour > 0">
                                                Pour: {{ day.pour }}
                                            </div>
                                            <div v-if="day.contre > 0">
                                                Contre: {{ day.contre }}
                                            </div>
                                            <div v-if="day.abstention > 0">
                                                Abstention: {{ day.abstention }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Axe des dates -->
                        <div
                            class="flex justify-between gap-1 text-xs text-muted-foreground"
                        >
                            <div
                                v-for="(day, index) in data"
                                :key="day.date"
                                class="flex-1 text-center"
                            >
                                <span
                                    v-if="
                                        index % Math.ceil(data.length / 10) ===
                                        0
                                    "
                                >
                                    {{ formatDate(day.date) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Légende -->
                <div class="flex flex-wrap gap-4 mt-6 text-sm">
                    <div class="flex items-center gap-2">
                        <div
                            class="w-4 h-4 bg-red-400 dark:bg-red-600 rounded"
                        ></div>
                        <span class="text-muted-foreground">Aucun vote</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div
                            class="w-4 h-4 bg-orange-500 dark:bg-orange-600 rounded"
                        ></div>
                        <span class="text-muted-foreground"
                            >Participation faible</span
                        >
                    </div>
                    <div class="flex items-center gap-2">
                        <div
                            class="w-4 h-4 bg-yellow-500 dark:bg-yellow-600 rounded"
                        ></div>
                        <span class="text-muted-foreground"
                            >Participation moyenne</span
                        >
                    </div>
                    <div class="flex items-center gap-2">
                        <div
                            class="w-4 h-4 bg-green-500 dark:bg-green-600 rounded"
                        ></div>
                        <span class="text-muted-foreground"
                            >Participation élevée</span
                        >
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-8 text-muted-foreground">
                Aucune donnée de participation disponible pour cette période.
            </div>
        </CardContent>
    </Card>
</template>
