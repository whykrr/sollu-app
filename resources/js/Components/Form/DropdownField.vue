<template>
    <div>
        <label v-if="label" :for="$attrs.id">{{ label }}</label>
        <select
            :id
            class="form pr-10!"
            :class="[{ 'text-gray-500': modelValue === '' }]"
            :value="modelValue"
            v-bind="$attrs"
            @change="emit('update:modelValue', $event.target.value)"
        >
            <option v-if="placeholder" value="">{{ placeholder }}</option>
            <option v-for="(o, i) in options" :key="i" :value="o.value">
                {{ o.label }}
            </option>
        </select>
        <span v-if="feedback" class="form-feedback">{{ feedback }}</span>
    </div>
</template>
<script setup>
defineOptions({
    inheritAttrs: false,
});

const { id, label, modelValue, placeholder, options, feedback } = defineProps({
    id: String,
    label: String,
    modelValue: { type: String, default: '' },
    placeholder: String,
    options: {
        type: Array,
        default: () => [], // format: [{ value: '1', label: 'Satu' }]
    },
    feedback: String,
});

const emit = defineEmits(['update:modelValue']);
</script>
