<template>
  <div>
    <label v-if="label" :for="id">{{ label }}</label>
    <input
      type="text"
      class="form"
      :value="modelValue"
      v-bind="$attrs"
      @input="emit('update:modelValue', $event.target.value)"
      @keydown="onKeydown"
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

function onKeydown(e) {
    const allowedKeys = [
        'Backspace',
        'Delete',
        'Tab',
        'ArrowLeft',
        'ArrowRight',
        'ArrowUp',
        'ArrowDown',
    ]
    const isNumber = /^[0-9]$/.test(e.key)

    if (!isNumber && !allowedKeys.includes(e.key)) {
        e.preventDefault()
    }
}
</script>
