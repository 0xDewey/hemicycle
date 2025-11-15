<script setup>
import { ref, computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import PublicLayout from "@/Layouts/PublicLayout.vue";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import DailyParticipationChart from "./Partials/DailyParticipationChart.vue";
import { ArrowLeft, Users, ChevronLeft, ChevronRight } from "lucide-vue-next";

const props = defineProps({
    deputy: {
        type: Object,
        required: true,
    },
    votes: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    },
});

// Récupérer les paramètres de retour depuis l'URL
const backUrl = computed(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const params = new URLSearchParams();

    if (urlParams.get("search")) {
        params.set("search", urlParams.get("search"));
    }

    if (urlParams.get("department")) {
        params.set("department", urlParams.get("department"));
    }

    return params.toString() ? `/deputies?${params.toString()}` : "/deputies";
});

const getPositionBadge = (position) => {
    const badges = {
        pour: {
            label: "Pour",
            variant: "success",
            class: "bg-green-100 text-green-800",
        },
        contre: {
            label: "Contre",
            variant: "destructive",
            class: "bg-red-100 text-red-800",
        },
        abstention: {
            label: "Abstention",
            variant: "secondary",
            class: "bg-gray-100 text-gray-800",
        },
        non_votant: {
            label: "Non votant",
            variant: "outline",
            class: "bg-orange-100 text-orange-800",
        },
    };
    return badges[position] || badges["non_votant"];
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString("fr-FR", {
        day: "numeric",
        month: "long",
        year: "numeric",
    });
};
</script>

<template>
    <PublicLayout :title="`Votes de ${deputy.prenom} ${deputy.nom}`">
        <div class="container mx-auto px-4 max-w-7xl py-12">
            <!-- Header -->
            <div class="mb-8">
                <Link
                    :href="backUrl"
                    class="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground mb-4 transition-colors"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Retour aux députés
                </Link>
                <div class="flex items-start gap-6 mb-6">
                    <div class="flex-shrink-0">
                        <img
                            v-if="deputy.photo"
                            :src="`/storage/${deputy.photo}`"
                            :alt="`Photo de ${deputy.prenom} ${deputy.nom}`"
                            class="w-24 h-24 rounded-xl object-cover border-4 border-border shadow-lg"
                            @error="
                                (e) => {
                                    e.target.style.display = 'none';
                                    e.target.nextElementSibling.style.display =
                                        'flex';
                                }
                            "
                        />
                        <div
                            class="w-24 h-24 rounded-xl bg-muted flex items-center justify-center text-3xl font-bold text-muted-foreground border-4 border-border shadow-lg"
                            :style="{
                                display: deputy.photo ? 'none' : 'flex',
                            }"
                        >
                            {{ deputy.prenom?.[0]?.toUpperCase() || ""
                            }}{{ deputy.nom?.[0]?.toUpperCase() || "" }}
                        </div>
                    </div>
                    <div class="flex-1">
                        <h1
                            class="text-4xl font-bold text-gray-900 dark:text-white mb-2"
                        >
                            {{ deputy.prenom }} {{ deputy.nom }}
                        </h1>
                        <div
                            class="flex items-center gap-4 text-muted-foreground"
                        >
                            <span>
                                {{ deputy.departement }} -
                                {{ deputy.circonscription }}e circonscription
                            </span>
                            <span
                                v-if="deputy.political_group"
                                class="flex items-center gap-2"
                            >
                                <span>•</span>
                                <div
                                    class="w-3 h-3 rounded-full"
                                    :style="{
                                        backgroundColor:
                                            deputy.political_group
                                                .couleur_associee,
                                    }"
                                ></div>
                                <span :title="deputy.political_group.libelle">
                                    {{ deputy.political_group.libelle_abrege }}
                                </span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daily Participation Chart -->
            <div class="mb-8">
                <DailyParticipationChart :deputy-id="deputy.id" :days="30" />
            </div>

            <!-- Mandate Information Card -->
            <Card class="mb-8">
                <CardHeader>
                    <CardTitle>Informations sur le mandat</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p
                                class="text-sm font-medium text-muted-foreground mb-1"
                            >
                                Date d'entrée à l'Assemblée Nationale
                            </p>
                            <p class="text-lg font-semibold">
                                {{
                                    deputy.mandate_start_date
                                        ? formatDate(deputy.mandate_start_date)
                                        : "Non renseignée"
                                }}
                            </p>
                        </div>
                        <div>
                            <p
                                class="text-sm font-medium text-muted-foreground mb-1"
                            >
                                Date de sortie de l'Assemblée Nationale
                            </p>
                            <p class="text-lg font-semibold">
                                {{
                                    deputy.mandate_end_date
                                        ? formatDate(deputy.mandate_end_date)
                                        : "En cours"
                                }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-4 p-3 bg-muted/50 rounded-lg">
                        <p class="text-xs text-muted-foreground">
                            <span class="font-semibold"
                                >Méthode de calcul :</span
                            >
                            Les statistiques ci-dessous sont calculées
                            uniquement sur les scrutins ayant eu lieu pendant le
                            mandat du député ({{
                                deputy.mandate_start_date
                                    ? `depuis le ${formatDate(
                                          deputy.mandate_start_date
                                      )}`
                                    : "depuis le début"
                            }}{{
                                deputy.mandate_end_date
                                    ? ` jusqu'au ${formatDate(
                                          deputy.mandate_end_date
                                      )}`
                                    : ""
                            }}). Le taux d'absentéisme représente le pourcentage
                            de scrutins auxquels le député n'a pas participé
                            pendant cette période.
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Statistics Cards -->
            <div
                class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-8"
            >
                <Card>
                    <CardContent class="pt-6">
                        <div class="text-center">
                            <p class="text-3xl font-bold text-primary">
                                {{ stats.total }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                Total votes
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="pt-6">
                        <div class="text-center">
                            <p class="text-3xl font-bold text-green-600">
                                {{ stats.pour }}
                            </p>
                            <p class="text-sm text-muted-foreground">Pour</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="pt-6">
                        <div class="text-center">
                            <p class="text-3xl font-bold text-red-600">
                                {{ stats.contre }}
                            </p>
                            <p class="text-sm text-muted-foreground">Contre</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="pt-6">
                        <div class="text-center">
                            <p
                                class="text-3xl font-bold text-gray-600 dark:text-gray-400"
                            >
                                {{ stats.abstention }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                Abstentions
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="pt-6">
                        <div class="text-center">
                            <p class="text-3xl font-bold text-purple-600">
                                {{ stats.taux_absenteisme }}%
                            </p>
                            <p class="text-sm text-muted-foreground">
                                Absentéisme
                            </p>
                            <p class="text-xs text-muted-foreground mt-1">
                                {{ stats.absents }} /
                                {{ stats.total_scrutins }} scrutins
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Votes List -->
            <Card>
                <CardHeader>
                    <CardTitle>Historique des votes</CardTitle>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="votes.data.length === 0"
                        class="text-center py-12 text-muted-foreground"
                    >
                        Aucun vote enregistré pour ce député
                    </div>

                    <div class="space-y-4">
                        <Link
                            v-for="deputyVote in votes.data"
                            :key="deputyVote.id"
                            :href="`/votes/${deputyVote.vote.id}`"
                            class="block border rounded-lg p-4 hover:bg-accent/50 transition-colors"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span
                                            :class="
                                                getPositionBadge(
                                                    deputyVote.position
                                                ).class
                                            "
                                            class="px-2 py-1 rounded text-xs font-medium"
                                        >
                                            {{
                                                getPositionBadge(
                                                    deputyVote.position
                                                ).label
                                            }}
                                        </span>
                                        <span
                                            v-if="deputyVote.par_delegation"
                                            class="px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800"
                                        >
                                            Par délégation
                                        </span>
                                        <span
                                            v-if="
                                                deputyVote.cause_position ===
                                                'MG'
                                            "
                                            class="px-2 py-1 rounded text-xs font-medium bg-purple-100 text-purple-800"
                                        >
                                            Membre du gouvernement
                                        </span>
                                        <span
                                            v-if="
                                                deputyVote.cause_position ===
                                                'PAN'
                                            "
                                            class="px-2 py-1 rounded text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200"
                                        >
                                            Président(e) de l'Assemblée
                                            nationale
                                        </span>
                                        <span
                                            v-if="deputyVote.vote.demandeur"
                                            class="px-2 py-1 rounded text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300"
                                        >
                                            {{ deputyVote.vote.demandeur }}
                                        </span>
                                    </div>
                                    <h3
                                        class="font-semibold text-foreground mb-1"
                                    >
                                        {{ deputyVote.vote.titre }}
                                    </h3>
                                    <p
                                        v-if="deputyVote.vote.description"
                                        class="text-sm text-muted-foreground mb-2"
                                    >
                                        {{ deputyVote.vote.description }}
                                    </p>
                                    <div
                                        class="flex items-center gap-2 text-xs"
                                    >
                                        <span class="text-muted-foreground">
                                            {{
                                                formatDate(
                                                    deputyVote.vote.date_scrutin
                                                )
                                            }}
                                        </span>
                                        <span
                                            v-if="deputyVote.vote.type"
                                            class="px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-300"
                                        >
                                            {{ deputyVote.vote.type }}
                                        </span>
                                    </div>
                                </div>
                                <div class="text-right text-sm">
                                    <div class="font-medium">Résultat</div>
                                    <div
                                        :class="
                                            deputyVote.vote.resultat ===
                                            'adopté'
                                                ? 'text-green-600'
                                                : 'text-red-600'
                                        "
                                    >
                                        {{ deputyVote.vote.resultat }}
                                    </div>
                                    <div
                                        class="text-xs text-muted-foreground mt-1"
                                    >
                                        Pour: {{ deputyVote.vote.pour }} |
                                        Contre: {{ deputyVote.vote.contre }}
                                    </div>
                                </div>
                            </div>
                        </Link>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="votes.links.length > 3"
                        class="flex flex-wrap justify-center gap-2 mt-6"
                    >
                        <component
                            v-for="(link, index) in votes.links"
                            :key="index"
                            :is="link.url ? Link : 'span'"
                            :href="link.url"
                            :class="[
                                'px-3 py-2 rounded text-sm flex items-center gap-1',
                                link.active
                                    ? 'bg-primary text-primary-foreground'
                                    : link.url
                                    ? 'bg-accent hover:bg-accent/80'
                                    : 'opacity-50 cursor-not-allowed',
                            ]"
                        >
                            <ChevronLeft v-if="index === 0" class="h-4 w-4" />
                            <span
                                v-if="
                                    index !== 0 &&
                                    index !== votes.links.length - 1
                                "
                                v-html="link.label"
                            />
                            <ChevronRight
                                v-if="index === votes.links.length - 1"
                                class="h-4 w-4"
                            />
                        </component>
                    </div>
                </CardContent>
            </Card>
        </div>
    </PublicLayout>
</template>
