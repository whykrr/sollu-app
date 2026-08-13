<template>
    <div>
        <label v-if="label" :for="$attrs.id" class="label">{{ label }}</label>
        <textarea
            class="form"
            :class="{ 'is-invalid': error, 'is-valid': success }"
            v-bind="$attrs"
            @input="emit('update:modelValue', $event.target.value)"
            :value="modelValue"
        ></textarea>
        <span v-if="error" class="form-feedback text-danger">{{ error }}</span>
        <span v-else-if="success" class="form-feedback text-success">{{
            success
        }}</span>
    </div>
</template>
<script setup>
defineOptions({
    inheritAttrs: false,
});

const props = defineProps({
    label: String,
    feedback: String,
    error: String,
    success: String,
    modelValue: String,
});

const emit = defineEmits(['update:modelValue']);
</script>
