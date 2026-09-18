<template>
    <div
        class="bg-white p-3 rounded-xl border border-neutral-200/70 flex items-center gap-2 transition-all duration-150"
    >
        <div
            class="w-10 h-10 rounded-lg flex items-center justify-center text-base shrink-0"
            :class="[variantClasses, iconClass]"
        >
            <slot name="icon">
                <FontAwesomeIcon v-if="icon" :icon="icon" />
            </slot>
        </div>
        <div class="min-w-0 flex-1">
            <div class="text-xs text-neutral-500 font-medium truncate">
                <slot name="title">{{ title }}</slot>
            </div>
            <div
                class="text-lg font-bold leading-tight truncate"
                :class="valueClass || 'text-neutral-800'"
            >
                <slot>{{ value }}</slot>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

const props = defineProps({
    icon: {
        type: [Object, Array, String],
        default: null,
    },
    title: {
        type: String,
        default: '',
    },
    value: {
        type: [String, Number],
        default: '',
    },
    variant: {
        type: String,
        default: 'main',
        validator: value =>
            [
                'main',
                'primary',
                'success',
                'sky',
                'info',
                'amber',
                'warning',
                'danger',
                'purple',
                'neutral',
                'gray',
            ].includes(value),
    },
    color: {
        type: String,
        default: '',
    },
    iconClass: {
        type: String,
        default: '',
    },
    valueClass: {
        type: String,
        default: '',
    },
})

const variantClasses = computed(() => {
    const key = props.color || props.variant
    switch (key) {
        case 'success':
            return 'bg-success/10 text-success'
        case 'sky':
        case 'info':
            return 'bg-sky-50 text-sky-600'
        case 'amber':
        case 'warning':
            return 'bg-amber-50 text-amber-600'
        case 'danger':
            return 'bg-danger/10 text-danger'
        case 'purple':
            return 'bg-purple-50 text-purple-600'
        case 'neutral':
        case 'gray':
            return 'bg-neutral-100 text-neutral-600'
        case 'main':
        case 'primary':
        default:
            return 'bg-main/10 text-main'
    }
})
</script>
