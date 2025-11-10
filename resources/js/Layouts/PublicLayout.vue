<script setup>
import { Head } from "@inertiajs/vue3";
import Header from "@/Components/Header.vue";
import Footer from "@/Components/Footer.vue";

defineProps({
    title: {
        type: String,
        default: "HémiCycle",
    },
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
    <div
        class="min-h-screen flex flex-col bg-gradient-to-br from-slate-50 to-slate-100 dark:from-gray-900 dark:to-gray-800"
    >
        <Head :title="title" />

        <Header :can-login="canLogin" :can-register="canRegister" />

        <main class="flex-1">
            <slot />
        </main>

        <Footer />
    </div>
</template>
