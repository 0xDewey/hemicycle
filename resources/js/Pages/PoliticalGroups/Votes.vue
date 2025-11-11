<script setup>
import { ref } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import PublicLayout from "@/Layouts/PublicLayout.vue";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Button from "@/Components/ui/Button.vue";
import { ArrowLeft, Search, ChevronLeft, ChevronRight } from "lucide-vue-next";

const props = defineProps({
    party: {
        type: Object,
        required: true,
    },
    votes: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const search = ref(props.filters.search || "");
const position = ref(props.filters.position || "");

const applyFilters = () => {
    router.get(
        `/parties/${props.party.id}/votes`,
        {
            search: search.value,
            position: position.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
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
        ? "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100"
        : "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100";
};

const getPositionColor = (position) => {
    const colors = {
        pour: "text-green-600",
        contre: "text-red-600",
        abstention: "text-gray-600",
        non_votant: "text-orange-600",
    };
    return colors[position] || "text-gray-600";
};
</script>

<template>
    <PublicLayout :title="`Votes de ${party.nom}`">
        <Head :title="`Votes de ${party.nom}`" />

        <div class="container mx-auto px-4 max-w-7xl">
            <!-- Back Button -->
            <Link
                :href="`/parties/${party.id}`"
                class="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground mb-6 transition-colors"
            >
                <ArrowLeft class="h-4 w-4" />
                Retour à {{ party.nom }}
            </Link>

            <!-- Header -->
            <Card class="mb-8">
                <CardHeader>
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 rounded-full flex-shrink-0"
                            :style="{
                                backgroundColor: party.couleur || '#808080',
                            }"
                        ></div>
                        <div>
                            <CardTitle class="text-2xl">{{
                                party.nom
                            }}</CardTitle>
                            <p class="text-muted-foreground">
                                {{ party.sigle }}
                            </p>
                        </div>
                    </div>
                </CardHeader>
            </Card>

            <!-- Filters -->
            <Card class="mb-6">
                <CardContent class="pt-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="relative">
                            <Search
                                class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground"
                            />
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Rechercher un scrutin..."
                                class="w-full pl-10 pr-4 py-2 border rounded-md bg-background"
                                @keyup.enter="applyFilters"
                            />
                        </div>

                        <div class="flex gap-2">
                            <select
                                v-model="position"
                                class="flex-1 px-4 py-2 border rounded-md bg-background"
                                @change="applyFilters"
                            >
                                <option value="">Toutes les positions</option>
                                <option value="pour">Pour</option>
                                <option value="contre">Contre</option>
                                <option value="abstention">Abstention</option>
                                <option value="non_votant">Non votant</option>
                            </select>

                            <Button @click="applyFilters"> Filtrer </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Votes List -->
            <div class="space-y-4">
                <Card
                    v-for="vote in votes.data"
                    :key="vote.id"
                    class="hover:shadow-md transition-shadow"
                >
                    <CardContent class="pt-6">
                        <Link :href="`/votes/${vote.id}`" class="block">
                            <div
                                class="flex items-start justify-between gap-4 mb-4"
                            >
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span
                                            class="text-sm text-muted-foreground"
                                        >
                                            Scrutin n°{{ vote.numero }}
                                        </span>
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >•</span
                                        >
                                        <span
                                            class="text-sm text-muted-foreground"
                                        >
                                            {{ formatDate(vote.date_scrutin) }}
                                        </span>
                                    </div>
                                    <h3 class="font-semibold text-lg mb-2">
                                        {{ vote.titre }}
                                    </h3>
                                    <p
                                        v-if="vote.description"
                                        class="text-sm text-muted-foreground line-clamp-2"
                                    >
                                        {{ vote.description }}
                                    </p>
                                </div>
                                <span
                                    :class="[
                                        'px-3 py-1 rounded-full text-sm font-medium whitespace-nowrap',
                                        getResultatBadge(vote.resultat),
                                    ]"
                                >
                                    {{ vote.resultat }}
                                </span>
                            </div>

                            <!-- Party vote stats -->
                            <div
                                class="grid grid-cols-2 md:grid-cols-5 gap-4 p-4 bg-muted/50 rounded-lg"
                            >
                                <div>
                                    <p
                                        class="text-xs text-muted-foreground mb-1"
                                    >
                                        Pour
                                    </p>
                                    <p class="text-xl font-bold text-green-600">
                                        {{ vote.party_votes.pour }}
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="text-xs text-muted-foreground mb-1"
                                    >
                                        Contre
                                    </p>
                                    <p class="text-xl font-bold text-red-600">
                                        {{ vote.party_votes.contre }}
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="text-xs text-muted-foreground mb-1"
                                    >
                                        Abstention
                                    </p>
                                    <p class="text-xl font-bold text-gray-600">
                                        {{ vote.party_votes.abstention }}
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="text-xs text-muted-foreground mb-1"
                                    >
                                        Non votant
                                    </p>
                                    <p
                                        class="text-xl font-bold text-orange-600"
                                    >
                                        {{ vote.party_votes.non_votant }}
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="text-xs text-muted-foreground mb-1"
                                    >
                                        Total
                                    </p>
                                    <p class="text-xl font-bold">
                                        {{ vote.party_votes.total }}
                                    </p>
                                </div>
                            </div>
                        </Link>
                    </CardContent>
                </Card>
            </div>

            <!-- Pagination -->
            <div
                v-if="votes.links.length > 3"
                class="flex flex-wrap justify-center gap-2 my-8"
            >
                <component
                    :is="link.url ? Link : 'span'"
                    v-for="(link, index) in votes.links"
                    :key="index"
                    :href="link.url"
                    :class="[
                        'px-3 py-2 rounded-md transition-colors flex items-center gap-1',
                        link.active
                            ? 'bg-primary text-primary-foreground'
                            : 'bg-background hover:bg-accent',
                        !link.url && 'opacity-50 cursor-not-allowed',
                    ]"
                >
                    <ChevronLeft v-if="index === 0" class="h-4 w-4" />
                    <span
                        v-if="index !== 0 && index !== votes.links.length - 1"
                        v-html="link.label"
                    />
                    <ChevronRight
                        v-if="index === votes.links.length - 1"
                        class="h-4 w-4"
                    />
                </component>
            </div>

            <!-- Empty state -->
            <Card v-if="votes.data.length === 0" class="text-center py-12">
                <CardContent>
                    <p class="text-muted-foreground">
                        Aucun scrutin trouvé pour ces critères.
                    </p>
                </CardContent>
            </Card>
        </div>
    </PublicLayout>
</template>
