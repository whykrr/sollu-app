<template>
    <div class="form-check" v-bind:class="$attrs.class">
        <input
            type="radio"
            class="form-check-btn peer"
            v-bind="$attrs"
            :value="modelValue"
            @input="emit('update:modelValue', $event.target.value)"
        />
        <label class="btn btn-outline-main" :for="$attrs.id">{{ label }}</label>
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
