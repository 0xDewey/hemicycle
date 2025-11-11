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
import Combobox from "@/Components/ui/Combobox.vue";
import Badge from "@/Components/ui/Badge.vue";
import DeputyGrid from "./Partials/DeputyGrid.vue";
import { Users, Building2, ArrowLeft } from "lucide-vue-next";

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
const deputySearch = ref("");
const departmentStats = ref(null);
const loading = ref(false);

// Restaurer les valeurs depuis l'URL au montage
onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const dept = urlParams.get("department");
    const search = urlParams.get("search");

    if (search) {
        deputySearch.value = search;
    }

    if (dept) {
        selectedDepartment.value = dept;
    }
});

// Fonction pour normaliser le texte (sans accents ni majuscules)
const normalizeText = (text) => {
    return text
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "");
};

const departmentOptions = computed(() => {
    return props.departments.map((dept) => ({
        value: dept.code,
        label: `${dept.code} - ${dept.name} (${dept.count} député${
            dept.count > 1 ? "s" : ""
        })`,
    }));
});

const filteredDepartmentOptions = computed(() => {
    if (!deputySearch.value) {
        return departmentOptions.value;
    }

    const search = normalizeText(deputySearch.value);

    // Chercher parmi tous les députés (recherche normalisée)
    const matchingDeputies = props.searchOptions.filter(
        (option) =>
            option.type === "deputy" &&
            normalizeText(option.label).includes(search)
    );

    // Extraire les départements uniques des députés trouvés
    const matchingDeptCodes = [
        ...new Set(matchingDeputies.map((d) => d.value)),
    ];

    // Filtrer les départements (recherche normalisée)
    return departmentOptions.value.filter(
        (dept) =>
            normalizeText(dept.label).includes(search) ||
            matchingDeptCodes.includes(dept.value)
    );
});

const fetchDepartmentStats = async (code) => {
    if (!code) {
        departmentStats.value = null;
        return;
    }

    loading.value = true;
    try {
        const response = await axios.get(
            `/deputies/api/departements/${code}/stats`
        );
        departmentStats.value = response.data;
    } catch (error) {
        console.error("Erreur lors de la récupération des députés:", error);
    } finally {
        loading.value = false;
    }
};

// Auto-sélection du département si la recherche correspond exactement
watch(deputySearch, (newValue) => {
    // Mettre à jour l'URL avec les paramètres de recherche
    updateURL();

    if (!newValue) return;

    const search = normalizeText(newValue);

    // Chercher une correspondance exacte avec un code de département
    const exactCodeMatch = props.departments.find(
        (dept) => dept.code.toLowerCase() === newValue.toLowerCase().trim()
    );

    if (exactCodeMatch) {
        selectedDepartment.value = exactCodeMatch.code;
        return;
    }

    // Chercher une correspondance avec un nom de département
    const exactNameMatch = props.departments.find(
        (dept) => normalizeText(dept.name) === search.trim()
    );

    if (exactNameMatch) {
        selectedDepartment.value = exactNameMatch.code;
        return;
    }

    // Si un seul département correspond au filtre, le sélectionner automatiquement
    if (filteredDepartmentOptions.value.length === 1) {
        selectedDepartment.value = filteredDepartmentOptions.value[0].value;
    }
});

watch(selectedDepartment, (newValue) => {
    // Mettre à jour l'URL avec les paramètres de recherche
    updateURL();

    if (newValue) {
        fetchDepartmentStats(newValue);
    }
});

// Fonction pour mettre à jour l'URL sans recharger la page
const updateURL = () => {
    const params = new URLSearchParams();

    if (deputySearch.value) {
        params.set("search", deputySearch.value);
    }

    if (selectedDepartment.value) {
        params.set("department", selectedDepartment.value);
    }

    const newURL = params.toString()
        ? `${window.location.pathname}?${params.toString()}`
        : window.location.pathname;

    window.history.replaceState({}, "", newURL);
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
                                Consultez les informations des députés par
                                département
                            </p>
                        </div>
                    </div>
                    <Link href="/votes">
                        <Button variant="secondary"> Voir les scrutins </Button>
                    </Link>
                </div>
            </div>

            <!-- Department Selection -->
            <Card class="mb-8">
                <CardHeader>
                    <CardTitle>Rechercher des députés</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <!-- Recherche de député -->
                    <div>
                        <label class="text-sm font-medium mb-2 block">
                            Rechercher par nom de député ou département
                        </label>
                        <input
                            v-model="deputySearch"
                            type="text"
                            placeholder="Ex: Dupont, Paris, 75..."
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                        />
                        <p class="text-xs text-muted-foreground mt-1">
                            Tapez un nom de député, un nom de département ou un
                            numéro pour filtrer
                        </p>
                    </div>

                    <!-- Sélection du département -->
                    <div>
                        <label class="text-sm font-medium mb-2 block">
                            Sélectionner un département
                        </label>
                        <Combobox
                            v-model="selectedDepartment"
                            :options="filteredDepartmentOptions"
                            placeholder="Choisissez un département..."
                            class="w-full"
                        />
                        <p
                            v-if="filteredDepartmentOptions.length === 0"
                            class="text-xs text-amber-600 dark:text-amber-400 mt-1"
                        >
                            Aucun département ne correspond à votre recherche
                        </p>
                        <p v-else class="text-xs text-muted-foreground mt-1">
                            {{ filteredDepartmentOptions.length }} département{{
                                filteredDepartmentOptions.length > 1 ? "s" : ""
                            }}
                            disponible{{
                                filteredDepartmentOptions.length > 1 ? "s" : ""
                            }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Loading State -->
            <div v-if="loading" class="text-center py-12">
                <div
                    class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary"
                ></div>
                <p class="mt-4 text-muted-foreground">
                    Chargement des données...
                </p>
            </div>

            <!-- Stats Display -->
            <div v-else-if="departmentStats && !loading">
                <!-- Overview Card -->
                <Card class="mb-8">
                    <CardHeader>
                        <CardTitle
                            >{{ departmentStats.department_name }} ({{
                                departmentStats.department_code
                            }})</CardTitle
                        >
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold text-primary">
                            {{ departmentStats.total_deputies }} député{{
                                departmentStats.total_deputies > 1 ? "s" : ""
                            }}
                        </p>
                    </CardContent>
                </Card>

                <!-- Deputy Grid -->
                <DeputyGrid :deputies="departmentStats.deputies" />
            </div>

            <!-- Empty State -->
            <Card v-else-if="!selectedDepartment && !loading">
                <CardContent class="py-12 text-center">
                    <p class="text-muted-foreground text-lg">
                        Sélectionnez un département pour afficher les députés
                    </p>
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
