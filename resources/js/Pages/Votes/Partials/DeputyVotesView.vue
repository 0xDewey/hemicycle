<script setup>
import { Link } from "@inertiajs/vue3";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardContent from "@/Components/ui/CardContent.vue";

const props = defineProps({
    deputyVotes: {
        type: Object,
        required: true,
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
</script>

<template>
    <div class="space-y-6">
        <div v-for="(deputies, position) in deputyVotes" :key="position">
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
                                <p class="text-sm text-muted-foreground">
                                    {{ deputyVote.deputy.departement }} -
                                    {{ deputyVote.deputy.circonscription }}e
                                    circ.
                                </p>
                                <div
                                    v-if="deputyVote.deputy.political_group"
                                    class="flex items-center gap-2 mt-1 text-xs text-muted-foreground"
                                >
                                    <div
                                        v-if="
                                            deputyVote.deputy.political_group
                                                .couleur
                                        "
                                        class="w-2 h-2 rounded-full"
                                        :style="{
                                            backgroundColor:
                                                deputyVote.deputy
                                                    .political_group.couleur,
                                        }"
                                    ></div>
                                    <span
                                        :title="
                                            deputyVote.deputy.political_group
                                                .libelle_abrege
                                        "
                                    >
                                        {{
                                            deputyVote.deputy.political_group
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
                                    v-if="deputyVote.cause_position === 'MG'"
                                    class="mt-2"
                                >
                                    <span
                                        class="text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded"
                                    >
                                        Membre du gouvernement
                                    </span>
                                </div>
                                <div
                                    v-if="deputyVote.cause_position === 'PAN'"
                                    class="mt-2"
                                >
                                    <span
                                        class="text-xs bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200 px-2 py-1 rounded"
                                    >
                                        Président(e) de l'Assemblée nationale
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
</template>
