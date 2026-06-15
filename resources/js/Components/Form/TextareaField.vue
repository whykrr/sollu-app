<template>
    <div>
        <label v-if="label" :for="$attrs.id">{{ label }}</label>
        <textarea
            class="form"
            v-bind="$attrs"
            @input="emit('update:modelValue', $event.target.value)"
            :value="modelValue"
        ></textarea>
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
