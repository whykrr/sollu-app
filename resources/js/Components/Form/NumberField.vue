<template>
    <div>
        <label
            v-if="label"
            :for="$attrs.id"
            class="label"
            :class="{ '!text-xs': size === 'sm', '!text-base': size === 'lg' }"
        >
            {{ label }}
        </label>
        <input
            type="text"
            class="form"
            :class="[
                { sm: size === 'sm', lg: size === 'lg', adaptive: size === 'adaptive' },
                { 'is-invalid': error, 'is-valid': success },
            ]"
            :value="modelValue"
            v-bind="$attrs"
            @input="onInput"
            @keydown="onKeydown"
        />
        <span v-if="error" class="form-feedback text-danger">{{ error }}</span>
        <span v-else-if="success" class="form-feedback text-success">{{ success }}</span>
        <span v-else-if="feedback" class="form-feedback text-neutral-500">{{ feedback }}</span>
    </div>
</template>
<script setup>
defineOptions({
    inheritAttrs: false,
})

const props = defineProps({
    label: String,
    feedback: String,
    error: String,
    success: String,
    modelValue: [String, Number],
    size: {
        type: String,
        default: 'base',
        validator: v => ['sm', 'base', 'adaptive', 'lg'].includes(v),
    },
})
const emit = defineEmits(['update:modelValue'])

function onInput(e) {
    let val = e.target.value

    // Allow empty string to reset the field
    if (val === '') {
        emit('update:modelValue', '')
        return
    }

    // Allow transient states (like starting with minus or decimal)
    if (val === '-' || val === '.' || val === '-.') {
        emit('update:modelValue', val)
        return
    }

    // Attempt to convert to a raw number
    const num = Number(val.replace(',', '.'))

    if (!isNaN(num)) {
        emit('update:modelValue', num)
    } else {
        emit('update:modelValue', val)
    }
}

function onKeydown(e) {
    const allowedKeys = [
        'Backspace',
        'Delete',
        'Tab',
        'ArrowLeft',
        'ArrowRight',
        'ArrowUp',
        'ArrowDown',
        '.',
        ',',
        '-',
    ]
    const isNumber = /^[0-9]$/.test(e.key)

    if (!isNumber && !allowedKeys.includes(e.key)) {
        e.preventDefault()
    }
}
</script>
