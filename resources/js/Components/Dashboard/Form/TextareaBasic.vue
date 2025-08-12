<template>
    <div>
        <label v-if="label" :for="id">{{ label }}</label>
        <textarea
            class="form"
            :id
            :placeholder
            :class="{ 'is-invalid': error, 'is-valid': success }"
            @input="emit('update:modelValue', $event.target.value)"
            >{{ modelValue }}</textarea
        >
        <span v-if="error" class="form-feedback">{{ error }}</span>
        <span v-if="success" class="form-feedback">{{ success }}</span>
    </div>
</template>
<script setup>
const props = defineProps({
    label: String,
    placeholder: String,
    error: String,
    success: String,
    modelValue: String,
});

const randomString = Math.random().toString(36).substr(2, 9);
const id = props.label
    ? props.label.toLowerCase().replace(/\s+/g, "-") + "-" + randomString
    : "input-basic-" + randomString;

const emit = defineEmits(["update:modelValue"]);
</script>
