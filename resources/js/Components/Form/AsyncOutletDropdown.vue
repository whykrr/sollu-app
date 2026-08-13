<template>
    <div class="relative">
        <div class="space-y-2">
            <label
                v-if="label"
                :for="$attrs.id"
                class="block text-xs font-semibold text-slate-500 uppercase tracking-wider"
            >
                {{ label }}
            </label>
            <div
                class="bg-slate-50/60 border border-slate-200 p-3 rounded-xl relative"
            >
                <SelectionGroupField
                    :id="$attrs.id"
                    v-model="internalValue"
                    :options="formattedOutlets"
                    :name="$attrs.id || 'outlet_id'"
                    class="sm btn-sm"
                    :disabled="disabled || isLoading"
                />

                <div
                    v-if="isLoading"
                    class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"
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
            <span v-if="feedback" class="text-danger text-xs mt-1 block">
                {{ feedback }}
            </span>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { useAuth } from '@/Composable/useAuth.js';
import SelectionGroupField from '@/Components/Form/SelectionGroupField.vue';

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

const { outlets: sharedOutlets } = useAuth();
const isLoading = ref(false);

const outlets = computed(() => {
    if (props.excludeFrozen) {
        return sharedOutlets.value.filter((o) => !o.is_stock_frozen);
    }
    return sharedOutlets.value;
});

const formattedOutlets = computed(() => {
    return outlets.value.map((o) => ({
        value: o.id,
        label: o.name,
    }));
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

onMounted(() => {
    emit('loaded', outlets.value);
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
