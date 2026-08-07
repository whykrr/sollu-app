<template>
    <div class="relative">
        <label
            v-if="label"
            :for="$attrs.id"
            class="block text-sm font-medium"
            >{{ label }}</label
        >
        <div class="relative">
            <select
                :id="$attrs.id"
                v-model="internalValue"
                class="form w-full"
                :class="{ 'border-danger': feedback, 'bg-gray-100': disabled }"
                :disabled="disabled || isLoading"
                v-bind="$attrs"
            >
                <option value="">{{ placeholder }}</option>
                <option
                    v-for="outlet in outlets"
                    :key="outlet.id"
                    :value="outlet.id"
                >
                    {{ outlet.name }}
                </option>
            </select>

            <div
                v-if="isLoading"
                class="absolute right-8 top-1/2 -translate-y-1/2 pointer-events-none"
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
        </div>
        <span v-if="feedback" class="text-danger text-xs mt-1 block">{{
            feedback
        }}</span>
    </div>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { useOutlets } from '@/Composable/useOutlets.js';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    label: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Pilih Outlet',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    feedback: {
        type: String,
        default: '',
    },
    excludeFrozen: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue', 'change', 'loaded']);

const internalValue = ref(props.modelValue);

const { outlets: sharedOutlets, isLoading, fetchOutlets } = useOutlets();

const outlets = computed(() => {
    if (props.excludeFrozen) {
        return sharedOutlets.value.filter((o) => !o.is_stock_frozen);
    }
    return sharedOutlets.value;
});

watch(
    () => props.modelValue,
    (newVal) => {
        internalValue.value = newVal;
    },
);

watch(internalValue, (newVal) => {
    emit('update:modelValue', newVal);
    const selected = outlets.value.find((o) => o.id == newVal);
    emit('change', selected || null);
});

const loadOutlets = async () => {
    try {
        await fetchOutlets();
        emit('loaded', outlets.value);
    } catch (error) {
        console.error('Failed to load outlets:', error);
    }
};

onMounted(() => {
    loadOutlets();
});

// Watch shared outlets to emit loaded if data arrives via other components forcing refresh
watch(
    sharedOutlets,
    () => {
        emit('loaded', outlets.value);
    },
    { deep: true },
);
</script>
