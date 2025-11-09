<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { Check, ChevronsUpDown, X } from "lucide-vue-next";

const props = defineProps({
    modelValue: {
        type: String,
        default: "",
    },
    options: {
        type: Array,
        required: true,
    },
    placeholder: {
        type: String,
        default: "Sélectionner...",
    },
});

const emit = defineEmits(["update:modelValue"]);

const open = ref(false);
const searchTerm = ref("");
const comboboxRef = ref(null);

const filteredOptions = computed(() => {
    if (!searchTerm.value) {
        return props.options;
    }

    const search = searchTerm.value.toLowerCase();
    return props.options.filter(
        (option) =>
            option.label.toLowerCase().includes(search) ||
            option.value.toLowerCase().includes(search)
    );
});

const selectedOption = computed(() => {
    return props.options.find((opt) => opt.value === props.modelValue);
});

const handleSelect = (value) => {
    emit("update:modelValue", value);
    open.value = false;
    searchTerm.value = "";
};

const clearSelection = () => {
    emit("update:modelValue", "");
    searchTerm.value = "";
};

const handleClickOutside = (event) => {
    if (comboboxRef.value && !comboboxRef.value.contains(event.target)) {
        open.value = false;
    }
};

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener("click", handleClickOutside);
});
</script>

<template>
    <div ref="comboboxRef" class="relative w-full">
        <!-- Input Field -->
        <div class="relative">
            <input
                v-model="searchTerm"
                type="text"
                :placeholder="
                    selectedOption ? selectedOption.label : placeholder
                "
                class="flex h-10 w-full rounded-md border border-input bg-background pl-3 pr-20 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                @focus="open = true"
                @input="open = true"
            />

            <div
                class="absolute right-0 top-0 h-full flex items-center gap-1 pr-2"
            >
                <!-- Clear button -->
                <button
                    v-if="modelValue"
                    type="button"
                    @click.stop="clearSelection"
                    class="h-6 w-6 flex items-center justify-center rounded hover:bg-muted transition-colors"
                >
                    <X class="h-3 w-3 opacity-50 hover:opacity-100" />
                </button>

                <!-- Dropdown icon -->
                <button
                    type="button"
                    @click.stop="open = !open"
                    class="h-6 w-6 flex items-center justify-center"
                >
                    <ChevronsUpDown class="h-4 w-4 opacity-50" />
                </button>
            </div>
        </div>

        <!-- Dropdown List -->
        <div
            v-show="open"
            class="absolute z-50 w-full mt-1 max-h-80 overflow-y-auto rounded-md border bg-popover text-popover-foreground shadow-md"
        >
            <!-- Empty state -->
            <div
                v-if="filteredOptions.length === 0"
                class="py-6 text-center text-sm text-muted-foreground"
            >
                Aucun résultat trouvé
            </div>

            <!-- Options list -->
            <div v-else class="p-1">
                <button
                    v-for="option in filteredOptions"
                    :key="option.value"
                    type="button"
                    class="relative flex w-full cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground transition-colors text-left"
                    :class="{
                        'bg-accent text-accent-foreground':
                            modelValue === option.value,
                    }"
                    @click="handleSelect(option.value)"
                >
                    <Check
                        class="mr-2 h-4 w-4 flex-shrink-0"
                        :class="
                            modelValue === option.value
                                ? 'opacity-100'
                                : 'opacity-0'
                        "
                    />
                    <span>{{ option.label }}</span>
                </button>
            </div>
        </div>
    </div>
</template>
