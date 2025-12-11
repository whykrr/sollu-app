<template>
  <div class="widget">
    <div class="flex flex-row items-center gap-2">
      <div>
        <div class="widget-icon">
          <FontAwesomeIcon class="text-base" :icon />
        </div>
      </div>
      <h2 class="font-medium">{{ title }}</h2>
    </div>
    <div class="text-gray-8 font-bold">
      <slot />
    </div>
    <div class="widget-bar">
      <div class="widget-value" :style="widthProgress" />
    </div>
    <div>
      <div class="inline-flex space-x-1.5 items-center">
        <FontAwesomeIcon
          :icon="faArrowUp"
          class="text-sm text-success"
        />
        <div class="text-xs text-gray-600">dari bulan lalu</div>
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
