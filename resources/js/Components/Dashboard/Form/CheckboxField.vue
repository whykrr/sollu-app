<template>
    <div class="form-check" v-bind:class="$attrs.class">
        <input
            type="checkbox"
            class="form-check-input"
            :value="modelValue"
            @input="emit('update:modelValue', $event.target.value)"
            v-bind="$attrs"
        />
        <label :for="$attrs.id" class="form-check-label">{{ label }}</label>
    </div>
    <span v-if="feedback" class="form-feedback">{{ feedback }}</span>
</template>
<script setup>
const props = defineProps({
    label: String,
    feedback: String,
    modelValue: String,
});
const emit = defineEmits(["update:modelValue"]);
</script>
