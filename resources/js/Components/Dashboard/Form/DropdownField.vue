<template>
    <div>
        <label v-if="label" :for="$attrs.id">{{ label }}</label>
        <select
            :id
            class="form"
            :class="[{ 'text-gray-500': modelValue === '' }]"
            :value="modelValue"
            @change="emit('update:modelValue', $event.target.value)"
            v-bind="$attrs"
        >
            <option v-if="placeholder" value="">{{ placeholder }}</option>
            <option v-for="o in options" :value="o.value">{{ o.label }}</option>
        </select>
        <span v-if="feedback" class="form-feedback">{{ feedback }}</span>
    </div>
</template>
<script setup>
const props = defineProps({
    id: String,
    label: String,
    feedback: String,
    modelValue: { type: String, default: "" },
    placeholder: String,
    options: {
        type: Array,
        default: () => [], // format: [{ value: '1', label: 'Satu' }]
    },
});

const emit = defineEmits(["update:modelValue"]);
</script>
