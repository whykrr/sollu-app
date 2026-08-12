<template>
  <button
    type="button"
    class="btn btn-sm w-full rounded-lg justify-center hover:bg-white hover:text-neutral-800 hover:font-medium"
    :class="{
      'bg-white font-medium': active,
      'text-neutral-700': !active,
    }"
  >
    {{ label }}
    <span
      v-if="badge > 0"
      class="text-[10px] flex items-center justify-center text-white rounded-lg w-5 h-5"
      :class="{
        'bg-main': active,
        'bg-neutral-400': !active,
      }"
    >{{ badge }}</span>
  </button>
</template>
<script setup>
const props = defineProps({
    label: String,
    active: Boolean,
    badge: {
        type: Number,
        default: 0,
    },
})
</script>
