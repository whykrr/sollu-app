<template>
    <div>
        <label v-if="label" :for="id">{{ label }}</label>
        <textarea
            class="form"
            @input="emit('update:modelValue', $event.target.value)"
            v-bind="$attrs"
            >{{ modelValue }}</textarea
        >
        <span v-if="feedback" class="form-feedback">{{ feedback }}</span>
    </div>
</template>
<script setup>
const props = defineProps({
    label: String,
    feedback: String,
    modelValue: String,
});

const emit = defineEmits(["update:modelValue"]);
</script>
