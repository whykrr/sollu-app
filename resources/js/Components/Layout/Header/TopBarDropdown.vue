<template>
    <div ref="dropdownRef" class="relative">
        <div>
            <slot name="trigger" :is-open="isOpen" :toggle="toggle" :close="close" :open="open" />
        </div>

        <transition name="fade-down" mode="in-out">
            <div
                v-if="isOpen"
                class="fixed inset-x-3 top-16 sm:absolute sm:inset-auto sm:top-[48px] z-100 bg-white border border-neutral-100 rounded-xl shadow-2xl ring-1 ring-black/5 p-4 max-h-[calc(100vh-5rem)] overflow-y-auto floating-scroll"
                :class="[
                    align === 'left' ? 'sm:left-0 origin-top-left' : 'sm:right-0 origin-top-right',
                    widthClass,
                    panelClass,
                ]"
            >
                <div class="flex flex-col gap-2">
                    <!-- Close Button -->
                    <div v-if="showCloseButton" class="absolute right-4 top-4">
                        <a
                            href="#"
                            class="text-neutral-400 hover:text-neutral-600 transition-colors"
                            aria-label="Tutup"
                            @click.prevent="close"
                        >
                            <FontAwesomeIcon :icon="faClose" />
                        </a>
                    </div>

                    <!-- Header Slot or Default Title -->
                    <slot name="header" :close="close">
                        <div v-if="title" class="text-center text-lg font-medium text-neutral-800">
                            {{ title }}
                        </div>
                    </slot>

                    <!-- Main Dropdown Content -->
                    <slot :close="close" :is-open="isOpen" />

                    <!-- Footer Slot -->
                    <slot name="footer" :close="close" />
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { computed, watch } from 'vue'
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
