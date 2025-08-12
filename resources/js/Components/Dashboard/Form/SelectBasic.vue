<template>
    <div>
        <label v-if="label" :for="id">{{ label }}</label>
        <select
            :id
            class="form"
            :class="[{ 'text-gray-500': modelValue === '' }]"
            :value="modelValue"
            @change="emit('update:modelValue', $event.target.value)"
        >
            <option v-if="emptyOption" value="">-- Pilih --</option>
            <option v-for="o in options" :value="o.value">{{ o.label }}</option>
        </select>
        <span v-if="error" class="form-feedback">{{ error }}</span>
        <span v-if="success" class="form-feedback">{{ success }}</span>
    </div>
</template>
<script setup>
const props = defineProps({
    label: String,
    type: {
        type: String,
        default: "text",
    },
    error: String,
    success: String,
    modelValue: { type: String, default: "" },
    emptyOption: {
        type: Boolean,
        default: true,
    },
    options: Array,
});

const randomString = Math.random().toString(36).substr(2, 9);
const id = props.label
    ? props.label.toLowerCase().replace(/\s+/g, "-") + "-" + randomString
    : "textarea-basic-" + randomString;

const emit = defineEmits(["update:modelValue"]);
</script>
