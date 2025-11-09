import { ref, onMounted, watch } from "vue";

export function useTheme() {
    const theme = ref("light");

    const setTheme = (newTheme) => {
        theme.value = newTheme;

        if (newTheme === "dark") {
            document.documentElement.classList.add("dark");
            localStorage.setItem("theme", "dark");
        } else {
            document.documentElement.classList.remove("dark");
            localStorage.setItem("theme", "light");
        }
    };

    const toggleTheme = () => {
        setTheme(theme.value === "light" ? "dark" : "light");
    };

    onMounted(() => {
        // Récupérer le thème sauvegardé ou utiliser la préférence système
        const savedTheme = localStorage.getItem("theme");
        const prefersDark = window.matchMedia(
            "(prefers-color-scheme: dark)"
        ).matches;

        const initialTheme = savedTheme || (prefersDark ? "dark" : "light");
        setTheme(initialTheme);
    });

    return {
        theme,
        setTheme,
        toggleTheme,
    };
}
