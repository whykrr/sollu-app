<script setup>
import { computed } from 'vue'
import { usePlanFeature } from '@/Composable/usePlanFeature'
import FeatureLockOverlay from './FeatureLockOverlay.vue'

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
    as: {
        type: String,
        default: 'div',
    },
    badgePosition: {
        type: String,
        default: 'top-right',
    },
    customLabel: {
        type: String,
        default: null,
    },
    contentClass: {
        type: [String, Object, Array],
        default: '',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
})

const { hasFeature, hasAnyFeature, hasAllFeatures } = usePlanFeature()

const locked = computed(() => {
    if (props.disabled) {
        return false
    }

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
</script>

<template>
    <component :is="as" class="relative group/lock" :aria-disabled="locked ? 'true' : undefined">
        <div :class="[contentClass, { 'pointer-events-none select-none opacity-65': locked }]">
            <slot :is-locked="locked" />
        </div>

        <FeatureLockOverlay
            v-if="locked"
            :feature="feature"
            :require-all="requireAll"
            :is-locked="locked"
            :badge-position="badgePosition"
            :custom-label="customLabel"
        />
    </component>
</template>
