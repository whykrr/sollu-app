<template>
    <div class="space-y-1">
        <label v-if="label" class="label">{{ label }}</label>
        <div :class="containerClass">
            <div
                v-for="(opt, idx) in options"
                :key="opt.value ?? idx"
                class="form-check"
                :class="[
                    itemClass,
                    $attrs.class,
                    { 'opacity-50 cursor-not-allowed': disabled || opt.disabled },
                ]"
            >
                <input
                    :id="getInputId(opt, idx)"
                    type="checkbox"
                    class="form-check-input"
                    :value="opt.value"
                    :checked="isChecked(opt.value)"
                    :disabled="disabled || opt.disabled"
                    @change="toggleValue(opt.value)"
                />
                <label :for="getInputId(opt, idx)" class="form-check-label">{{ opt.label }}</label>
            </div>
        </div>
        <div v-if="feedback" class="text-danger text-xs select-none">
            {{ feedback }}
        </div>
    </div>
</template>

<script setup>
defineOptions({
    inheritAttrs: false,
})

const props = defineProps({
    label: String,
    feedback: String,
    options: {
        type: Array,
        default: () => [],
    },
    modelValue: {
        type: Array,
        default: () => [],
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    name: {
        type: String,
        default: '',
    },
    containerClass: {
        type: [String, Object, Array],
        default: 'flex flex-wrap gap-1',
    },
    itemClass: {
        type: [String, Object, Array],
        default: '',
    },
})

const emit = defineEmits(['update:modelValue'])

function getInputId(opt, idx) {
    const prefix = props.name || 'chk_'
    const safeVal = String(opt.value ?? idx).replace(/[^a-zA-Z0-9_-]/g, '_')
    return `${prefix}${idx}_${safeVal}`
}

function isChecked(value) {
    if (!props.modelValue || !Array.isArray(props.modelValue)) {
        return false
    }
    return props.modelValue.includes(value)
}

function toggleValue(value) {
    const current = Array.isArray(props.modelValue) ? [...props.modelValue] : []
    const index = current.indexOf(value)

    if (index === -1) {
        current.push(value)
    } else {
        current.splice(index, 1)
    }

    emit('update:modelValue', current)
}
</script>
