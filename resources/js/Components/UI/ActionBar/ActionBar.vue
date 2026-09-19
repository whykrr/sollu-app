<template>
    <div
        class="flex flex-col md:flex-row md:items-center md:justify-between gap-1.5 md:gap-2 w-full"
        :class="{ '!gap-1': compact }"
    >
        <!-- Left / Filters Area (Desktop: Left flex-1, Mobile: Bottom row) -->
        <div
            ref="scrollContainerRef"
            class="order-2 md:order-1 relative flex-1 min-w-0 flex items-center"
        >
            <!-- Left Fade Mask (Scroll Indicator) -->
            <div
                v-if="canScrollLeft"
                class="absolute left-0 top-0 bottom-0 w-4 md:w-6 bg-gradient-to-r from-white via-white/80 to-transparent pointer-events-none z-10"
            />

            <!-- Scrollable Filter Track -->
            <div
                ref="trackRef"
                class="flex items-center gap-1.5 md:gap-2 overflow-x-auto no-scrollbar py-0.5 w-full select-none cursor-grab active:cursor-grabbing"
                @scroll.passive="checkScroll"
                @mousedown="onMouseDown"
                @mouseleave="onMouseLeave"
                @mouseup="onMouseUp"
                @mousemove="onMouseMove"
            >
                <slot name="filters">
                    <slot name="left">
                        <slot />
                    </slot>
                </slot>
            </div>

            <!-- Right Fade Mask (Scroll Indicator) -->
            <div
                v-if="canScrollRight"
                class="absolute right-0 top-0 bottom-0 w-4 md:w-6 bg-gradient-to-l from-white via-white/80 to-transparent pointer-events-none z-10"
            />
        </div>

        <!-- Right / Controls: Search, Tools/Opsi Data, & Primary Add Button (Desktop: Right shrink-0, Mobile: Top row) -->
        <div
            class="order-1 md:order-2 flex items-center gap-1.5 md:gap-2 justify-between md:justify-end shrink-0 w-full md:w-auto"
        >
            <!-- 1. Search slot (Fills width on mobile, standard width on desktop) -->
            <div v-if="$slots.search" class="flex-1 md:flex-initial min-w-0">
                <slot name="search" />
            </div>

            <!-- 2. Tools / Actions slot (Opsi Data dropdown, view toggle, etc.) -->
            <div
                v-if="$slots.tools || $slots.actions"
                class="flex items-center gap-1.5 shrink-0 justify-end"
            >
                <slot name="tools">
                    <slot name="actions" />
                </slot>
            </div>

            <!-- 3. Primary Create / Add Data Button (PALING KANAN) -->
            <div
                v-if="$slots.create || $slots.primary || $slots.right"
                class="shrink-0 flex items-center justify-end"
            >
                <slot name="create">
                    <slot name="primary">
                        <slot name="right" />
                    </slot>
                </slot>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue'

defineProps({
    compact: {
        type: Boolean,
        default: false,
    },
})

const scrollContainerRef = ref(null)
const trackRef = ref(null)
const canScrollLeft = ref(false)
const canScrollRight = ref(false)

let isDragging = false
let startX = 0
let scrollLeft = 0
let resizeObserver = null
let mutationObserver = null

const checkScroll = () => {
    const el = trackRef.value
    if (!el) return
    canScrollLeft.value = el.scrollLeft > 4
    canScrollRight.value = el.scrollLeft + el.clientWidth < el.scrollWidth - 4
}

// Mouse drag-to-scroll support for desktop
const onMouseDown = e => {
    // Only primary button and ignore if clicking on interactive elements
    if (e.button !== 0) return
    if (e.target.closest('button, input, select, textarea, a, [role="button"]')) return
    const el = trackRef.value
    if (!el) return
    isDragging = true
    startX = e.pageX - el.offsetLeft
    scrollLeft = el.scrollLeft
}

const onMouseLeave = () => {
    isDragging = false
}

const onMouseUp = () => {
    isDragging = false
}

const onMouseMove = e => {
    if (!isDragging) return
    e.preventDefault()
    const el = trackRef.value
    if (!el) return
    const x = e.pageX - el.offsetLeft
    const walk = (x - startX) * 1.2
    el.scrollLeft = scrollLeft - walk
    checkScroll()
}

onMounted(() => {
    nextTick(() => {
        checkScroll()

        if (trackRef.value) {
            resizeObserver = new ResizeObserver(() => checkScroll())
            resizeObserver.observe(trackRef.value)

            mutationObserver = new MutationObserver(() => checkScroll())
            mutationObserver.observe(trackRef.value, { childList: true, subtree: true })
        }
    })

    window.addEventListener('resize', checkScroll)
})

onBeforeUnmount(() => {
    window.removeEventListener('resize', checkScroll)
    if (resizeObserver) resizeObserver.disconnect()
    if (mutationObserver) mutationObserver.disconnect()
})
</script>
