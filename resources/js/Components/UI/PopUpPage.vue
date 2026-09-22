<template>
    <aside
        class="fixed inset-0 z-50 flex justify-end p-0 sm:p-3 sm:pt-4 overflow-hidden transition-all duration-300"
        :class="
            show
                ? 'bg-slate-900/40 backdrop-blur-[2px] opacity-100 pointer-events-auto visible'
                : 'bg-transparent opacity-0 pointer-events-none invisible'
        "
        aria-labelledby="popUpTitle"
        role="dialog"
        aria-modal="true"
        @click.self="requestClose(false)"
    >
        <div
            class="w-full h-full h-[100dvh] max-w-full bg-white flex flex-col shadow-none border-0 rounded-none sm:h-full sm:w-full sm:shrink-0 sm:rounded-2xl sm:shadow-2xl sm:border sm:border-slate-200/90 overflow-hidden transform transition-transform duration-300 ease-out"
            :class="[show ? 'translate-x-0' : 'translate-x-full', computedSizeClass]"
        >
            <!-- Modal Header -->
            <div
                class="modal-header shrink-0 sticky top-0 z-20 bg-white border-b border-slate-100 px-4 py-3 sm:px-5 sm:py-4 pt-[max(0.75rem,env(safe-area-inset-top,0px))] sm:rounded-t-2xl flex items-center justify-between gap-3"
            >
                <div class="font-bold min-w-0 flex-1">
                    <h2
                        id="popUpTitle"
                        class="text-base sm:text-lg font-bold text-slate-900 truncate"
                    >
                        {{ title }}
                        <span
                            v-if="subTitle"
                            class="text-slate-400 font-normal text-xs sm:text-sm ml-1 inline-block"
                        >
                            {{ subTitle }}
                        </span>
                    </h2>
                </div>
                <button
                    id="closeModalBtn"
                    type="button"
                    class="text-slate-400 hover:text-slate-700 hover:bg-slate-100 active:bg-slate-200 rounded-lg w-9 h-9 sm:w-8 sm:h-8 flex items-center justify-center transition cursor-pointer shrink-0 touch-target-sm"
                    aria-label="Tutup"
                    @click="requestClose(false)"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4 sm:h-5 sm:w-5"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div
                class="modal-body p-4 sm:p-5 flex-1 overflow-y-auto overscroll-y-contain floating-scroll"
                @input="markDirty"
                @change="markDirty"
                @submit="onFormSubmit"
            >
                <!-- Dynamic Component Support -->
                <component
                    :is="component"
                    v-if="component"
                    v-bind="componentProps"
                    v-on="componentEvents || {}"
                    @close="handleChildClose"
                />
                <!-- Default Slot Fallback -->
                <slot v-else />
            </div>

            <!-- Modal Footer Container (Teleport target & slot fallback) -->
            <div
                id="popUpFooter"
                class="modal-footer sticky bottom-0 z-20 bg-white border-t border-slate-100 px-4 py-3 sm:px-5 sm:py-3.5 shrink-0 empty:hidden flex items-center justify-end gap-2 sm:gap-2.5 pb-[max(0.75rem,env(safe-area-inset-bottom,0px))] sm:rounded-b-2xl"
            >
                <slot name="footer" />
            </div>
        </div>
    </aside>
</template>

<script setup>
import { computed, ref, watch, provide, onMounted, onUnmounted } from 'vue'
import { useModalStore } from '@/store/notification'

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: '',
    },
    subTitle: {
        type: String,
        default: null,
    },
    size: {
        type: String,
        default: 'md',
    },
    // Dynamic Vue Component to render inside body
    component: {
        type: [Object, Function],
        default: null,
    },
    // Props passed directly to dynamic component
    componentProps: {
        type: Object,
        default: () => ({}),
    },
    // Event handlers passed directly to dynamic component
    componentEvents: {
        type: Object,
        default: () => ({}),
    },
})

const emit = defineEmits(['close'])

const modalStore = useModalStore()
const isDirty = ref(false)
const isSubmitting = ref(false)

const markDirty = () => {
    isDirty.value = true
}

const onFormSubmit = () => {
    isSubmitting.value = true
}

provide('popUpIsDirty', isDirty)
provide('setPopUpDirty', val => {
    isDirty.value = val
})

// Reset dirty & submitting status whenever the popup opens or changes
watch(
    () => props.show,
    newVal => {
        if (newVal) {
            isDirty.value = false
            isSubmitting.value = false
        }
    }
)

const closePage = () => {
    isDirty.value = false
    isSubmitting.value = false
    emit('close')
}

/**
 * Handle close requests with dirty validation guard
 * @param {boolean} force - If true, bypasses dirty confirmation
 */
const requestClose = (force = false) => {
    if (force || isSubmitting.value || !isDirty.value) {
        closePage()
        return
    }

    modalStore.confirm({
        title: 'Perubahan Belum Disimpan',
        message:
            'Kamu memiliki perubahan data yang belum disimpan. Yakin mau membatalkan dan keluar dari formulir ini?',
        type: 'warning',
        confirmText: 'Ya, Buang Perubahan',
        cancelText: 'Lanjut Mengisi',
        confirmClass: 'btn-danger bg-rose-600 hover:bg-rose-700 text-white',
        onConfirm: () => {
            closePage()
        },
        onCancel: () => {
            // Stay on the form, keep dirty state
        },
    })
}

const handleChildClose = payload => {
    const isForce = payload === true || (typeof payload === 'object' && payload?.force === true)
    requestClose(isForce)
}

// Handle global Escape key press
const handleKeyDown = e => {
    if (e.key === 'Escape' && props.show) {
        if (!modalStore.activeModal.isVisible) {
            e.preventDefault()
            requestClose(false)
        }
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown)
})

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown)
})

const sizeClasses = {
    sm: 'sm:max-w-md',
    md: 'sm:max-w-lg',
    lg: 'sm:max-w-xl lg:max-w-2xl',
    xl: 'sm:max-w-2xl lg:max-w-4xl',
    '2xl': 'sm:max-w-3xl lg:max-w-5xl',
}

const computedSizeClass = computed(() => {
    if (sizeClasses[props.size]) {
        return sizeClasses[props.size]
    }
    if (props.size && props.size.startsWith('max-w-')) {
        return `sm:${props.size}`
    }
    return props.size || 'sm:max-w-lg'
})
</script>
