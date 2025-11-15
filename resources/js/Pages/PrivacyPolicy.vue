<script setup>
import PublicLayout from "@/Layouts/PublicLayout.vue";

defineProps({
    policy: String,
});
</script>

<template>
    <PublicLayout title="Politique de Confidentialité">
        <div class="container mx-auto px-4 max-w-4xl py-12">
            <div
                class="prose prose-lg dark:prose-invert max-w-none bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8"
                v-html="policy"
            />
        </div>
    </PublicLayout>
</template>
