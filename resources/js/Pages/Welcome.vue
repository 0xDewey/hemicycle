<script setup>
import { Link } from "@inertiajs/vue3";
import PublicLayout from "@/Layouts/PublicLayout.vue";
import Card from "@/Components/ui/Card.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import Button from "@/Components/ui/Button.vue";
import Badge from "@/Components/ui/Badge.vue";
import {
    Users,
    Vote,
    Building2,
    TrendingUp,
    Calendar,
    CheckCircle,
    XCircle,
} from "lucide-vue-next";

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    deputiesCount: {
        type: Number,
        default: 0,
    },
    votesCount: {
        type: Number,
        default: 0,
    },
    politicalGroupsCount: {
        type: Number,
        default: 0,
    },
    recentVotes: {
        type: Array,
        default: () => [],
    },
    politicalGroups: {
        type: Array,
        default: () => [],
    },
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
        ? "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200"
        : "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200";
};
</script>

<template>
    <PublicLayout
        title="Accueil"
        :can-login="canLogin"
        :can-register="canRegister"
    >
        <div class="w-full max-w-7xl mx-auto px-6 py-12">
            <!-- Hero Section -->
            <div class="text-center mb-16">
                <h1
                    class="text-5xl md:text-6xl font-bold text-gray-900 dark:text-white mb-4"
                >
                    HémiCycle
                </h1>
                <p
                    class="text-xl text-gray-600 dark:text-gray-300 max-w-2xl mx-auto"
                >
                    Suivez l'activité de l'Assemblée Nationale : députés,
                    scrutins et groupes politiques de la 17e législature
                </p>
            </div>

            <!-- Statistics Cards -->
            <div class="grid gap-6 md:grid-cols-3 mb-12">
                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Députés</CardTitle
                        >
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ deputiesCount }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Députés de la 17e législature
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Scrutins</CardTitle
                        >
                        <Vote class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ votesCount }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Votes enregistrés
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Groupes Politiques</CardTitle
                        >
                        <Building2 class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ politicalGroupsCount }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Partis représentés
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent Votes Section -->
            <Card class="mb-12">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="text-2xl"
                                >Derniers Scrutins</CardTitle
                            >
                            <CardDescription>
                                Les 5 votes les plus récents de l'Assemblée
                            </CardDescription>
                        </div>
                        <Link :href="route('votes.index')">
                            <Button variant="outline" size="sm">
                                Voir tout
                            </Button>
                        </Link>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <Link
                            v-for="vote in recentVotes"
                            :key="vote.id"
                            :href="`/votes/${vote.id}`"
                            class="block border rounded-lg p-4 hover:bg-accent/50 transition-colors"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <Badge
                                            variant="outline"
                                            class="text-xs"
                                        >
                                            Scrutin n°{{ vote.numero }}
                                        </Badge>
                                        <Badge
                                            :class="
                                                getResultatBadge(vote.resultat)
                                            "
                                            class="text-xs"
                                        >
                                            {{ vote.resultat }}
                                        </Badge>
                                        <Badge
                                            v-if="vote.demandeur"
                                            class="text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300"
                                        >
                                            {{ vote.demandeur }}
                                        </Badge>
                                    </div>
                                    <h3
                                        class="font-semibold text-foreground mb-1"
                                    >
                                        {{ vote.titre }}
                                    </h3>
                                    <div
                                        class="flex items-center gap-4 text-xs text-muted-foreground"
                                    >
                                        <span class="flex items-center gap-1">
                                            <Calendar class="h-3 w-3" />
                                            {{ formatDate(vote.date_scrutin) }}
                                        </span>
                                        <span class="text-green-600"
                                            >Pour: {{ vote.pour }}</span
                                        >
                                        <span class="text-red-600"
                                            >Contre: {{ vote.contre }}</span
                                        >
                                        <span class="text-gray-600"
                                            >Abstention:
                                            {{ vote.abstention }}</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </Link>

                        <div
                            v-if="recentVotes.length === 0"
                            class="text-center py-8 text-muted-foreground"
                        >
                            Aucun scrutin récent
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Political Groups Section -->
            <Card class="mb-12">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="text-2xl"
                                >Groupes Politiques</CardTitle
                            >
                            <CardDescription>
                                Principaux groupes parlementaires
                            </CardDescription>
                        </div>
                        <Link :href="route('parties.index')">
                            <Button variant="outline" size="sm">
                                Voir tout
                            </Button>
                        </Link>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <Link
                            v-for="group in politicalGroups"
                            :key="group.id"
                            :href="`/parties/${group.id}`"
                            class="flex items-center gap-3 p-4 border rounded-lg hover:bg-accent/50 transition-colors"
                        >
                            <div
                                class="w-4 h-4 rounded-full flex-shrink-0"
                                :style="{
                                    backgroundColor: group.couleur_associee,
                                }"
                            ></div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold truncate">
                                    {{ group.libelle_abrege }}
                                </p>
                                <p
                                    class="text-xs text-muted-foreground truncate"
                                >
                                    {{ group.libelle }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold">
                                    {{ group.deputies_count }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    député{{
                                        group.deputies_count > 1 ? "s" : ""
                                    }}
                                </p>
                            </div>
                        </Link>

                        <div
                            v-if="politicalGroups.length === 0"
                            class="col-span-2 text-center py-8 text-muted-foreground"
                        >
                            Aucun groupe politique
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Feature Cards -->
            <div class="grid gap-8 md:grid-cols-2">
                <Card class="hover:shadow-lg transition-shadow">
                    <CardHeader>
                        <div class="flex items-center gap-3 mb-2">
                            <div
                                class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg"
                            >
                                <Users
                                    class="h-6 w-6 text-blue-600 dark:text-blue-400"
                                />
                            </div>
                            <CardTitle class="text-2xl">Les Députés</CardTitle>
                        </div>
                        <CardDescription>
                            Parcourez la liste complète des députés avec leurs
                            informations détaillées, mandats et appartenances
                            politiques.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ul
                            class="space-y-2 mb-6 text-sm text-gray-600 dark:text-gray-300"
                        >
                            <li>
                                • Recherche par nom, département ou groupe
                                politique
                            </li>
                            <li>• Informations biographiques complètes</li>
                            <li>• Historique des votes individuels</li>
                            <li>• Statistiques de participation</li>
                        </ul>
                        <Link :href="route('deputies.index')">
                            <Button class="w-full"> Voir les députés </Button>
                        </Link>
                    </CardContent>
                </Card>

                <Card class="hover:shadow-lg transition-shadow">
                    <CardHeader>
                        <div class="flex items-center gap-3 mb-2">
                            <div
                                class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg"
                            >
                                <Vote
                                    class="h-6 w-6 text-purple-600 dark:text-purple-400"
                                />
                            </div>
                            <CardTitle class="text-2xl">Les Scrutins</CardTitle>
                        </div>
                        <CardDescription>
                            Explorez tous les scrutins publics avec les détails
                            des votes et les positions de chaque député.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ul
                            class="space-y-2 mb-6 text-sm text-gray-600 dark:text-gray-300"
                        >
                            <li>• Filtrer par type de scrutin et résultat</li>
                            <li>• Détails complets de chaque vote</li>
                            <li>• Répartition pour/contre/abstention</li>
                            <li>• Visualisation par partis politiques</li>
                        </ul>
                        <Link :href="route('votes.index')">
                            <Button class="w-full" variant="secondary">
                                Voir les scrutins
                            </Button>
                        </Link>
                    </CardContent>
                </Card>
            </div>
        </div>
    </PublicLayout>
</template>
