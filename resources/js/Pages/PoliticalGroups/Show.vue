<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { computed } from "vue";
import PublicLayout from "@/Layouts/PublicLayout.vue";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Button from "@/Components/ui/Button.vue";
import {
    ArrowLeft,
    Users,
    ThumbsUp,
    ThumbsDown,
    Minus,
    XCircle,
    Vote,
} from "lucide-vue-next";

const props = defineProps({
    party: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    },
});

const totalVotes = computed(
    () =>
        props.stats.pour +
        props.stats.contre +
        props.stats.abstention +
        props.stats.non_votant
);

const getPourcentage = (count) => {
    if (totalVotes.value === 0) return 0;
    return ((count / totalVotes.value) * 100).toFixed(1);
};
</script>

<template>
    <PublicLayout :title="party.nom">
        <Head :title="party.nom" />

        <div class="container mx-auto px-4 max-w-7xl">
            <!-- Back Button -->
            <Link
                href="/parties"
                class="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground mb-6 transition-colors"
            >
                <ArrowLeft class="h-4 w-4" />
                Retour aux partis
            </Link>

            <!-- Header -->
            <Card class="mb-8">
                <CardHeader>
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div
                                class="w-20 h-20 rounded-full flex-shrink-0 flex items-center justify-center text-white font-bold text-2xl"
                                :style="{
                                    backgroundColor: party.couleur || '#808080',
                                }"
                            >
                                {{ party.sigle }}
                            </div>
                            <div>
                                <CardTitle class="text-3xl mb-2">
                                    {{ party.nom }}
                                </CardTitle>
                                <div
                                    class="flex items-center gap-2 text-muted-foreground"
                                >
                                    <Users class="h-5 w-5" />
                                    <span class="text-lg"
                                        >{{ stats.total_deputies }} député{{
                                            stats.total_deputies > 1 ? "s" : ""
                                        }}</span
                                    >
                                </div>
                            </div>
                        </div>
                        <Link :href="`/parties/${party.id}/votes`">
                            <Button size="lg">
                                <Vote class="h-5 w-5 mr-2" />
                                Voir tous les votes
                            </Button>
                        </Link>
                    </div>
                </CardHeader>
            </Card>

            <!-- Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Vote Stats -->
                <Card>
                    <CardHeader>
                        <CardTitle>Statistiques de vote</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div>
                                <div
                                    class="flex justify-between items-center mb-2"
                                >
                                    <div class="flex items-center gap-2">
                                        <ThumbsUp
                                            class="h-5 w-5 text-green-600"
                                        />
                                        <span class="font-medium">Pour</span>
                                    </div>
                                    <div class="text-right">
                                        <span
                                            class="text-2xl font-bold text-green-600"
                                            >{{ stats.pour }}</span
                                        >
                                        <span
                                            class="text-sm text-muted-foreground ml-2"
                                            >({{
                                                getPourcentage(stats.pour)
                                            }}%)</span
                                        >
                                    </div>
                                </div>
                                <div
                                    class="w-full bg-gray-200 rounded-full h-2"
                                >
                                    <div
                                        class="bg-green-600 h-2 rounded-full"
                                        :style="{
                                            width:
                                                getPourcentage(stats.pour) +
                                                '%',
                                        }"
                                    ></div>
                                </div>
                            </div>

                            <div>
                                <div
                                    class="flex justify-between items-center mb-2"
                                >
                                    <div class="flex items-center gap-2">
                                        <ThumbsDown
                                            class="h-5 w-5 text-red-600"
                                        />
                                        <span class="font-medium">Contre</span>
                                    </div>
                                    <div class="text-right">
                                        <span
                                            class="text-2xl font-bold text-red-600"
                                            >{{ stats.contre }}</span
                                        >
                                        <span
                                            class="text-sm text-muted-foreground ml-2"
                                            >({{
                                                getPourcentage(stats.contre)
                                            }}%)</span
                                        >
                                    </div>
                                </div>
                                <div
                                    class="w-full bg-gray-200 rounded-full h-2"
                                >
                                    <div
                                        class="bg-red-600 h-2 rounded-full"
                                        :style="{
                                            width:
                                                getPourcentage(stats.contre) +
                                                '%',
                                        }"
                                    ></div>
                                </div>
                            </div>

                            <div>
                                <div
                                    class="flex justify-between items-center mb-2"
                                >
                                    <div class="flex items-center gap-2">
                                        <Minus class="h-5 w-5 text-gray-600" />
                                        <span class="font-medium"
                                            >Abstention</span
                                        >
                                    </div>
                                    <div class="text-right">
                                        <span
                                            class="text-2xl font-bold text-gray-600"
                                            >{{ stats.abstention }}</span
                                        >
                                        <span
                                            class="text-sm text-muted-foreground ml-2"
                                            >({{
                                                getPourcentage(
                                                    stats.abstention
                                                )
                                            }}%)</span
                                        >
                                    </div>
                                </div>
                                <div
                                    class="w-full bg-gray-200 rounded-full h-2"
                                >
                                    <div
                                        class="bg-gray-600 h-2 rounded-full"
                                        :style="{
                                            width:
                                                getPourcentage(
                                                    stats.abstention
                                                ) + '%',
                                        }"
                                    ></div>
                                </div>
                            </div>

                            <div>
                                <div
                                    class="flex justify-between items-center mb-2"
                                >
                                    <div class="flex items-center gap-2">
                                        <XCircle
                                            class="h-5 w-5 text-orange-600"
                                        />
                                        <span class="font-medium"
                                            >Non votant</span
                                        >
                                    </div>
                                    <div class="text-right">
                                        <span
                                            class="text-2xl font-bold text-orange-600"
                                            >{{ stats.non_votant }}</span
                                        >
                                        <span
                                            class="text-sm text-muted-foreground ml-2"
                                            >({{
                                                getPourcentage(
                                                    stats.non_votant
                                                )
                                            }}%)</span
                                        >
                                    </div>
                                </div>
                                <div
                                    class="w-full bg-gray-200 rounded-full h-2"
                                >
                                    <div
                                        class="bg-orange-600 h-2 rounded-full"
                                        :style="{
                                            width:
                                                getPourcentage(
                                                    stats.non_votant
                                                ) + '%',
                                        }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- General Stats -->
                <Card>
                    <CardHeader>
                        <CardTitle>Vue d'ensemble</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-6">
                            <div>
                                <p class="text-sm text-muted-foreground mb-1">
                                    Nombre de députés
                                </p>
                                <p class="text-4xl font-bold">
                                    {{ stats.total_deputies }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-muted-foreground mb-1">
                                    Total des votes
                                </p>
                                <p class="text-4xl font-bold">
                                    {{ stats.total_votes }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-muted-foreground mb-1">
                                    Moyenne par député
                                </p>
                                <p class="text-4xl font-bold">
                                    {{
                                        stats.total_deputies > 0
                                            ? Math.round(
                                                  stats.total_votes /
                                                      stats.total_deputies
                                              )
                                            : 0
                                    }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Deputies List -->
            <Card>
                <CardHeader>
                    <CardTitle>Députés du parti</CardTitle>
                </CardHeader>
                <CardContent>
                    <div
                        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
                    >
                        <Link
                            v-for="deputy in party.deputies"
                            :key="deputy.id"
                            :href="`/deputies/${deputy.id}/votes`"
                            class="p-4 border rounded-lg hover:bg-accent/50 transition-colors"
                        >
                            <p class="font-medium">
                                {{ deputy.prenom }} {{ deputy.nom }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ deputy.departement }} -
                                {{ deputy.circonscription }}e circ.
                            </p>
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </div>
    </PublicLayout>
</template>
