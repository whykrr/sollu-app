<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs overflow-y-auto"
        @click.self="handleBackdropClick"
      >
        <div
          class="relative w-full rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden transform transition-all"
          :class="size"
        >
          <!-- Modal Header -->
          <div
            v-if="title || $slots.header || showClose"
            class="flex items-center justify-between px-5 py-4 border-b border-slate-100 bg-slate-50/50"
          >
            <slot name="header">
              <div class="flex items-center gap-2.5">
                <!-- Optional Icon Header Badge -->
                <div
                  v-if="type && type !== 'default'"
                  class="flex h-7 w-7 items-center justify-center rounded-lg text-xs"
                  :class="iconHeaderClasses[type]"
                >
                  <FontAwesomeIcon :icon="computedIcon" />
                </div>
                <h3 class="text-base font-bold text-slate-800 leading-tight">
                  {{ title }}
                </h3>
              </div>
            </slot>

            <button
              v-if="showClose"
              type="button"
              class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-200/60 hover:text-slate-700 transition cursor-pointer"
              aria-label="Tutup"
              @click="closeModal"
            >
              <FontAwesomeIcon
                :icon="faXmark"
                class="text-sm"
              />
            </button>
          </div>

          <!-- Modal Body -->
          <div class="p-5 max-h-[75vh] overflow-y-auto text-slate-600 text-sm leading-relaxed">
            <slot />
          </div>

          <!-- Modal Footer -->
          <div
            v-if="$slots.footer"
            class="flex items-center justify-end gap-2.5 px-5 py-3.5 border-t border-slate-100 bg-slate-50/50"
          >
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, onMounted, onUnmounted } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faCheck,
    faInfo,
    faExclamation,
    faXmark,
    faTriangleExclamation,
} from '@fortawesome/free-solid-svg-icons'

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: '',
    },
    type: {
        type: String,
        default: 'default',
    },
    size: {
        type: String,
        default: 'max-w-lg',
    },
    showClose: {
        type: Boolean,
        default: true,
    },
    closeOnBackdrop: {
        type: Boolean,
        default: true,
    },
})

const emit = defineEmits(['close'])

const closeModal = () => {
    emit('close')
}

const handleBackdropClick = () => {
    if (props.closeOnBackdrop) {
        closeModal()
    }
}

const handleKeyDown = (e) => {
    if (e.key === 'Escape' && props.show) {
        closeModal()
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown)
})

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown)
})

const computedIcon = computed(() => {
    switch (props.type) {
        case 'success':
            return faCheck
        case 'warning':
            return faTriangleExclamation
        case 'danger':
            return faXmark
        case 'info':
        default:
            return faInfo
    }
})

const iconHeaderClasses = {
    success: 'bg-emerald-100 text-emerald-600',
    info: 'bg-blue-100 text-blue-600',
    warning: 'bg-amber-100 text-amber-600',
    danger: 'bg-rose-100 text-rose-600',
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.25s ease, transform 0.25s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from .relative,
.modal-leave-to .relative {
    transform: scale(0.95) translateY(10px);
}
</style>
