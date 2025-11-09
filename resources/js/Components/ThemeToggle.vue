<script setup>
import { Moon, Sun } from "lucide-vue-next";
import { useTheme } from "@/composables/useTheme";
import Button from "@/Components/ui/Button.vue";

const { theme, toggleTheme } = useTheme();
</script>

<template>
    <Button
        variant="ghost"
        size="icon"
        @click="toggleTheme"
        class="rounded-full"
        :title="
            theme === 'light' ? 'Passer en mode sombre' : 'Passer en mode clair'
        "
    >
        <Sun v-if="theme === 'light'" class="h-5 w-5" />
        <Moon v-else class="h-5 w-5" />
    </Button>
</template>
