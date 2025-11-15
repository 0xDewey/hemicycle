<script setup>
import { ref, onMounted, watch } from "vue";
import L from "leaflet";
import "leaflet/dist/leaflet.css";

const props = defineProps({
    selectedDepartment: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(["select-circonscription", "select-deputy"]);

const mapContainer = ref(null);
const foreignDeputies = ref([]);
const postalCodeSearch = ref("");
const searchError = ref("");
const isLoading = ref(true);
const isSearching = ref(false);
let map = null;
let geojsonLayer = null;

onMounted(async () => {
    // Initialiser la carte
    map = L.map(mapContainer.value).setView([46.603354, 1.888334], 6); // Centre de la France

    // Ajouter le fond de carte
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "© OpenStreetMap contributors",
        maxZoom: 18,
    }).addTo(map);

    // Charger les circonscriptions et les députés de l'étranger
    await loadCirconscriptions();
    await loadForeignDeputies();

    // Cacher le loading après le chargement initial
    isLoading.value = false;
});

watch(
    () => props.selectedDepartment,
    async (newDept) => {
        if (newDept && map) {
            await loadCirconscriptions(newDept);
        }
    }
);

const loadCirconscriptions = async (departmentCode = null) => {
    try {
        const url = "/circonscriptions/geojson";
        const response = await fetch(url);
        const geojson = await response.json();

        // Supprimer la couche précédente si elle existe
        if (geojsonLayer) {
            map.removeLayer(geojsonLayer);
        }

        // Filtrer par département si nécessaire
        let features = geojson.features;
        if (departmentCode) {
            features = features.filter(
                (f) => f.properties.code_departement === departmentCode
            );
        }

        // Ajouter la couche GeoJSON
        geojsonLayer = L.geoJSON(
            { type: "FeatureCollection", features },
            {
                style: (feature) => ({
                    fillColor: getColor(feature.properties.deputies_count),
                    weight: 2,
                    opacity: 1,
                    color: "#666",
                    fillOpacity: 0.6,
                }),
                onEachFeature: (feature, layer) => {
                    const props = feature.properties;

                    // Popup avec infos
                    let popupContent = `
                    <div class="p-2">
                        <h3 class="font-bold text-lg mb-2">${props.nom}</h3>
                        <p class="text-sm text-gray-600 mb-2">
                            Département: ${props.code_departement} - Circonscription ${props.numero}
                        </p>
                `;

                    if (props.deputies && props.deputies.length > 0) {
                        popupContent += '<div class="space-y-2">';
                        props.deputies.forEach((deputy) => {
                            popupContent += `
                            <div class="flex items-center gap-2 p-2 hover:bg-gray-100 rounded cursor-pointer" 
                                 onclick="window.selectDeputy(${deputy.id})">
                                ${
                                    deputy.photo
                                        ? `<img src="/storage/${deputy.photo}" class="w-8 h-8 rounded-full" />`
                                        : ""
                                }
                                <div>
                                    <p class="font-semibold text-sm">${
                                        deputy.full_name
                                    }</p>
                                    <p class="text-xs text-gray-500">${
                                        deputy.groupe_politique || "Sans parti"
                                    }</p>
                                </div>
                            </div>
                        `;
                        });
                        popupContent += "</div>";
                    } else {
                        popupContent +=
                            '<p class="text-sm text-gray-500">Aucun député actif</p>';
                    }

                    popupContent += "</div>";

                    layer.bindPopup(popupContent);

                    // Événements
                    layer.on({
                        mouseover: (e) => {
                            e.target.setStyle({
                                weight: 3,
                                fillOpacity: 0.8,
                            });
                        },
                        mouseout: (e) => {
                            geojsonLayer.resetStyle(e.target);
                        },
                        click: (e) => {
                            emit("select-circonscription", props);
                        },
                    });
                },
            }
        ).addTo(map);

        // Ajuster la vue sur les circonscriptions chargées
        if (geojsonLayer.getBounds().isValid()) {
            map.fitBounds(geojsonLayer.getBounds(), { padding: [50, 50] });
        }
    } catch (error) {
        console.error("Erreur lors du chargement des circonscriptions:", error);
    }
};

// Fonction globale pour sélectionner un député depuis la popup
window.selectDeputy = (deputyId) => {
    emit("select-deputy", deputyId);
};

const loadForeignDeputies = async () => {
    try {
        const response = await fetch("/api/deputies/foreign");
        const data = await response.json();
        foreignDeputies.value = data;
    } catch (error) {
        console.error(
            "Erreur lors du chargement des députés de l'étranger:",
            error
        );
    }
};

const getColor = (deputiesCount) => {
    return deputiesCount > 0 ? "#3b82f6" : "#e5e7eb";
};

const resetView = async () => {
    emit("select-circonscription", null);
    await loadCirconscriptions();

    // Supprimer le marqueur du code postal si existant
    if (window.postalCodeMarker && map) {
        map.removeLayer(window.postalCodeMarker);
        window.postalCodeMarker = null;
    }

    // Réinitialiser la vue de la carte sur la France
    if (map) {
        map.setView([46.603354, 1.888334], 6);
    }
};

const searchByPostalCode = async () => {
    searchError.value = "";

    if (!postalCodeSearch.value || postalCodeSearch.value.length < 2) {
        searchError.value = "Veuillez entrer un code postal valide";
        return;
    }

    isSearching.value = true;

    try {
        const response = await fetch(
            `/api/postal-code/search?postal_code=${postalCodeSearch.value}`
        );

        if (!response.ok) {
            const error = await response.json();
            searchError.value = error.error || "Code postal non trouvé";
            return;
        }

        const data = await response.json();

        // Si on a trouvé une circonscription précise (méthode geocoding)
        if (data.method === "geocoding" && data.circonscription) {
            const circ = data.circonscription;

            // Zoomer sur le département
            emit("select-circonscription", {
                code_departement: circ.code_departement,
            });
            await loadCirconscriptions(circ.code_departement);

            // Ajouter un marqueur sur la position exacte du code postal
            if (data.coordinates && map) {
                // Supprimer les anciens marqueurs si existants
                if (window.postalCodeMarker) {
                    map.removeLayer(window.postalCodeMarker);
                }

                // Créer un marqueur personnalisé
                const marker = L.marker(
                    [data.coordinates.lat, data.coordinates.lon],
                    {
                        icon: L.divIcon({
                            className: "custom-marker",
                            html: `<div style="background-color: #ef4444; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>`,
                            iconSize: [20, 20],
                            iconAnchor: [10, 10],
                        }),
                    }
                ).addTo(map);

                // Construire le contenu de la popup avec les députés
                let popupContent = `
                    <div style="padding: 8px; min-width: 200px;">
                        <div style="font-weight: bold; margin-bottom: 8px; font-size: 14px;">
                            Code postal : ${postalCodeSearch.value}
                        </div>
                        <div style="font-weight: 600; margin-bottom: 8px; color: #1f2937;">
                            ${circ.nom}
                        </div>
                `;

                if (circ.deputies_count > 0 && circ.deputies) {
                    popupContent +=
                        '<div style="border-top: 1px solid #e5e7eb; padding-top: 8px; margin-top: 8px;">';
                    circ.deputies.forEach((deputy) => {
                        popupContent += `
                            <div style="margin-bottom: 8px; padding: 8px; background: #f9fafb; border-radius: 6px; cursor: pointer; transition: background 0.2s; display: flex; align-items: center; gap: 8px;"
                                 onmouseover="this.style.background='#f3f4f6'"
                                 onmouseout="this.style.background='#f9fafb'"
                                 onclick="window.selectDeputy(${deputy.id})">
                                ${
                                    deputy.photo
                                        ? `<img src="/storage/${deputy.photo}" 
                                          style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #e5e7eb;"
                                          onerror="this.style.display='none'">`
                                        : `<div style="width: 40px; height: 40px; border-radius: 50%; background: #e5e7eb; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px; color: #6b7280;">
                                        ${deputy.prenom[0]}${deputy.nom[0]}
                                    </div>`
                                }
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-weight: 600; font-size: 13px; color: #1f2937; margin-bottom: 2px;">
                                        ${deputy.prenom} ${deputy.nom}
                                    </div>
                                    ${
                                        deputy.groupe_politique
                                            ? `<div style="font-size: 11px; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${deputy.groupe_politique}</div>`
                                            : ""
                                    }
                                </div>
                            </div>
                        `;
                    });
                    popupContent += "</div>";
                } else {
                    popupContent +=
                        '<div style="color: #9ca3af; font-style: italic; font-size: 12px; margin-top: 8px;">Aucun député actif</div>';
                }

                popupContent += "</div>";

                marker.bindPopup(popupContent).openPopup();

                // Centrer la carte sur le marqueur avec un bon niveau de zoom
                map.setView([data.coordinates.lat, data.coordinates.lon], 10);

                // Sauvegarder le marqueur pour pouvoir le supprimer plus tard
                window.postalCodeMarker = marker;
            }
        }
        // Sinon, utiliser la méthode par département (fallback)
        else if (data.department_code) {
            emit("select-circonscription", {
                code_departement: data.department_code,
            });
            await loadCirconscriptions(data.department_code);
        }
    } catch (error) {
        console.error("Erreur lors de la recherche par code postal:", error);
        searchError.value = "Erreur lors de la recherche";
    } finally {
        isSearching.value = false;
    }
};
</script>

<template>
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        <!-- Carte principale -->
        <div class="lg:col-span-3 relative">
            <!-- Barre de recherche par code postal -->
            <div class="absolute top-4 left-4 right-4 z-[1001] max-w-md">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-3">
                    <div class="flex gap-2">
                        <input
                            v-model="postalCodeSearch"
                            type="text"
                            placeholder="Rechercher par code postal..."
                            @keyup.enter="searchByPostalCode"
                            class="flex-1 px-3 py-2 text-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                        <button
                            @click="searchByPostalCode"
                            :disabled="isSearching"
                            class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-md text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                        >
                            <svg
                                v-if="isSearching"
                                class="animate-spin h-4 w-4"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                ></path>
                            </svg>
                            <svg
                                v-else
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <circle cx="11" cy="11" r="8" />
                                <path d="m21 21-4.35-4.35" />
                            </svg>
                        </button>
                    </div>
                    <p v-if="searchError" class="text-xs text-red-500 mt-1">
                        {{ searchError }}
                    </p>
                </div>
            </div>

            <div
                ref="mapContainer"
                class="w-full h-[600px] rounded-lg border shadow-lg"
            ></div>

            <!-- Loading overlay pour la carte -->
            <div
                v-if="isLoading"
                class="absolute inset-0 bg-white dark:bg-gray-900 bg-opacity-90 dark:bg-opacity-90 rounded-lg flex items-center justify-center z-[999]"
            >
                <div class="text-center">
                    <svg
                        class="animate-spin h-12 w-12 text-blue-500 mx-auto mb-4"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>
                    <p class="text-gray-600 dark:text-gray-300 font-medium">
                        Chargement de la carte...
                    </p>
                </div>
            </div>

            <!-- Bouton de retour -->
            <button
                v-if="selectedDepartment"
                @click="resetView"
                class="absolute bottom-4 left-4 bg-white dark:bg-gray-800 p-2 rounded-lg shadow-lg z-[1000] hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center gap-2"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path d="M19 12H5M12 19l-7-7 7-7" />
                </svg>
                <span class="text-sm font-medium">Voir toute la France</span>
            </button>

            <div
                class="absolute top-4 right-4 bg-white dark:bg-gray-800 p-3 rounded-lg shadow-lg z-[1000]"
            >
                <h4 class="font-semibold text-sm mb-2">Légende</h4>
                <div class="space-y-1 text-xs">
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 bg-blue-500 rounded"></div>
                        <span>Avec député(s)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 bg-gray-300 rounded"></div>
                        <span>Sans député actif</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panneau Français de l'étranger -->
        <div class="lg:col-span-1">
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-4 h-[600px] overflow-y-auto"
            >
                <h3 class="font-semibold text-lg mb-4 flex items-center gap-2">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <circle cx="12" cy="12" r="10"></circle>
                        <path
                            d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"
                        ></path>
                        <path d="M2 12h20"></path>
                    </svg>
                    Français de l'étranger
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    11 circonscriptions représentant les Français établis hors
                    de France
                </p>

                <!-- Loading state -->
                <div
                    v-if="isLoading"
                    class="flex items-center justify-center py-12"
                >
                    <svg
                        class="animate-spin h-8 w-8 text-blue-500"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>
                </div>

                <!-- Liste des députés -->
                <div v-else class="space-y-3">
                    <div
                        v-for="deputy in foreignDeputies"
                        :key="deputy.id"
                        @click="emit('select-deputy', deputy.id)"
                        class="p-3 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors"
                    >
                        <div class="flex items-start gap-3">
                            <img
                                v-if="deputy.photo"
                                :src="`/storage/${deputy.photo}`"
                                :alt="`${deputy.prenom} ${deputy.nom}`"
                                class="w-12 h-12 rounded-full object-cover border-2"
                                @error="
                                    (e) => (e.target.style.display = 'none')
                                "
                            />
                            <div
                                v-else
                                class="w-12 h-12 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center font-semibold text-sm"
                            >
                                {{ deputy.prenom[0] }}{{ deputy.nom[0] }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-sm truncate">
                                    {{ deputy.prenom }} {{ deputy.nom }}
                                </div>
                                <div
                                    class="text-xs text-gray-600 dark:text-gray-400"
                                >
                                    {{ deputy.circonscription }}ème
                                    circonscription
                                </div>
                                <div
                                    v-if="deputy.groupe_politique"
                                    class="text-xs text-gray-500 dark:text-gray-400 mt-1"
                                >
                                    {{ deputy.groupe_politique }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Fix pour les icônes Leaflet manquantes */
:deep(.leaflet-container) {
    font-family: inherit;
}
</style>
