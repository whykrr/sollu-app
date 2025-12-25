<template>
  <div>
    <label v-if="label" :for="id">{{ label }}</label>
    <input
      type="password"
      class="form"
      :value="modelValue"
      v-bind="$attrs"
      @input="emit('update:modelValue', $event.target.value)"
    />
    <span v-if="feedback" class="form-feedback">{{ feedback }}</span>
  </div>
</template>
<script setup>
const props = defineProps({
    label: String,
    feedback: String,
    modelValue: String,
})
const emit = defineEmits(['update:modelValue'])
</script>
