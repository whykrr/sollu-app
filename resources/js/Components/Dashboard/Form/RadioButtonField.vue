<template>
  <div class="flex flex-wrap gap-1">
    <div
      v-for="(opt, idx) in options"
      :key="idx"
      class="form-check"
      :class="$attrs.class"
    >
      <input
        v-bind="$attrs"
        :id="$attrs.name + idx"
        type="radio"
        class="form-check-btn peer"
        :value="opt.value"
        :checked="modelValue === opt.value"
        @change="emit('update:modelValue', $event.target.value)"
      />
      <label
        class="btn btn-outline-main"
        :class="$attrs.class"
        :for="$attrs.name + idx"
      >{{ opt.label }}</label>
    </div>
  </div>
</template>
<script setup>
const props = defineProps({
    label: String,
    options: Array,
    modelValue: Array,
})
const emit = defineEmits(['update:modelValue'])
</script>
