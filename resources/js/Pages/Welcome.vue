<script setup>
import { Link } from "@inertiajs/vue3";
import PublicLayout from "@/Layouts/PublicLayout.vue";
import Card from "@/Components/ui/Card.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import CardDescription from "@/Components/ui/CardDescription.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import Button from "@/Components/ui/Button.vue";
import { Users, Vote, Building2 } from "lucide-vue-next";

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
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
});
</script>

<template>
    <PublicLayout
        title="Accueil - Visibilité des Députés"
        :can-login="canLogin"
        :can-register="canRegister"
    >
        <div class="w-full max-w-7xl mx-auto px-6 py-12">
            <!-- Hero Section -->
            <div class="text-center mb-16">
                <h2
                    class="text-4xl md:text-5xl font-bold text-gray-900 dark:text-white mb-4"
                >
                    Explorez l'Assemblée Nationale
                </h2>
                <p
                    class="text-xl text-gray-600 dark:text-gray-300 max-w-2xl mx-auto"
                >
                    Consultez les députés, leurs votes et leurs groupes
                    politiques de la 17e législature.
                </p>
            </div>

            <!-- Statistics Cards -->
            <div class="grid gap-6 md:grid-cols-2 mb-12">
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
            </div>

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
                            <li>• Historique des mandats</li>
                            <li>• Consulter leurs votes individuels</li>
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
                            <li>• Voir les positions des députés</li>
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
