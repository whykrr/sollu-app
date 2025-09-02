<template>
    <div class="flex flex-wrap gap-1">
        <div
            v-for="(opt, idx) in options"
            class="form-check"
            v-bind:class="$attrs.class"
        >
            <input
                type="radio"
                class="form-check-input"
                :id="$attrs.name + idx"
                :value="opt.value"
                @change="emit('update:modelValue', $event.target.value)"
            />
            <label :for="$attrs.name + idx" class="form-check-label">{{
                opt.label
            }}</label>
        </div>
    </div>
</template>
<script setup>
const props = defineProps({
    label: String,
    options: Array,
    modelValue: Array,
});
const emit = defineEmits(["update:modelValue"]);
</script>
