<template>
    <div
        class="relative inline-flex items-center cursor-pointer select-none group"
        :class="{ 'opacity-50 cursor-not-allowed': disabled }"
    >
        <input
            :id="id"
            type="checkbox"
            class="peer absolute inset-0 w-full h-full opacity-0 cursor-pointer pointer-events-auto"
            :checked="isChecked"
            :disabled="disabled"
            tabindex="-1"
            @change="handleChange"
        />
        <span
            class="flex items-center bg-slate-300 rounded-full p-0.5 transition-colors duration-200 ease-in-out peer-checked:bg-main shrink-0 pointer-events-none"
            :class="[
                size === 'sm' ? 'w-7 h-4' : size === 'lg' ? 'w-11 h-6' : 'w-9 h-5',
                { '!bg-main': isChecked }
            ]"
        >
            <span
                class="bg-white rounded-full shadow-xs transform transition-transform duration-200 ease-in-out pointer-events-none"
                :class="[
                    size === 'sm' ? 'w-3 h-3' : size === 'lg' ? 'w-5 h-5' : 'w-4 h-4',
                    isChecked
                        ? (size === 'sm' ? 'translate-x-3' : size === 'lg' ? 'translate-x-5' : 'translate-x-4')
                        : 'translate-x-0'
                ]"
            />
        </span>

        <span
            v-if="labeling"
            class="ml-2 text-slate-700 font-medium cursor-pointer pointer-events-none"
            :class="{
                'text-xs': size === 'sm',
                'text-sm': !size || size === 'md',
                'text-base': size === 'lg',
            }"
        >
            {{ labeling }}
        </span>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    id: {
        type: String,
        default: () => `switch-${Math.random().toString(36).substring(2, 9)}`,
    },
    labeling: {
        type: String,
        default: '',
    },
    size: {
        type: String,
        default: 'md',
    },
    modelValue: {
        type: [Boolean, Number, String],
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue', 'change']);

const isChecked = computed(() => {
    if (typeof props.modelValue === 'boolean') {
        return props.modelValue;
    }
    if (typeof props.modelValue === 'number') {
        return props.modelValue === 1;
    }
    return props.modelValue === '1' || props.modelValue === 'true';
});

const handleChange = (event) => {
    const checked = event.target.checked;
    let emittedValue;
    if (typeof props.modelValue === 'boolean') {
        emittedValue = checked;
    } else if (typeof props.modelValue === 'number') {
        emittedValue = checked ? 1 : 0;
    } else {
        emittedValue = checked;
    }
    emit('update:modelValue', emittedValue);
    emit('change', emittedValue);
};
</script>
