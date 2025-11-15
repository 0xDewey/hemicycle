<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { Link, router } from "@inertiajs/vue3";
import axios from "axios";
import PublicLayout from "@/Layouts/PublicLayout.vue";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import Button from "@/Components/ui/Button.vue";
import CirconscriptionsMap from "./Partials/CirconscriptionsMap.vue";
import { Users } from "lucide-vue-next";

const props = defineProps({
    departments: {
        type: Array,
        required: true,
    },
    searchOptions: {
        type: Array,
        required: true,
    },
    lastSyncDate: {
        type: String,
        default: null,
    },
});

const selectedDepartment = ref("");

// Gérer la sélection d'une circonscription depuis la carte
const handleCirconscriptionSelect = (circonscription) => {
    if (circonscription && circonscription.code_departement) {
        selectedDepartment.value = circonscription.code_departement;
    } else {
        selectedDepartment.value = "";
    }
};

// Gérer la sélection d'un député depuis la carte
const handleDeputySelect = (deputyId) => {
    router.visit(`/deputies/${deputyId}/votes`);
};
</script>

<template>
    <PublicLayout title="Députés de l'Assemblée Nationale">
        <div class="container mx-auto px-4 max-w-7xl py-12">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-4">
                        <div
                            class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg"
                        >
                            <Users
                                class="h-8 w-8 text-blue-600 dark:text-blue-400"
                            />
                        </div>
                        <div>
                            <h1
                                class="text-4xl font-bold text-gray-900 dark:text-white mb-2"
                            >
                                Députés de l'Assemblée Nationale
                            </h1>
                            <p class="text-muted-foreground">
                                Explorez les députés par circonscription et code
                                postal
                            </p>
                        </div>
                    </div>
                    <Link href="/votes">
                        <Button variant="secondary"> Voir les scrutins </Button>
                    </Link>
                </div>
            </div>

            <!-- Map View -->
            <Card class="mb-8">
                <CardHeader>
                    <CardTitle>Carte des circonscriptions</CardTitle>
                </CardHeader>
                <CardContent>
                    <CirconscriptionsMap
                        :selected-department="selectedDepartment"
                        @select-circonscription="handleCirconscriptionSelect"
                        @select-deputy="handleDeputySelect"
                    />
                </CardContent>
            </Card>

            <!-- Footer with legal notice -->
            <footer class="mt-12 text-sm text-muted-foreground border-t pt-8">
                <p class="mb-2">
                    Données issues du portail open data de
                    <a
                        href="https://data.assemblee-nationale.fr"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-primary hover:underline"
                    >
                        l'Assemblée nationale </a
                    >, sous licence
                    <a
                        href="https://www.etalab.gouv.fr/licence-ouverte-open-licence"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-primary hover:underline"
                    >
                        Etalab Open Licence v2.0 </a
                    >.
                </p>
                <p>
                    Ce site est un projet indépendant et n'a aucun lien officiel
                    avec l'Assemblée nationale.
                </p>
                <p
                    v-if="lastSyncDate"
                    class="mt-2 text-xs text-muted-foreground"
                >
                    Dernière mise à jour des données : {{ lastSyncDate }}
                </p>
            </footer>
        </div>
    </PublicLayout>
</template>
