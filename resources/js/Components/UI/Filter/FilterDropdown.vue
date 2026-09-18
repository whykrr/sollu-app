<template>
    <div ref="dropdownRef" class="relative inline-block text-left select-none shrink-0">
        <button
            ref="triggerRef"
            type="button"
            class="btn btn-sm h-[30px] bg-white border border-gray-200 hover:border-gray-300 rounded-lg inline-flex items-center gap-1.5 transition cursor-pointer"
            :class="
                hasActiveValue
                    ? 'border-primary-300 bg-primary-50/40 text-primary-800 font-semibold'
                    : 'text-neutral-700 font-medium'
            "
            @click="toggle"
        >
            <FontAwesomeIcon v-if="icon" :icon="icon" class="text-xs text-neutral-400" />
            <span class="text-xs leading-4">{{ triggerLabel }}</span>
            <FontAwesomeIcon
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
                    class="fixed z-[9999] min-w-[200px] max-w-[280px] rounded-xl bg-white p-1.5 shadow-xl ring-1 ring-black/5 focus:outline-none border border-gray-200 text-xs sm:text-sm space-y-1"
                    :style="menuStyle"
                >
                    <!-- Search within dropdown if options > 6 -->
                    <div v-if="isSearchable" class="px-1 pt-1 pb-1">
                        <input
                            v-model="searchQuery"
                            type="text"
                            class="form sm"
                            :placeholder="`Cari ${label.toLowerCase()}...`"
                        />
                    </div>

                    <div class="max-h-60 overflow-y-auto space-y-0.5">
                        <!-- Default / Reset Option -->
                        <button
                            v-if="showAllOption"
                            type="button"
                            class="flex w-full items-center justify-between rounded-lg px-2.5 py-1.5 text-left transition cursor-pointer"
                            :class="
                                !hasActiveValue
                                    ? 'bg-primary-50 text-primary-700 font-semibold'
                                    : 'text-neutral-700 hover:bg-gray-50'
                            "
                            @click="selectOption('')"
                        >
                            <span>{{ allOptionLabel }}</span>
                            <FontAwesomeIcon
                                v-if="!hasActiveValue"
                                :icon="faCheck"
                                class="text-xs text-primary-600"
                            />
                        </button>

                        <!-- Filtered Options -->
                        <button
                            v-for="option in filteredOptions"
                            :key="option.value"
                            type="button"
                            class="flex w-full items-center justify-between rounded-lg px-2.5 py-1.5 text-left transition cursor-pointer"
                            :class="
                                isSelected(option.value)
                                    ? 'bg-primary-50 text-primary-700 font-semibold'
                                    : 'text-neutral-700 hover:bg-gray-50'
                            "
                            @click="selectOption(option.value)"
                        >
                            <div class="flex items-center gap-1.5 min-w-0">
                                <FontAwesomeIcon
                                    v-if="option.icon"
                                    :icon="option.icon"
                                    class="text-xs text-neutral-400"
                                />
                                <span class="truncate">{{ option.label }}</span>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0 ml-2">
                                <span
                                    v-if="option.count !== undefined"
                                    class="text-[10px] text-neutral-400 font-normal"
                                >
                                    {{ option.count }}
                                </span>
                                <FontAwesomeIcon
                                    v-if="isSelected(option.value)"
                                    :icon="faCheck"
                                    class="text-xs text-primary-600"
                                />
                            </div>
                        </button>

                        <div
                            v-if="filteredOptions.length === 0"
                            class="px-3 py-2 text-center text-xs text-neutral-400"
                        >
                            Tidak ditemukan
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faChevronDown, faCheck } from '@fortawesome/free-solid-svg-icons'

const props = defineProps({
    modelValue: {
        type: [String, Number, Boolean],
        default: '',
    },
    label: {
        type: String,
        required: true,
    },
    placeholder: {
        type: String,
        default: '',
    },
    options: {
        type: Array,
        default: () => [],
    },
    icon: {
        type: Object,
        default: null,
    },
    align: {
        type: String,
        default: 'left',
    },
    showAllOption: {
        type: Boolean,
        default: true,
    },
    allOptionLabel: {
        type: String,
        default: 'Semua',
    },
    searchable: {
        type: Boolean,
        default: null,
    },
})

const emit = defineEmits(['update:modelValue', 'change'])

const isOpen = ref(false)
const searchQuery = ref('')
const dropdownRef = ref(null)
const triggerRef = ref(null)
const menuRef = ref(null)
const menuStyle = ref({})

const isSearchable = computed(() => {
    if (props.searchable !== null) return props.searchable
    return props.options.length > 6
})

const hasActiveValue = computed(() => {
    return props.modelValue !== '' && props.modelValue !== null && props.modelValue !== undefined
})

const selectedOption = computed(() => {
    return props.options.find(o => String(o.value) === String(props.modelValue))
})

const triggerLabel = computed(() => {
    if (!hasActiveValue.value) {
        return props.placeholder || props.label
    }
    const optLabel = selectedOption.value?.label || props.modelValue
    return `${props.label}: ${optLabel}`
})

const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.options
    const query = searchQuery.value.toLowerCase()
    return props.options.filter(opt => String(opt.label).toLowerCase().includes(query))
})

const isSelected = val => {
    return String(props.modelValue ?? '') === String(val ?? '')
}

const updatePosition = () => {
    if (!triggerRef.value) return
    const rect = triggerRef.value.getBoundingClientRect()
    const viewportWidth = window.innerWidth
    const viewportHeight = window.innerHeight

    const menuWidth = 220
    const estimatedHeight = 260

    let top = rect.bottom + 6
    if (top + estimatedHeight > viewportHeight && rect.top > estimatedHeight) {
        top = Math.max(8, rect.top - estimatedHeight - 6)
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

const toggle = () => {
    isOpen.value = !isOpen.value
    if (isOpen.value) {
        searchQuery.value = ''
        nextTick(() => {
            updatePosition()
        })
    }
}

const selectOption = val => {
    emit('update:modelValue', val)
    emit('change', val)
    isOpen.value = false
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
