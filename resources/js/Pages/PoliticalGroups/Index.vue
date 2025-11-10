<script setup>
import { Head, Link } from "@inertiajs/vue3";
import PublicLayout from "@/Layouts/PublicLayout.vue";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import { Users, TrendingUp } from "lucide-vue-next";

const props = defineProps({
    parties: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <PublicLayout title="Partis Politiques">
        <Head title="Partis Politiques" />

        <div class="container mx-auto px-4 max-w-7xl">
            <!-- Header -->
            <div class="mb-8">
                <h1
                    class="text-4xl font-bold text-gray-900 dark:text-white mb-4"
                >
                    Partis Politiques
                </h1>
                <p class="text-lg text-muted-foreground">
                    Découvrez comment votent les différents partis politiques
                    représentés à l'Assemblée nationale
                </p>
            </div>

            <!-- Parties Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <Link
                    v-for="party in parties"
                    :key="party.id"
                    :href="`/parties/${party.id}`"
                    class="block group"
                >
                    <Card
                        class="h-full hover:shadow-lg transition-all duration-200 group-hover:scale-[1.02]"
                    >
                        <CardHeader>
                            <div class="flex items-start gap-4">
                                <div
                                    class="w-16 h-16 rounded-full flex-shrink-0 flex items-center justify-center text-white font-bold text-xl"
                                    :style="{
                                        backgroundColor:
                                            party.couleur || '#808080',
                                    }"
                                >
                                    {{ party.sigle }}
                                </div>
                                <div class="flex-1">
                                    <CardTitle class="text-lg mb-2">
                                        {{ party.nom }}
                                    </CardTitle>
                                    <div
                                        class="flex items-center gap-2 text-sm text-muted-foreground"
                                    >
                                        <Users class="h-4 w-4" />
                                        <span
                                            >{{ party.deputies_count }} député{{
                                                party.deputies_count > 1
                                                    ? "s"
                                                    : ""
                                            }}</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div
                                class="flex items-center gap-2 text-sm text-primary"
                            >
                                <TrendingUp class="h-4 w-4" />
                                <span>Voir les votes</span>
                            </div>
                        </CardContent>
                    </Card>
                </Link>
            </div>

            <!-- Empty state -->
            <Card v-if="parties.length === 0" class="text-center py-12">
                <CardContent>
                    <p class="text-muted-foreground">
                        Aucun parti politique trouvé.
                    </p>
                </CardContent>
            </Card>
        </div>
    </PublicLayout>
</template>
