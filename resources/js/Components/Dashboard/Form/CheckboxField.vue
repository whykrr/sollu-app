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
        type="checkbox"
        class="form-check-input"
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
})
const emit = defineEmits(['update:modelValue'])
function toggleValue(value) {
    const newValue = [...props.modelValue]
    const index = newValue.indexOf(value)

    if (index === -1) {
        newValue.push(value)
    } else {
        newValue.splice(index, 1)
    }

    emit('update:modelValue', newValue)
}
</script>
