<script setup>
import { Link } from "@inertiajs/vue3";
import { Building2, Users, Vote } from "lucide-vue-next";
import ThemeToggle from "@/Components/ThemeToggle.vue";

defineProps({
    canLogin: {
        type: Boolean,
        default: false,
    },
    canRegister: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <header
        class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-50 backdrop-blur-sm bg-white/90 dark:bg-gray-800/90"
    >
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex items-center justify-between h-16">
                <!-- Logo et titre -->
                <Link
                    href="/"
                    class="flex items-center gap-3 hover:opacity-80 transition-opacity"
                >
                    <Building2
                        class="h-8 w-8 text-blue-600 dark:text-blue-400"
                    />
                    <div>
                        <h1
                            class="text-lg font-bold text-gray-900 dark:text-white"
                        >
                            Visibilité des Députés
                        </h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            17e législature
                        </p>
                    </div>
                </Link>

                <!-- Navigation principale -->
                <nav class="hidden md:flex items-center gap-6">
                    <Link
                        href="/deputies"
                        class="flex items-center gap-2 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                    >
                        <Users class="h-4 w-4" />
                        <span class="font-medium">Députés</span>
                    </Link>
                    <Link
                        href="/votes"
                        class="flex items-center gap-2 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                    >
                        <Vote class="h-4 w-4" />
                        <span class="font-medium">Scrutins</span>
                    </Link>
                </nav>

                <!-- Actions -->
                <div class="flex items-center gap-2">
                    <ThemeToggle />

                    <template v-if="canLogin">
                        <div
                            v-if="$page.props.auth.user"
                            class="hidden sm:flex items-center gap-2"
                        >
                            <Link
                                :href="route('dashboard')"
                                class="rounded-md px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            >
                                Dashboard
                            </Link>
                        </div>

                        <div v-else class="hidden sm:flex items-center gap-2">
                            <Link
                                :href="route('login')"
                                class="rounded-md px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            >
                                Connexion
                            </Link>

                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="rounded-md px-3 py-2 text-sm bg-blue-600 text-white hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 transition-colors"
                            >
                                Inscription
                            </Link>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Navigation mobile -->
            <div class="md:hidden flex items-center gap-4 pb-4">
                <Link
                    href="/deputies"
                    class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                >
                    <Users class="h-4 w-4" />
                    <span>Députés</span>
                </Link>
                <Link
                    href="/votes"
                    class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                >
                    <Vote class="h-4 w-4" />
                    <span>Scrutins</span>
                </Link>
            </div>
        </div>
    </header>
</template>
