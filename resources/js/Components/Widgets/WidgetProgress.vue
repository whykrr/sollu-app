<template>
  <div class="widget">
    <div class="flex flex-row items-center gap-3">
      <div class="widget-icon bg-main/10 text-main shrink-0">
        <FontAwesomeIcon class="text-lg" :icon />
      </div>
      <h2 class="font-medium text-sm text-neutral-600">{{ title }}</h2>
    </div>
    <div class="text-2xl text-neutral-800 font-bold mt-3 mb-1">
      <slot />
    </div>
    <div class="w-full bg-neutral-100 rounded-full h-1.5 mb-2">
      <div class="bg-main h-1.5 rounded-full transition-all duration-500" :style="widthProgress" />
    </div>
    <div>
      <div class="inline-flex gap-1.5 items-center">
        <FontAwesomeIcon
          :icon="faArrowUp"
          class="text-xs text-success"
        />
        <span class="text-xs text-neutral-500">dari bulan lalu</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { faArrowUp } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { computed, ref } from 'vue'

const props = defineProps({
    icon: String,
    title: String,
    value: Number,
    maxValue: Number,
})

const widthProgress = computed(() => {
    const progress = ref(Math.round((props.value / props.maxValue) * 100))

    if (progress.value > 100) {
        progress.value = 100
    }
    return `width: ${progress.value}%;`
})
</script>
