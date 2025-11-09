<script setup>
import { Link } from "@inertiajs/vue3";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import CardFooter from "@/Components/ui/CardFooter.vue";
import { Eye } from "lucide-vue-next";

defineProps({
    deputies: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <div>
        <h2 class="text-2xl font-bold mb-4">Liste des Députés</h2>

        <div v-if="deputies.length === 0" class="text-center py-12">
            <p class="text-muted-foreground">
                Aucun député trouvé pour ce département
            </p>
        </div>

        <div
            v-else
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
        >
            <Card
                v-for="deputy in deputies"
                :key="deputy.id"
                class="hover:shadow-lg transition-shadow"
            >
                <CardHeader>
                    <CardTitle class="text-lg">
                        {{ deputy.firstname }} {{ deputy.name }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-2">
                        <div v-if="deputy.constituency" class="text-sm">
                            <span class="font-medium text-muted-foreground"
                                >Circonscription :</span
                            >
                            <p class="text-foreground">
                                {{ deputy.constituency }}
                            </p>
                        </div>
                    </div>
                </CardContent>
                <CardFooter>
                    <Link
                        :href="`/deputies/${deputy.id}/votes`"
                        class="inline-flex items-center gap-2 text-sm text-primary hover:underline"
                    >
                        <Eye class="h-4 w-4" />
                        Voir les votes
                    </Link>
                </CardFooter>
            </Card>
        </div>
    </div>
</template>
