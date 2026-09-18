<template>
    <div
        class="bg-slate-200 p-0.5 rounded-lg inline-flex items-center h-[30px] box-border text-xs font-medium border border-gray-200 select-none overflow-x-auto max-w-full"
    >
        <button
            v-for="(option, index) in options"
            :key="index"
            type="button"
            class="px-2.5 py-1 h-[24px] rounded-md transition-all duration-150 inline-flex items-center gap-1.5 shrink-0 cursor-pointer text-xs leading-4"
            :class="
                isSelected(option.value)
                    ? 'bg-white text-neutral-900 font-semibold'
                    : 'text-neutral-500 hover:text-neutral-800'
            "
            @click="select(option.value)"
        >
            <span>{{ option.label }}</span>
            <span
                v-if="option.count !== undefined && option.count !== null"
                class="text-[10px] px-1.5 py-0.2 rounded-full font-medium text-white"
                :class="isSelected(option.value) ? 'bg-main ' : 'bg-main/50'"
            >
                {{ option.count }}
            </span>
        </button>
    </div>
</template>

<script setup>
const props = defineProps({
    modelValue: {
        type: [String, Number, Boolean],
        default: '',
    },
    options: {
        type: Array,
        required: true,
        default: () => [],
    },
})

const emit = defineEmits(['update:modelValue', 'change'])

const isSelected = val => {
    return String(props.modelValue ?? '') === String(val ?? '')
}

const select = val => {
    emit('update:modelValue', val)
    emit('change', val)
}
</script>
