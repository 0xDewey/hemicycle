<script setup>
import { ref } from "vue";
import { Link, router } from "@inertiajs/vue3";
import PublicLayout from "@/Layouts/PublicLayout.vue";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Button from "@/Components/ui/Button.vue";
import Badge from "@/Components/ui/Badge.vue";
import { Search, Filter, ChevronRight, ArrowLeft, Vote } from "lucide-vue-next";

const props = defineProps({
    votes: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    },
    types: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const search = ref(props.filters.search || "");
const selectedType = ref(props.filters.type || "");
const selectedResultat = ref(props.filters.resultat || "");

const applyFilters = () => {
    router.get(
        "/votes",
        {
            search: search.value,
            type: selectedType.value,
            resultat: selectedResultat.value,
        },
        {
            preserveState: true,
            replace: true,
        }
    );
};

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
    <PublicLayout title="Scrutins de l'Assemblée Nationale">
        <div class="container mx-auto px-4 max-w-7xl py-12">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-4">
                        <div
                            class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg"
                        >
                            <Vote
                                class="h-8 w-8 text-purple-600 dark:text-purple-400"
                            />
                        </div>
                        <div>
                            <h1
                                class="text-4xl font-bold text-gray-900 dark:text-white mb-2"
                            >
                                Scrutins de l'Assemblée Nationale
                            </h1>
                            <p class="text-muted-foreground">
                                Consultez tous les votes qui ont eu lieu à
                                l'Assemblée nationale
                            </p>
                        </div>
                    </div>
                    <Link href="/deputies">
                        <Button variant="secondary"> Voir les députés </Button>
                    </Link>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <Card>
                    <CardContent class="pt-6">
                        <div class="text-center">
                            <p class="text-3xl font-bold text-primary">
                                {{ stats.total }}
                            </p>
                            <p class="text-sm text-muted-foreground mt-1">
                                Total de scrutins
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="pt-6">
                        <div class="text-center">
                            <p class="text-3xl font-bold text-green-600">
                                {{ stats.adopte }}
                            </p>
                            <p class="text-sm text-muted-foreground mt-1">
                                Textes adoptés
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="pt-6">
                        <div class="text-center">
                            <p class="text-3xl font-bold text-red-600">
                                {{ stats.rejete }}
                            </p>
                            <p class="text-sm text-muted-foreground mt-1">
                                Textes rejetés
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <Card class="mb-8">
                <CardHeader>
                    <CardTitle>Filtres</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Search -->
                        <div class="relative">
                            <Search
                                class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground"
                            />
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Rechercher..."
                                class="w-full pl-10 pr-4 py-2 rounded-md border border-input bg-background"
                                @keyup.enter="applyFilters"
                            />
                        </div>

                        <!-- Type filter -->
                        <select
                            v-model="selectedType"
                            class="w-full px-4 py-2 rounded-md border border-input bg-background"
                            @change="applyFilters"
                        >
                            <option value="">Tous les types</option>
                            <option
                                v-for="type in types"
                                :key="type"
                                :value="type"
                            >
                                {{ type }}
                            </option>
                        </select>

                        <!-- Resultat filter -->
                        <select
                            v-model="selectedResultat"
                            class="w-full px-4 py-2 rounded-md border border-input bg-background"
                            @change="applyFilters"
                        >
                            <option value="">Tous les résultats</option>
                            <option value="adopté">Adopté</option>
                            <option value="rejeté">Rejeté</option>
                        </select>
                    </div>
                </CardContent>
            </Card>

            <!-- Votes List -->
            <div class="space-y-4">
                <Card
                    v-for="vote in votes.data"
                    :key="vote.id"
                    class="hover:shadow-lg transition-shadow"
                >
                    <CardHeader>
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
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
                                <CardTitle class="text-lg mb-2">
                                    {{ vote.titre }}
                                </CardTitle>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <span
                                    :class="[
                                        'px-3 py-1 rounded-full text-xs font-medium',
                                        getResultatBadge(vote.resultat),
                                    ]"
                                >
                                    {{ vote.resultat }}
                                </span>
                                <span
                                    v-if="vote.type"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ vote.type }}
                                </span>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center justify-between">
                            <div class="flex gap-6 text-sm">
                                <div>
                                    <span class="font-medium text-green-600"
                                        >Pour :</span
                                    >
                                    <span class="ml-1">{{ vote.pour }}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-red-600"
                                        >Contre :</span
                                    >
                                    <span class="ml-1">{{ vote.contre }}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-600"
                                        >Abstentions :</span
                                    >
                                    <span class="ml-1">{{
                                        vote.abstention
                                    }}</span>
                                </div>
                            </div>
                            <Link
                                :href="`/votes/${vote.id}`"
                                class="text-sm text-primary hover:underline"
                            >
                                Voir les détails →
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <Card v-if="votes.data.length === 0" class="text-center py-12">
                <CardContent>
                    <p class="text-muted-foreground">Aucun scrutin trouvé</p>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div
                v-if="votes.links.length > 3"
                class="flex justify-center gap-2 mt-8"
            >
                <component
                    v-for="(link, index) in votes.links"
                    :key="index"
                    :is="link.url ? Link : 'span'"
                    :href="link.url"
                    :class="[
                        'px-3 py-2 rounded text-sm',
                        link.active
                            ? 'bg-primary text-primary-foreground'
                            : link.url
                            ? 'bg-accent hover:bg-accent/80'
                            : 'opacity-50 cursor-not-allowed',
                    ]"
                >
                    <span v-html="link.label" />
                </component>
            </div>
        </div>
    </PublicLayout>
</template>
