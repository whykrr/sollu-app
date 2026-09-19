<template>
    <div
        class="border rounded-xl transition-colors duration-150"
        :class="[
            hasError
                ? 'border-danger-300 bg-danger-50/20'
                : isOpen
                  ? 'border-slate-300 bg-slate-50/40'
                  : 'border-slate-200 bg-white hover:border-slate-300',
        ]"
    >
        <!-- Header / Toggle Button -->
        <button
            type="button"
            class="w-full flex items-center justify-between p-3 text-left select-none rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-main/20"
            :aria-expanded="isOpen"
            @click="toggle"
        >
            <div class="flex items-center gap-2.5 min-w-0 pr-2">
                <FontAwesomeIcon
                    v-if="icon"
                    :icon="icon"
                    class="text-xs transition-colors shrink-0"
                    :class="isOpen ? 'text-main' : 'text-slate-400'"
                />
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-700">
                            {{ title }}
                        </span>
                        <!-- Active count badge -->
                        <span
                            v-if="badge !== null && badge !== undefined && badge !== ''"
                            class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium"
                            :class="
                                hasError
                                    ? 'bg-danger-100 text-danger-700'
                                    : 'bg-slate-200 text-slate-700'
                            "
                        >
                            {{ badge }}
                        </span>
                        <!-- Error Indicator Dot -->
                        <span
                            v-else-if="hasError"
                            class="inline-block w-2 h-2 rounded-full bg-danger-500 animate-pulse"
                            title="Terdapat input yang perlu diperbaiki"
                        ></span>
                    </div>
                    <p v-if="description" class="text-xs text-slate-500 mt-0.5 truncate">
                        {{ description }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <span v-if="!isOpen && closedHint" class="text-xs text-slate-400 hidden sm:inline">
                    {{ closedHint }}
                </span>
                <div
                    class="w-6 h-6 flex items-center justify-center rounded-lg text-slate-400 transition-transform duration-200"
                    :class="{ 'rotate-180 text-slate-600': isOpen }"
                >
                    <FontAwesomeIcon :icon="faChevronDown" class="text-xs" />
                </div>
            </div>
        </button>

        <!-- Collapsible Body -->
        <div v-show="isOpen" class="px-3 pb-3 pt-1 border-t border-slate-100 space-y-2">
            <slot />
        </div>
    </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faChevronDown } from '@fortawesome/free-solid-svg-icons'

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: undefined,
    },
    defaultOpen: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    icon: {
        type: Object,
        default: null,
    },
    badge: {
        type: [String, Number],
        default: null,
    },
    closedHint: {
        type: String,
        default: '',
    },
    error: {
        type: [Boolean, String],
        default: false,
    },
})

const emit = defineEmits(['update:modelValue', 'toggle'])

const internalOpen = ref(props.defaultOpen)

const isOpen = computed(() => {
    return props.modelValue !== undefined ? props.modelValue : internalOpen.value
})

const hasError = computed(() => Boolean(props.error))

watch(
    () => props.error,
    newVal => {
        if (newVal && !isOpen.value) {
            // Auto open if error occurs inside collapsed section
            if (props.modelValue !== undefined) {
                emit('update:modelValue', true)
            } else {
                internalOpen.value = true
            }
        }
    }
)

const toggle = () => {
    const nextState = !isOpen.value
    if (props.modelValue !== undefined) {
        emit('update:modelValue', nextState)
    } else {
        internalOpen.value = nextState
    }
    emit('toggle', nextState)
}
</script>
