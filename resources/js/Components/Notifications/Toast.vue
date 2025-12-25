<template>
  <div
    class="toast backdrop-blur border"
    :class="[colorClasses[color], { hide: !showToast, show: showToast }]"
  >
    <div class="flex flex-col">
      <strong>
        <FontAwesomeIcon :icon />
        {{ title }}
      </strong>
      <div class="text-xs">
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
    title: String,
    icon: String,
    color: String,
})
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { onMounted, ref } from 'vue'

const showToast = ref(false)

const emit = defineEmits(['hide'])

const colorClasses = {
    danger: 'bg-danger/20 text-danger border-danger',
    success: 'bg-success/20 text-success border-success',
    info: 'bg-info/20 text-info border-info',
    warning: 'bg-warning/20 text-warning border-warning',
}

onMounted(() => {
    showToast.value = false
    setTimeout(() => {
        showToast.value = true
    }, 100)
    setTimeout(() => {
        showToast.value = false
    }, 3100)
    setTimeout(() => {
        emit('hide')
    }, 3200)
})
</script>
