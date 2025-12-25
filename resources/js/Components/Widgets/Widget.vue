<template>
  <div class="widget">
    <div class="flex flex-row items-center gap-2">
      <div>
        <div class="widget-icon">
          <FontAwesomeIcon :icon />
        </div>
      </div>
      <h2 class="font-medium">{{ title }}</h2>
    </div>
    <div class="text-gray-8 font-bold">
      <slot />
    </div>
    <div>
      <div class="inline-flex space-x-1.5 items-center">
        <FontAwesomeIcon
          v-if="traction === 'up'"
          :icon="faArrowUp"
          class="text-sm text-success"
        />
        <FontAwesomeIcon
          v-else
          :icon="faArrowDown"
          class="text-sm text-danger"
        />
        <span
          class="text-xs"
          :class="{
            'text-success': traction === 'up',
            'text-danger': traction === 'down',
          }"
        >{{ tractionPercentage }}%</span>
        <div class="text-xs text-gray-600">{{ descriptors }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
    faArrowDown,
    faArrowUp,
    faBell,
} from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { computed, ref } from 'vue'

const props = defineProps({
    icon: Object,
    title: String,
    traction: {
        type: String,
        default: 'up',
    },
    tractionPercentage: {
        type: Number,
        default: 0,
    },
    descriptors: {
        type: String,
        default: 'dari bulan lalu',
    },
})
</script>
