<template>
    <div ref="dropdownRef" class="relative inline-block text-left shrink-0 select-none">
        <button
            ref="triggerRef"
            type="button"
            :class="[
                buttonClass ||
                    (iconOnly
                        ? 'btn btn-flat btn-sm w-[30px] h-[30px] !p-0 inline-flex items-center justify-center text-xs'
                        : 'btn btn-flat btn-sm h-[30px] inline-flex items-center gap-1.5 text-xs leading-4'),
                'cursor-pointer transition-all duration-150',
                { '!border-main/40 !bg-main/5': isOpen },
            ]"
            :title="title || label"
            :aria-label="label"
            :aria-expanded="isOpen"
            @click="toggleDropdown"
        >
            <FontAwesomeIcon
                :icon="icon || (iconOnly ? faEllipsisVertical : faFolderOpen)"
                :class="iconOnly ? 'text-sm' : 'text-xs text-neutral-500'"
            />
            <span v-if="!iconOnly" class="text-xs leading-4 font-medium text-neutral-700">
                {{ label }}
            </span>
            <FontAwesomeIcon
                v-if="!iconOnly && showCaret"
                :icon="faChevronDown"
                class="text-[10px] text-neutral-400 transition-transform duration-200"
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
                    class="fixed z-[9999] rounded-xl bg-white p-1.5 shadow-xl ring-1 ring-black/5 focus:outline-none border border-gray-200 max-h-[85vh] overflow-y-auto floating-scroll text-xs space-y-0.5"
                    :style="menuStyle"
                >
                    <!-- Optional Header Slot -->
                    <div
                        v-if="$slots.header || menuTitle"
                        class="px-2.5 py-1.5 border-b border-gray-100 mb-1"
                    >
                        <slot name="header">
                            <span
                                class="text-[11px] font-bold text-neutral-400 uppercase tracking-wider"
                            >
                                {{ menuTitle }}
                            </span>
                        </slot>
                    </div>

                    <!-- Custom Slot or Declarative Items -->
                    <slot :close="close">
                        <template v-for="(item, index) in items" :key="index">
                            <!-- Divider -->
                            <div
                                v-if="item.divider"
                                class="my-1 border-t border-gray-100 -mx-1"
                                role="separator"
                            />

                            <!-- Action Item Button -->
                            <button
                                type="button"
                                class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 sm:py-1.5 text-left transition-colors cursor-pointer group"
                                :class="[
                                    item.disabled
                                        ? 'opacity-40 cursor-not-allowed bg-transparent'
                                        : item.danger
                                          ? 'text-rose-600 hover:bg-rose-50 hover:text-rose-700 active:bg-rose-100'
                                          : 'text-neutral-700 hover:bg-gray-50 hover:text-neutral-900 active:bg-gray-100',
                                    item.class,
                                ]"
                                :disabled="item.disabled"
                                @click="handleItemClick(item)"
                            >
                                <!-- Icon -->
                                <div
                                    v-if="item.icon"
                                    class="w-4 flex items-center justify-center shrink-0"
                                >
                                    <FontAwesomeIcon
                                        :icon="item.icon"
                                        class="text-xs transition-colors"
                                        :class="
                                            item.iconClass ||
                                            (item.danger
                                                ? 'text-rose-500'
                                                : 'text-neutral-400 group-hover:text-neutral-700')
                                        "
                                    />
                                </div>

                                <!-- Label & Description -->
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium text-xs leading-tight truncate">
                                        {{ item.label }}
                                    </div>
                                    <div
                                        v-if="item.description"
                                        class="text-[10px] text-neutral-400 leading-tight mt-0.5 truncate"
                                    >
                                        {{ item.description }}
                                    </div>
                                </div>

                                <!-- Optional Badge / Tag -->
                                <div v-if="item.badge" class="shrink-0 ml-auto">
                                    <span
                                        class="px-1.5 py-0.5 rounded text-[10px] font-semibold"
                                        :class="item.badgeClass || 'bg-slate-100 text-slate-600'"
                                    >
                                        {{ item.badge }}
                                    </span>
                                </div>
                            </button>
                        </template>

                        <div
                            v-if="items.length === 0 && !$slots.default"
                            class="px-3 py-2 text-center text-xs text-neutral-400"
                        >
                            Tidak ada aksi
                        </div>
                    </slot>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faEllipsisVertical, faChevronDown, faFolderOpen } from '@fortawesome/free-solid-svg-icons'

const props = defineProps({
    label: {
        type: String,
        default: 'Opsi Data',
    },
    icon: {
        type: Object,
        default: null,
    },
    iconOnly: {
        type: Boolean,
        default: false,
    },
    showCaret: {
        type: Boolean,
        default: true,
    },
    menuTitle: {
        type: String,
        default: '',
    },
    buttonClass: {
        type: String,
        default: '',
    },
    align: {
        type: String,
        default: 'right',
        validator: value => ['left', 'right'].includes(value),
    },
    menuWidth: {
        type: Number,
        default: 190,
    },
    title: {
        type: String,
        default: '',
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
    const width = Math.min(props.menuWidth, viewportWidth - 16)
    const estimatedHeight = 220

    // Vertical positioning: default below, flip to top if overflowing bottom
    let top = rect.bottom + 6
    if (top + estimatedHeight > viewportHeight && rect.top > estimatedHeight) {
        top = Math.max(8, rect.top - estimatedHeight - 6)
    }

    // Horizontal positioning: align right or left
    let left = rect.left
    if (props.align === 'right') {
        left = rect.right - width
    }

    // Viewport bounds constraint
    if (left < 8) left = 8
    if (left + width > viewportWidth - 8) {
        left = Math.max(8, viewportWidth - width - 8)
    }

    menuStyle.value = {
        top: `${top}px`,
        left: `${left}px`,
        minWidth: `${width}px`,
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
    if (item.disabled) return
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

const handleKeyDown = event => {
    if (event.key === 'Escape' && isOpen.value) {
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
    document.addEventListener('keydown', handleKeyDown)
    window.addEventListener('scroll', handleScrollOrResize, true)
    window.addEventListener('resize', handleScrollOrResize)
})

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside)
    document.removeEventListener('keydown', handleKeyDown)
    window.removeEventListener('scroll', handleScrollOrResize, true)
    window.removeEventListener('resize', handleScrollOrResize)
})
</script>
