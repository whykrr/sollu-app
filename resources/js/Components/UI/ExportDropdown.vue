<template>
    <div ref="dropdownRef" class="relative inline-block text-left shrink-0">
        <button
            ref="triggerRef"
            type="button"
            :class="[
                buttonClass ||
                    'btn btn-flat btn-sm h-[30px] inline-flex items-center gap-1.5 text-xs leading-4',
                'select-none cursor-pointer',
            ]"
            @click="toggleDropdown"
        >
            <FontAwesomeIcon :icon="icon || faDownload" class="text-xs" />
            <span class="text-xs leading-4">{{ label }}</span>
            <FontAwesomeIcon
                :icon="faChevronDown"
                class="text-[10px] text-slate-400 transition-transform duration-200"
                :class="{ 'rotate-180': isOpen }"
            />
        </button>

        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-100 ease-out"
                enter-from-class="transform scale-95 opacity-0"
                enter-to-class="transform scale-100 opacity-100"
                leave-active-class="transition duration-75 ease-in"
                leave-from-class="transform scale-100 opacity-100"
                leave-to-class="transform scale-95 opacity-0"
            >
                <div
                    v-if="isOpen"
                    ref="menuRef"
                    class="fixed z-[9999] min-w-[170px] rounded-xl bg-white p-1 shadow-lg ring-1 ring-black/5 focus:outline-none border border-gray-200"
                    :style="menuStyle"
                >
                    <slot :close="close">
                        <button
                            v-for="(item, index) in items"
                            :key="index"
                            type="button"
                            class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-neutral-700 transition hover:bg-gray-50 hover:text-neutral-900 cursor-pointer"
                            :class="item.class"
                            @click="handleItemClick(item)"
                        >
                            <FontAwesomeIcon v-if="item.icon" :icon="item.icon" class="text-xs" />
                            <span>{{ item.label }}</span>
                        </button>
                    </slot>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faDownload, faChevronDown } from '@fortawesome/free-solid-svg-icons'

const props = defineProps({
    label: {
        type: String,
        default: 'Ekspor Data',
    },
    icon: {
        type: Object,
        default: null,
    },
    buttonClass: {
        type: String,
        default: 'btn btn-flat btn-sm',
    },
    align: {
        type: String,
        default: 'right',
        validator: value => ['left', 'right'].includes(value),
    },
    items: {
        type: Array,
        default: () => [],
    },
})

const isOpen = ref(false)
const dropdownRef = ref(null)
const triggerRef = ref(null)
const menuRef = ref(null)
const menuStyle = ref({})

const updatePosition = () => {
    if (!triggerRef.value) return
    const rect = triggerRef.value.getBoundingClientRect()
    const viewportWidth = window.innerWidth
    const viewportHeight = window.innerHeight
    const menuWidth = 170
    const menuHeight = 120

    let top = rect.bottom + 6
    if (top + menuHeight > viewportHeight && rect.top > menuHeight) {
        top = Math.max(8, rect.top - menuHeight - 6)
    }

    let left = rect.left
    if (props.align === 'right') {
        left = rect.right - menuWidth
    }

    if (left < 8) left = 8
    if (left + menuWidth > viewportWidth - 8) {
        left = Math.max(8, viewportWidth - menuWidth - 8)
    }

    menuStyle.value = {
        top: `${top}px`,
        left: `${left}px`,
    }
}

const toggleDropdown = () => {
    isOpen.value = !isOpen.value
    if (isOpen.value) {
        nextTick(() => {
            updatePosition()
        })
    }
}

const close = () => {
    isOpen.value = false
}

const handleItemClick = item => {
    if (typeof item.action === 'function') {
        item.action()
    }
    close()
}

const handleClickOutside = event => {
    if (!isOpen.value) return
    if (
        triggerRef.value &&
        !triggerRef.value.contains(event.target) &&
        menuRef.value &&
        !menuRef.value.contains(event.target)
    ) {
        isOpen.value = false
    }
}

const handleScrollOrResize = () => {
    if (isOpen.value) {
        updatePosition()
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
    window.addEventListener('scroll', handleScrollOrResize, true)
    window.addEventListener('resize', handleScrollOrResize)
})

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside)
    window.removeEventListener('scroll', handleScrollOrResize, true)
    window.removeEventListener('resize', handleScrollOrResize)
})
</script>
