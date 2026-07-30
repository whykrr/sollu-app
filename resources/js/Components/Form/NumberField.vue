<template>
    <div>
        <label v-if="label" :for="$attrs.id">{{ label }}</label>
        <input
            type="text"
            class="form"
            :value="modelValue"
            v-bind="$attrs"
            @input="onInput"
            @keydown="onKeydown"
        />
        <span v-if="feedback" class="form-feedback">{{ feedback }}</span>
    </div>
</template>
<script setup>
defineOptions({
    inheritAttrs: false,
});

const props = defineProps({
    label: String,
    feedback: String,
    modelValue: [String, Number],
});
const emit = defineEmits(['update:modelValue']);

function onInput(e) {
    let val = e.target.value;
    
    // Allow empty string to reset the field
    if (val === '') {
        emit('update:modelValue', '');
        return;
    }

    // Allow transient states (like starting with minus or decimal)
    if (val === '-' || val === '.' || val === '-.') {
        emit('update:modelValue', val);
        return;
    }

    // Attempt to convert to a raw number
    const num = Number(val.replace(',', '.'));
    
    if (!isNaN(num)) {
        emit('update:modelValue', num);
    } else {
        emit('update:modelValue', val);
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
    ];
    const isNumber = /^[0-9]$/.test(e.key);

    if (!isNumber && !allowedKeys.includes(e.key)) {
        e.preventDefault();
    }
}
</script>
