<script setup>
import { ref, computed } from "vue";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardContent from "@/Components/ui/CardContent.vue";

const props = defineProps({
    deputies: {
        type: Array,
        required: true,
    },
});

const sortBy = ref("participation_rate");
const sortOrder = ref("desc");

const sortedDeputies = computed(() => {
    const sorted = [...props.deputies].sort((a, b) => {
        const aVal = a[sortBy.value];
        const bVal = b[sortBy.value];

        if (sortOrder.value === "asc") {
            return aVal - bVal;
        } else {
            return bVal - aVal;
        }
    });

    return sorted;
});

const changeSortBy = (field) => {
    if (sortBy.value === field) {
        sortOrder.value = sortOrder.value === "asc" ? "desc" : "asc";
    } else {
        sortBy.value = field;
        sortOrder.value = "desc";
    }
};

const formatPercent = (value) => {
    return `${value.toFixed(1)}%`;
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Liste des Députés</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border">
                            <th class="text-left p-3 font-semibold">Nom</th>
                            <th class="text-left p-3 font-semibold">
                                Circonscription
                            </th>
                            <th
                                class="text-right p-3 font-semibold cursor-pointer hover:bg-muted"
                                @click="changeSortBy('participation_rate')"
                            >
                                Participation
                                <span v-if="sortBy === 'participation_rate'">
                                    {{ sortOrder === "asc" ? "↑" : "↓" }}
                                </span>
                            </th>
                            <th
                                class="text-right p-3 font-semibold cursor-pointer hover:bg-muted"
                                @click="changeSortBy('loyalty_rate')"
                            >
                                Loyauté
                                <span v-if="sortBy === 'loyalty_rate'">
                                    {{ sortOrder === "asc" ? "↑" : "↓" }}
                                </span>
                            </th>
                            <th
                                class="text-right p-3 font-semibold cursor-pointer hover:bg-muted"
                                @click="changeSortBy('majority_proximity')"
                            >
                                Proximité Majorité
                                <span v-if="sortBy === 'majority_proximity'">
                                    {{ sortOrder === "asc" ? "↑" : "↓" }}
                                </span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="deputy in sortedDeputies"
                            :key="deputy.id"
                            class="border-b border-border hover:bg-muted/50 transition-colors"
                        >
                            <td class="p-3">
                                <div class="font-medium">
                                    {{ deputy.firstname }} {{ deputy.name }}
                                </div>
                            </td>
                            <td class="p-3 text-muted-foreground">
                                {{ deputy.constituency }}
                            </td>
                            <td class="p-3 text-right">
                                <span
                                    class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium"
                                    :class="{
                                        'bg-green-100 text-green-800':
                                            deputy.participation_rate >= 90,
                                        'bg-yellow-100 text-yellow-800':
                                            deputy.participation_rate >= 70 &&
                                            deputy.participation_rate < 90,
                                        'bg-red-100 text-red-800':
                                            deputy.participation_rate < 70,
                                    }"
                                >
                                    {{
                                        formatPercent(deputy.participation_rate)
                                    }}
                                </span>
                            </td>
                            <td class="p-3 text-right">
                                {{ formatPercent(deputy.loyalty_rate) }}
                            </td>
                            <td class="p-3 text-right">
                                {{ formatPercent(deputy.majority_proximity) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </CardContent>
    </Card>
</template>
