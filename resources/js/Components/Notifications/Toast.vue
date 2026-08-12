<template>
  <div
    class="toast-card group relative flex w-80 sm:w-96 items-start gap-3 rounded-xl border bg-white p-3.5 shadow-xl border-l-4 backdrop-blur-md transition-all duration-300 pointer-events-auto"
    :class="variantClasses[toastType]"
  >
    <!-- Left Icon Badge -->
    <div
      class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl transition-transform duration-200 group-hover:scale-105"
      :class="iconBadgeClasses[toastType]"
    >
      <FontAwesomeIcon
        :icon="computedIcon"
        class="text-sm"
      />
    </div>

    <!-- Main Content -->
    <div class="flex-1 min-w-0 pr-4 pt-0.5">
      <h4
        v-if="title"
        class="text-sm font-bold text-slate-800 leading-tight mb-0.5"
      >
        {{ title }}
      </h4>

      <div class="text-xs text-slate-500 leading-relaxed break-words font-medium">
        <slot>{{ message }}</slot>
      </div>

      <!-- Action Button / Link if provided -->
      <div
        v-if="action"
        class="mt-1.5"
      >
        <button
          type="button"
          class="inline-flex items-center text-xs font-semibold underline underline-offset-2 hover:opacity-80 transition cursor-pointer"
          :class="actionColorClasses[toastType]"
          @click="handleAction"
        >
          {{ action.text }}
        </button>
      </div>
    </div>

    <!-- Dismiss Button -->
    <button
      v-if="dismissible"
      type="button"
      class="absolute top-3 right-3 flex h-6 w-6 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition cursor-pointer"
      aria-label="Tutup"
      @click="dismiss"
    >
      <FontAwesomeIcon
        :icon="faXmark"
        class="text-xs"
      />
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faCheck,
    faInfo,
    faExclamation,
    faXmark,
} from '@fortawesome/free-solid-svg-icons'

const props = defineProps({
    title: {
        type: String,
        default: '',
    },
    message: {
        type: String,
        default: '',
    },
    type: {
        type: String,
        default: 'info',
    },
    // Backwards compatibility for 'color' prop
    color: {
        type: String,
        default: null,
    },
    icon: {
        type: [Object, String, Array],
        default: null,
    },
    dismissible: {
        type: Boolean,
        default: true,
    },
    action: {
        type: Object,
        default: null, // { text: string, onClick: function }
    },
})

const emit = defineEmits(['dismiss', 'hide'])

const toastType = computed(() => {
    const raw = props.color || props.type || 'info'
    return raw === 'error' ? 'danger' : raw
})

const computedIcon = computed(() => {
    if (props.icon) return props.icon
    switch (toastType.value) {
        case 'success':
            return faCheck
        case 'warning':
            return faExclamation
        case 'danger':
            return faXmark
        case 'info':
        default:
            return faInfo
    }
})

const variantClasses = {
    success: 'border-slate-200 border-l-emerald-500 shadow-slate-900/5',
    info: 'border-slate-200 border-l-blue-500 shadow-slate-900/5',
    warning: 'border-slate-200 border-l-amber-500 shadow-slate-900/5',
    danger: 'border-slate-200 border-l-rose-500 shadow-slate-900/5',
}

const iconBadgeClasses = {
    success: 'bg-emerald-50 text-emerald-600 border border-emerald-200/60',
    info: 'bg-blue-50 text-blue-600 border border-blue-200/60',
    warning: 'bg-amber-50 text-amber-600 border border-amber-200/60',
    danger: 'bg-rose-50 text-rose-600 border border-rose-200/60',
}

const actionColorClasses = {
    success: 'text-emerald-600 hover:text-emerald-700',
    info: 'text-blue-600 hover:text-blue-700',
    warning: 'text-amber-600 hover:text-amber-700',
    danger: 'text-rose-600 hover:text-rose-700',
}

const dismiss = () => {
    emit('dismiss')
    emit('hide')
}

const handleAction = () => {
    if (props.action && typeof props.action.onClick === 'function') {
        props.action.onClick()
    }
}
</script>
