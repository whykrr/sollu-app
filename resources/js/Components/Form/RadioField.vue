<template>
    <div class="flex flex-wrap gap-1">
        <div
            v-for="(opt, idx) in options"
            :key="idx"
            class="form-check"
            :class="$attrs.class"
        >
            <input
                :id="$attrs.name + idx"
                type="radio"
                class="form-check-input"
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
defineOptions({
    inheritAttrs: false,
});
const props = defineProps({
    label: String,
    options: Array,
    modelValue: Array,
});
const emit = defineEmits(['update:modelValue']);
</script>
