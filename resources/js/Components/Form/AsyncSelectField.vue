<template>
    <div class="relative">
        <label
            v-if="label"
            :for="$attrs.id"
            class="block text-sm font-medium mb-1"
            >{{ label }}</label
        >
        <div class="relative">
            <input
                :id="$attrs.id"
                type="text"
                v-model="searchQuery"
                @input="debouncedSearch"
                @focus="handleFocus"
                class="form w-full"
                :class="{ 'border-danger': feedback, 'bg-gray-100': disabled }"
                :placeholder="placeholder"
                :disabled="disabled"
                autocomplete="off"
                v-bind="$attrs"
            />

            <div
                v-if="isLoading"
                class="absolute right-3 top-1/2 -translate-y-1/2"
            >
                <svg
                    class="animate-spin h-4 w-4 text-gray-500"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    ></circle>
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                </svg>
            </div>

            <!-- Dropdown Results -->
            <div
                v-if="
                    showDropdown &&
                    (results.length > 0 ||
                        (searchQuery.length >= minChars && !isLoading))
                "
                class="absolute z-50 w-full bg-white border border-gray-200 rounded shadow-lg mt-1 max-h-60 overflow-y-auto"
            >
                <template v-if="results.length > 0">
                    <div
                        v-for="(item, index) in results"
                        :key="index"
                        @click="selectItem(item)"
                        class="p-2 border-b last:border-b-0 cursor-pointer hover:bg-gray-50 transition-colors"
                    >
                        <slot name="option" :item="item">
                            <!-- Default option layout if slot is not provided -->
                            <div class="font-semibold text-sm">
                                {{ getLabel(item) }}
                            </div>
                        </slot>
                    </div>
                </template>
                <template
                    v-else-if="!isLoading && searchQuery.length >= minChars"
                >
                    <div class="p-3 text-center text-sm text-gray-500">
                        Pencarian tidak ditemukan.
                    </div>
                </template>
            </div>
        </div>
        <span v-if="feedback" class="text-danger text-xs mt-1 block">{{
            feedback
        }}</span>

        <!-- Backdrop to close dropdown when clicking outside -->
        <div
            v-if="showDropdown"
            @click="closeDropdown"
            class="fixed inset-0 z-40 bg-transparent"
        ></div>
    </div>
</template>

<script setup>
import { ref, watch, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    label: { type: String, default: '' },
    placeholder: { type: String, default: 'Ketik untuk mencari...' },
    disabled: { type: Boolean, default: false },
    feedback: { type: String, default: '' },

    // API Configuration
    apiUrl: { type: String, required: true },
    apiParams: { type: Object, default: () => ({}) },
    searchParamName: { type: String, default: 'query' },

    // Behavior
    minChars: { type: Number, default: 3 },
    debounceTime: { type: Number, default: 300 },

    // Fallback display if not using slot
    optionLabel: { type: [String, Function], default: 'name' },
});

const emit = defineEmits(['select']);

const searchQuery = ref('');
const results = ref([]);
const showDropdown = ref(false);
const isLoading = ref(false);
let searchTimeout = null;

const getLabel = (item) => {
    if (typeof props.optionLabel === 'function') {
        return props.optionLabel(item);
    }
    return item[props.optionLabel] || 'Unknown';
};

const handleFocus = () => {
    if (results.value.length > 0) {
        showDropdown.value = true;
    }
};

const closeDropdown = () => {
    showDropdown.value = false;
};

const selectItem = (item) => {
    emit('select', item);
    showDropdown.value = false;
    searchQuery.value = ''; // Reset input after selection
    results.value = [];
};

const debouncedSearch = () => {
    clearTimeout(searchTimeout);

    if (searchQuery.value.length >= props.minChars) {
        isLoading.value = true;
        showDropdown.value = true;

        searchTimeout = setTimeout(() => {
            fetchResults();
        }, props.debounceTime);
    } else {
        results.value = [];
        showDropdown.value = false;
    }
};

const fetchResults = async () => {
    try {
        const params = {
            ...props.apiParams,
            [props.searchParamName]: searchQuery.value,
        };

        const res = await axios.get(props.apiUrl, { params });

        // Handle Laravel standard pagination vs direct array vs custom wrapping
        if (Array.isArray(res.data)) {
            results.value = res.data;
        } else if (res.data.data && Array.isArray(res.data.data)) {
            results.value = res.data.data;
        } else {
            results.value = [];
        }
    } catch (e) {
        console.error('AsyncSelect search error:', e);
        results.value = [];
    } finally {
        isLoading.value = false;
    }
};

// Cancel any pending timeout if component is destroyed
onUnmounted(() => {
    clearTimeout(searchTimeout);
});
</script>
<style scoped>
/* Ensure dropdown appears above other elements */
.z-50 {
    z-index: 50;
}
.z-40 {
    z-index: 40;
}
</style>
