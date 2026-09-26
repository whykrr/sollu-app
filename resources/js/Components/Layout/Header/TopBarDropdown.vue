<template>
    <div ref="dropdownRef" class="relative">
        <div>
            <slot name="trigger" :is-open="isOpen" :toggle="toggle" :close="close" :open="open" />
        </div>

        <!-- Mobile Teleport Wrapper for Backdrop and Dropdown Panel (< sm) -->
        <Teleport to="body" :disabled="!isMobile">
            <!-- Mobile Backdrop Overlay (< sm) -->
            <Transition
                enter-active-class="transition-opacity duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="isOpen && isMobile"
                    class="fixed inset-0 bg-black/30 backdrop-blur-xs z-[99]"
                    aria-hidden="true"
                    @click="close"
                />
            </Transition>

            <Transition name="fade-down">
                <div
                    v-if="isOpen"
                    class="fixed inset-x-3 max-w-[calc(100vw-1.5rem)] mx-auto top-16 sm:absolute sm:inset-auto sm:top-[48px] sm:max-w-none z-[100] bg-white border border-neutral-100 rounded-xl shadow-2xl ring-1 ring-black/5 p-4 max-h-[calc(100vh-5rem)] overflow-y-auto floating-scroll"
                    :class="[
                        align === 'left' ? 'sm:left-0 origin-top-left' : 'sm:right-0 origin-top-right',
                        widthClass,
                        panelClass,
                    ]"
                >
                    <div class="flex flex-col gap-2">
                        <!-- Close Button -->
                        <div
                            v-if="showCloseButton"
                            class="absolute right-2.5 top-2.5 sm:right-3 sm:top-3"
                        >
                            <button
                                type="button"
                                class="min-w-[40px] min-h-[40px] flex items-center justify-center rounded-lg text-neutral-400 hover:text-neutral-600 hover:bg-neutral-100 active:bg-neutral-200 transition-colors touch-manipulation cursor-pointer"
                                aria-label="Tutup"
                                @click.prevent="close"
                            >
                                <FontAwesomeIcon :icon="faClose" class="text-sm" />
                            </button>
                        </div>

                        <!-- Header Slot or Default Title -->
                        <slot name="header" :close="close">
                            <div
                                v-if="title"
                                class="text-center text-lg font-medium text-neutral-800 pr-8 pl-8 sm:pr-6 sm:pl-6"
                            >
                                {{ title }}
                            </div>
                        </slot>

                        <!-- Main Dropdown Content -->
                        <slot :close="close" :is-open="isOpen" />

                        <!-- Footer Slot -->
                        <slot name="footer" :close="close" />
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted, watch } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faClose } from '@fortawesome/free-solid-svg-icons'
import { useDropdown } from '@/Composable/useDropdown'

const props = defineProps({
    title: {
        type: String,
        default: '',
    },
    widthClass: {
        type: String,
        default: 'sm:w-80',
    },
    panelClass: {
        type: String,
        default: '',
    },
    align: {
        type: String,
        default: 'right',
        validator: value => ['left', 'right'].includes(value),
    },
    showCloseButton: {
        type: Boolean,
        default: true,
    },
    modelValue: {
        type: Boolean,
        default: undefined,
    },
})

const emit = defineEmits(['update:modelValue', 'open', 'close', 'toggle'])

const isMobile = ref(false)
const checkMobile = () => {
    if (typeof window !== 'undefined') {
        isMobile.value = window.innerWidth < 640
    }
}

onMounted(() => {
    checkMobile()
    window.addEventListener('resize', checkMobile)
})

onUnmounted(() => {
    window.removeEventListener('resize', checkMobile)
})

const {
    isOpen: internalIsOpen,
    toggle: internalToggle,
    close: internalClose,
    dropdownRef,
} = useDropdown()

const isOpen = computed(() => {
    return props.modelValue !== undefined ? props.modelValue : internalIsOpen.value
})

const toggle = () => {
    internalToggle()
    const newState = internalIsOpen.value
    emit('update:modelValue', newState)
    emit('toggle', newState)
    if (newState) {
        emit('open')
    } else {
        emit('close')
    }
}

const close = () => {
    internalClose()
    emit('update:modelValue', false)
    emit('close')
}

const open = () => {
    if (!isOpen.value) {
        internalIsOpen.value = true
        emit('update:modelValue', true)
        emit('open')
    }
}

watch(
    () => props.modelValue,
    val => {
        if (val !== undefined) {
            internalIsOpen.value = val
        }
    }
)

defineExpose({
    isOpen,
    toggle,
    close,
    open,
    dropdownRef,
})
</script>
