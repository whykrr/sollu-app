<template>
    <div>
        <label v-if="label" :for="$attrs.id">{{ label }}</label>
        <input
            type="text"
            class="form"
            :value="modelValue"
            v-bind="$attrs"
            @input="emit('update:modelValue', $event.target.value)"
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
    modelValue: String,
});
const emit = defineEmits(['update:modelValue']);
</script>
