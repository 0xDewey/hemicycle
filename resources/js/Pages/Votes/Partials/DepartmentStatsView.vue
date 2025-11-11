<script setup>
import { computed, ref } from "vue";
import Card from "@/Components/ui/Card.vue";
import CardHeader from "@/Components/ui/CardHeader.vue";
import CardTitle from "@/Components/ui/CardTitle.vue";
import CardContent from "@/Components/ui/CardContent.vue";
import { Bar } from "vue-chartjs";

const props = defineProps({
    departmentStats: {
        type: Array,
        required: true,
    },
    selectedPosition: {
        type: String,
        default: "all",
    },
    departments: {
        type: Array,
        required: true,
    },
});

const selectedDepartment = ref(null);

const getDepartmentName = (code) => {
    const dept = props.departments.find((d) => d.code === code);
    return dept?.name || code;
};

const allDepartments = computed(() => {
    return props.departments
        .filter((dept) =>
            props.departmentStats.some((d) => d.department === dept.code)
        )
        .sort((a, b) => a.name.localeCompare(b.name, "fr"));
});

const departmentVoteChartData = computed(() => {
    if (!selectedDepartment.value) {
        return { labels: [], datasets: [] };
    }

    // Show single department
    const dept = props.departmentStats.find(
        (d) => d.department === selectedDepartment.value
    );

    if (!dept) {
        return { labels: [], datasets: [] };
    }

    return {
        labels: ["Pour", "Contre", "Abstention", "Non votant", "Absents"],
        datasets: [
            {
                label: getDepartmentName(selectedDepartment.value),
                data: [
                    dept.pour,
                    dept.contre,
                    dept.abstention,
                    dept.non_votant,
                    dept.absents,
                ],
                backgroundColor: [
                    "rgba(34, 197, 94, 0.8)",
                    "rgba(239, 68, 68, 0.8)",
                    "rgba(156, 163, 175, 0.8)",
                    "rgba(251, 146, 60, 0.8)",
                    "rgba(168, 85, 247, 0.8)",
                ],
            },
        ],
    };
});

const departmentBarChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: "x",
    scales: {
        x: {
            stacked: true,
            beginAtZero: true,
            ticks: {
                stepSize: 1,
            },
        },
        y: {
            stacked: true,
            beginAtZero: true,
            ticks: {
                stepSize: 1,
            },
        },
    },
    plugins: {
        legend: {
            position: "top",
            labels: {
                padding: 15,
                font: {
                    size: 12,
                },
            },
        },
        tooltip: {
            callbacks: {
                label: function (context) {
                    const label = context.dataset.label || "";
                    const value = context.parsed.y;
                    return `${label}: ${value} député${value > 1 ? "s" : ""}`;
                },
            },
        },
    },
}));

const filteredDepartmentStats = computed(() => {
    if (props.selectedPosition === "all") {
        return props.departmentStats;
    }

    return props.departmentStats.filter((dept) => {
        const count = dept[props.selectedPosition] || 0;
        return count > 0;
    });
});
</script>

<template>
    <div class="space-y-6">
        <!-- Department Selection -->
        <Card>
            <CardHeader>
                <CardTitle>Sélectionner un département</CardTitle>
            </CardHeader>
            <CardContent>
                <select
                    v-model="selectedDepartment"
                    class="w-full px-4 py-2 rounded-md border border-input bg-background"
                >
                    <option :value="null">Sélectionner un département</option>
                    <option
                        v-for="dept in allDepartments"
                        :key="dept.code"
                        :value="dept.code"
                    >
                        {{ dept.name }}
                    </option>
                </select>
            </CardContent>
        </Card>

        <!-- Department Vote Distribution Chart -->
        <Card v-if="selectedDepartment">
            <CardHeader>
                <CardTitle class="text-center">
                    {{ getDepartmentName(selectedDepartment) }}
                </CardTitle>
                <p class="text-sm text-muted-foreground text-center mt-2">
                    Répartition des votes
                </p>
            </CardHeader>
            <CardContent>
                <div style="height: 400px">
                    <Bar
                        :data="departmentVoteChartData"
                        :options="departmentBarChartOptions"
                    />
                </div>
            </CardContent>
        </Card>

        <!-- Department Stats Details -->
        <Card v-if="selectedPosition === 'all'">
            <CardHeader>
                <CardTitle>Détails par département</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-3">
                    <div
                        v-for="dept in filteredDepartmentStats"
                        :key="dept.department"
                        class="p-4 border rounded-lg hover:bg-accent/50 transition-colors"
                    >
                        <div class="flex justify-between items-start mb-2">
                            <p class="font-medium text-lg">
                                {{ getDepartmentName(dept.department) }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ dept.total_deputies }} député{{
                                    dept.total_deputies > 1 ? "s" : ""
                                }}
                            </p>
                        </div>
                        <div
                            class="grid grid-cols-2 md:grid-cols-5 gap-2 text-sm"
                        >
                            <div class="text-green-600 dark:text-green-400">
                                Pour: {{ dept.pour }}
                            </div>
                            <div class="text-red-600 dark:text-red-400">
                                Contre: {{ dept.contre }}
                            </div>
                            <div class="text-gray-600 dark:text-gray-400">
                                Abstention: {{ dept.abstention }}
                            </div>
                            <div class="text-orange-600 dark:text-orange-400">
                                Non votant: {{ dept.non_votant }}
                                <span
                                    v-if="
                                        dept.non_votant_pan > 0 ||
                                        dept.non_votant_gov > 0
                                    "
                                    class="text-xs text-muted-foreground block"
                                >
                                    <span v-if="dept.non_votant_pan > 0"
                                        >PAN: {{ dept.non_votant_pan }}</span
                                    >
                                    <span v-if="dept.non_votant_gov > 0"
                                        >GOV: {{ dept.non_votant_gov }}</span
                                    >
                                </span>
                            </div>
                            <div class="text-purple-600 dark:text-purple-400">
                                Absents: {{ dept.absents }}
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Single Position Details -->
        <Card v-else>
            <CardHeader>
                <CardTitle>
                    {{
                        selectedPosition === "pour"
                            ? "Pour"
                            : selectedPosition === "contre"
                            ? "Contre"
                            : selectedPosition === "abstention"
                            ? "Abstention"
                            : selectedPosition === "non_votant"
                            ? "Non votant"
                            : "Absents"
                    }}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-3">
                    <div
                        v-for="dept in filteredDepartmentStats"
                        :key="dept.department"
                        class="flex items-center justify-between p-4 border rounded-lg hover:bg-accent/50 transition-colors"
                    >
                        <div>
                            <p class="font-medium">
                                {{ getDepartmentName(dept.department) }}
                            </p>
                            <p
                                v-if="selectedPosition === 'absents'"
                                class="text-xs text-muted-foreground"
                            >
                                sur {{ dept.total_deputies }} député{{
                                    dept.total_deputies > 1 ? "s" : ""
                                }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold">
                                {{ dept[selectedPosition] }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                député{{
                                    dept[selectedPosition] > 1 ? "s" : ""
                                }}
                            </p>
                            <div
                                v-if="
                                    selectedPosition === 'non_votant' &&
                                    (dept.non_votant_pan > 0 ||
                                        dept.non_votant_gov > 0 ||
                                        dept.non_votant_autres > 0)
                                "
                                class="mt-2 text-xs text-muted-foreground space-y-1"
                            >
                                <p v-if="dept.non_votant_pan > 0">
                                    Président AN: {{ dept.non_votant_pan }}
                                </p>
                                <p v-if="dept.non_votant_gov > 0">
                                    Gouvernement: {{ dept.non_votant_gov }}
                                </p>
                                <p v-if="dept.non_votant_autres > 0">
                                    Autres: {{ dept.non_votant_autres }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
