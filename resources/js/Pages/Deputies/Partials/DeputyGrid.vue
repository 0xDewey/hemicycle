<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import CardFooter from "@/Components/ui/CardFooter.vue";
import { Eye } from "lucide-vue-next";

defineProps({
    deputies: {
        type: Array,
        required: true,
    },
});

// Récupérer les paramètres de l'URL pour les passer aux liens
const getBackUrl = computed(() => {
    const params = new URLSearchParams(window.location.search);
    return params.toString() ? `?${params.toString()}` : "";
});
</script>

<template>
    <div>
        <h2 class="text-2xl font-bold mb-4">Liste des Députés</h2>

        <div v-if="deputies.length === 0" class="text-center py-12">
            <p class="text-muted-foreground">
                Aucun député trouvé pour ce département
            </p>
        </div>

        <div
            v-else
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
        >
            <Card
                v-for="deputy in deputies"
                :key="deputy.id"
                class="hover:shadow-lg transition-shadow"
            >
                <CardHeader>
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <img
                                v-if="deputy.photo"
                                :src="`/storage/${deputy.photo}`"
                                :alt="`Photo de ${deputy.firstname} ${deputy.name}`"
                                class="w-16 h-16 rounded-lg object-cover border-2 border-border"
                                @error="
                                    (e) => {
                                        e.target.style.display = 'none';
                                        e.target.nextElementSibling.style.display =
                                            'flex';
                                    }
                                "
                            />
                            <div
                                class="w-16 h-16 rounded-lg bg-muted flex items-center justify-center text-2xl font-bold text-muted-foreground"
                                :style="{
                                    display: deputy.photo ? 'none' : 'flex',
                                }"
                            >
                                {{ deputy.firstname?.[0]?.toUpperCase() || ""
                                }}{{ deputy.name?.[0]?.toUpperCase() || "" }}
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <CardTitle class="text-lg">
                                {{ deputy.firstname }} {{ deputy.name }}
                            </CardTitle>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="space-y-2">
                        <div v-if="deputy.political_group" class="mb-3">
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-3 h-3 rounded-full"
                                    :style="{
                                        backgroundColor:
                                            deputy.political_group.couleur,
                                    }"
                                ></div>
                                <span class="text-sm font-medium">
                                    {{ deputy.political_group.sigle }}
                                </span>
                            </div>
                            <p class="text-xs text-muted-foreground mt-1">
                                {{ deputy.political_group.nom }}
                            </p>
                        </div>
                        <div v-if="deputy.constituency" class="text-sm">
                            <span class="font-medium text-muted-foreground"
                                >Circonscription :</span
                            >
                            <p class="text-foreground">
                                {{ deputy.constituency }}
                            </p>
                        </div>
                    </div>
                </CardContent>
                <CardFooter>
                    <Link
                        :href="`/deputies/${deputy.id}/votes${getBackUrl}`"
                        class="inline-flex items-center gap-2 text-sm text-primary hover:underline"
                    >
                        <Eye class="h-4 w-4" />
                        Voir les votes
                    </Link>
                </CardFooter>
            </Card>
        </div>
    </div>
</template>
