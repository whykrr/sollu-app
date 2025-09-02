<template>
    <div class="flex flex-wrap gap-1">
        <div
            v-for="(opt, idx) in options"
            class="form-check"
            v-bind:class="$attrs.class"
        >
            <input
                type="checkbox"
                class="form-check-input"
                :id="$attrs.name + idx"
                :value="opt.value"
                :checked="modelValue.includes(opt.value)"
                @change="toggleValue(opt.value)"
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
    feedback: String,
    options: Array,
    modelValue: Array,
});
const emit = defineEmits(["update:modelValue"]);
function toggleValue(value) {
    const newValue = [...modelValue];
    const index = newValue.indexOf(value);

    if (index === -1) {
        newValue.push(value);
    } else {
        newValue.splice(index, 1);
    }

    emit("update:modelValue", newValue);
}
</script>
