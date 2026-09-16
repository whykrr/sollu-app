<script setup>
import { computed } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faLock } from '@fortawesome/free-solid-svg-icons'
import { usePlanFeature } from '@/Composable/usePlanFeature'

const props = defineProps({
    feature: {
        type: [String, Array],
        default: null,
    },
    requireAll: {
        type: Boolean,
        default: false,
    },
    isLocked: {
        type: Boolean,
        default: undefined,
    },
    badgePosition: {
        type: String,
        default: 'top-right',
        validator: value =>
            ['top-right', 'center', 'center-right', 'bottom-right', 'top-left'].includes(value),
    },
    customLabel: {
        type: String,
        default: null,
    },
})

const emit = defineEmits(['click'])

const { hasFeature, hasAnyFeature, hasAllFeatures, requireFeature, subscription } = usePlanFeature()

const locked = computed(() => {
    if (props.isLocked !== undefined) {
        return props.isLocked
    }

    if (!props.feature) {
        return false
    }

    if (Array.isArray(props.feature)) {
        if (props.feature.length === 0) return false
        return props.requireAll ? !hasAllFeatures(props.feature) : !hasAnyFeature(props.feature)
    }

    return !hasFeature(props.feature)
})

const isSubscribed = computed(() => {
    return Boolean(subscription.value && subscription.value.status === 'active')
})

const labelText = computed(() => {
    if (props.customLabel) {
        return props.customLabel
    }
    return isSubscribed.value ? 'Tingkatkan Paket' : 'Langganan'
})

const alignmentClasses = computed(() => {
    switch (props.badgePosition) {
        case 'center':
            return 'items-center justify-center'
        case 'center-right':
            return 'items-center justify-end'
        case 'bottom-right':
            return 'items-end justify-end'
        case 'top-left':
            return 'items-start justify-start'
        case 'top-right':
        default:
            return 'items-start justify-end'
    }
})

const targetFeature = computed(() => {
    if (Array.isArray(props.feature)) {
        return props.feature[0] || null
    }
    return props.feature
})

const handleClick = event => {
    event.preventDefault()
    event.stopPropagation()
    emit('click', event)

    if (targetFeature.value) {
        requireFeature(targetFeature.value)
    }
}
</script>

<template>
    <div
        v-if="locked"
        class="sollu-feature-lock-overlay absolute inset-0 z-20 flex p-3 rounded-[inherit] bg-white/60 backdrop-blur-[0.5px] cursor-pointer transition-all select-none"
        :class="alignmentClasses"
        role="button"
        tabindex="0"
        aria-label="Fitur terkunci, klik untuk melihat opsi paket langganan"
        @click="handleClick"
        @keydown.enter="handleClick"
        @keydown.space.prevent="handleClick"
    >
        <button
            type="button"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold shadow-md bg-gradient-to-r from-main to-secondary hover:from-main-dark hover:to-secondary-dark text-white transition-all duration-200 hover:scale-105 active:scale-95 cursor-pointer whitespace-nowrap opacity-100 shrink-0"
            @click="handleClick"
        >
            <FontAwesomeIcon :icon="faLock" class="w-3 h-3 shrink-0" />
            <span>{{ labelText }}</span>
        </button>
    </div>
</template>
