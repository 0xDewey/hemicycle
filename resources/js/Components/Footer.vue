<script setup>
import { Link } from "@inertiajs/vue3";
import { Building2, Github, Mail, ExternalLink } from "lucide-vue-next";

const currentYear = new Date().getFullYear();
</script>

<template>
    <footer
        class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-auto"
    >
        <div class="max-w-7xl mx-auto px-6 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- À propos -->
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center gap-3 mb-4">
                        <Building2
                            class="h-8 w-8 text-blue-600 dark:text-blue-400"
                        />
                        <div>
                            <h3
                                class="text-lg font-bold text-gray-900 dark:text-white"
                            >
                                Visibilité des Députés
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                17e législature
                            </p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                        Plateforme de consultation des données de l'Assemblée
                        Nationale. Explorez les députés, leurs votes et leurs
                        groupes politiques.
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Données issues de
                        <a
                            href="https://data.assemblee-nationale.fr"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-1"
                        >
                            data.assemblee-nationale.fr
                            <ExternalLink class="h-3 w-3" />
                        </a>
                    </p>
                </div>

                <!-- Navigation -->
                <div>
                    <h4
                        class="font-semibold text-gray-900 dark:text-white mb-4"
                    >
                        Navigation
                    </h4>
                    <ul class="space-y-2">
                        <li>
                            <Link
                                href="/"
                                class="text-sm text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                            >
                                Accueil
                            </Link>
                        </li>
                        <li>
                            <Link
                                href="/deputies"
                                class="text-sm text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                            >
                                Députés
                            </Link>
                        </li>
                        <li>
                            <Link
                                href="/votes"
                                class="text-sm text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                            >
                                Scrutins
                            </Link>
                        </li>
                    </ul>
                </div>

                <!-- Ressources -->
                <div>
                    <h4
                        class="font-semibold text-gray-900 dark:text-white mb-4"
                    >
                        Ressources
                    </h4>
                    <ul class="space-y-2">
                        <li>
                            <a
                                href="https://www.assemblee-nationale.fr"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-sm text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors inline-flex items-center gap-1"
                            >
                                Assemblée Nationale
                                <ExternalLink class="h-3 w-3" />
                            </a>
                        </li>
                        <li>
                            <a
                                href="https://data.assemblee-nationale.fr"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-sm text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors inline-flex items-center gap-1"
                            >
                                Open Data AN
                                <ExternalLink class="h-3 w-3" />
                            </a>
                        </li>
                        <li>
                            <a
                                href="https://www.data.gouv.fr/fr/datasets/deputes-actifs"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-sm text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors inline-flex items-center gap-1"
                            >
                                Data.gouv.fr
                                <ExternalLink class="h-3 w-3" />
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Séparateur -->
            <div
                class="border-t border-gray-200 dark:border-gray-700 mt-8 pt-8"
            >
                <div
                    class="flex flex-col md:flex-row justify-between items-center gap-4"
                >
                    <p
                        class="text-sm text-gray-500 dark:text-gray-400 text-center md:text-left"
                    >
                        © {{ currentYear }} HémiCycle. Projet non officiel.
                    </p>
                    <div class="flex items-center gap-4">
                        <a
                            href="mailto:contact@example.com"
                            class="text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                            title="Contact"
                        >
                            <Mail class="h-5 w-5" />
                        </a>
                        <a
                            href="https://github.com"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                            title="GitHub"
                        >
                            <Github class="h-5 w-5" />
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</template>
