<script setup>
import PublicLayout from "@/Layouts/PublicLayout.vue";

defineProps({
    terms: String,
});
</script>

<template>
    <PublicLayout title="Conditions Générales d'Utilisation">
        <div class="container mx-auto px-4 max-w-4xl py-12">
            <div
                class="prose prose-lg dark:prose-invert max-w-none bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8"
                v-html="terms"
            />
        </div>
    </PublicLayout>
</template>
