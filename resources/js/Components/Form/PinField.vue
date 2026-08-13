<template>
    <div class="pin-field-container">
        <label v-if="label"
               class="block text-sm font-medium mb-1"
               :class="error ? 'text-danger' : 'text-neutral-700'">{{
            label }}</label>
        <div class="flex gap-2">
            <input v-for="(digit, index) in 6"
                   :key="index"
                   ref="inputs"
                   v-model="pinValues[index]"
                   type="text"
                   inputmode="numeric"
                   maxlength="1"
                   class="form text-center text-lg w-10 h-10"
                   :class="{ 'border-danger': error }"
                   @input="onInput(index, $event)"
                   @keydown="onKeyDown(index, $event)"
                   @paste="onPaste" />
        </div>
        <div v-if="error"
             class="text-danger text-xs mt-1">{{
            error }}</div>
        <div v-if="hint"
             class="text-neutral-400 text-xs mt-1">
            {{ hint }}</div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
    label: String,
    error: String,
    hint: String
});

const emit = defineEmits(['update:modelValue']);

const pinValues = ref(Array(6).fill(''));
const inputs = ref([]);

watch(() => props.modelValue, (newVal) => {
    if (newVal === null || newVal === undefined) newVal = '';
    const valArr = String(newVal).split('');
    for (let i = 0; i < 6; i++) {
        pinValues.value[i] = valArr[i] || '';
    }
}, { immediate: true });

const emitValue = () => {
    emit('update:modelValue', pinValues.value.join(''));
};

const onInput = (index, event) => {
    let val = event.target.value;
    // ensure numeric
    val = val.replace(/[^0-9]/g, '');
    pinValues.value[index] = val;
    event.target.value = val;

    emitValue();

    if (val && index < 5) {
        inputs.value[index + 1].focus();
    }
};

const onKeyDown = (index, event) => {
    if (event.key === 'Backspace' && !pinValues.value[index] && index > 0) {
        inputs.value[index - 1].focus();
    }
};

const onPaste = (event) => {
    event.preventDefault();
    const pasteData = event.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 6);
    if (pasteData) {
        for (let i = 0; i < pasteData.length; i++) {
            pinValues.value[i] = pasteData[i];
        }
        emitValue();
        const nextIndex = Math.min(pasteData.length, 5);
        inputs.value[nextIndex].focus();
    }
};
</script>
