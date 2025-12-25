<template>
  <div class="card bg-transparent! rounded-none">
    <div v-if="image">
      <img :src="image" alt="Card Image" class="block-inline w-full" />
    </div>
    <div v-if="hasTitleOrButtons" class="card-header p-0!">
      <h2 class="text-2xl font-semibold">{{ title }}</h2>
      <div class="flex flex-row gap-2">
        <slot name="buttons" />
      </div>
    </div>
    <div class="">
      <slot />
    </div>
  </div>
</template>

<script setup>
import { computed, useSlots } from 'vue'

const props = defineProps({
    image: String,
    title: String,
})

// Cek slot
const slots = useSlots()
const hasTitleOrButtons = computed(() => props.title || slots.buttons)
</script>
