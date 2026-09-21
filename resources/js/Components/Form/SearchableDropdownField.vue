<template>
    <div ref="containerRef" class="relative">
        <label
            v-if="label"
            :for="inputId"
            class="label"
            :class="{ '!text-xs': size === 'sm', '!text-base': size === 'lg' }"
        >
            {{ label }}
            <span v-if="required" class="text-danger ml-0.5">*</span>
        </label>

        <div class="relative">
            <button
                :id="inputId"
                ref="triggerRef"
                type="button"
                class="form w-full text-left flex items-center justify-between transition cursor-pointer select-none"
                :class="[
                    { sm: size === 'sm', lg: size === 'lg', adaptive: size === 'adaptive' },
                    { 'is-invalid': error, 'is-valid': success },
                    { 'opacity-60 cursor-not-allowed bg-slate-50': disabled },
                    { 'text-slate-400': !hasActiveValue },
                ]"
                :disabled="disabled"
                @click="toggle"
                @keydown.down.prevent="openAndFocus"
                @keydown.esc.prevent="close"
            >
                <div class="flex items-center gap-2 min-w-0 flex-1 truncate">
                    <FontAwesomeIcon
                        v-if="selectedOption?.icon || icon"
                        :icon="selectedOption?.icon || icon"
                        class="text-xs text-neutral-400 shrink-0"
                    />
                    <span
                        class="truncate"
                        :class="{ 'text-slate-800 font-medium': hasActiveValue }"
                    >
                        {{ displayLabel }}
                    </span>
                </div>

                <div class="flex items-center gap-1.5 shrink-0 ml-2">
                    <button
                        v-if="clearable && hasActiveValue && !disabled"
                        type="button"
                        class="text-neutral-400 hover:text-danger text-xs p-0.5 rounded transition cursor-pointer"
                        title="Hapus pilihan"
                        @click.stop="clearSelection"
                    >
                        ✕
                    </button>
                    <FontAwesomeIcon
                        :icon="faChevronDown"
                        class="text-[10px] text-neutral-400 transition-transform duration-200"
                        :class="{ 'rotate-180': isOpen }"
                    />
                </div>
            </button>
        </div>

        <span v-if="error" class="form-feedback text-danger">{{ error }}</span>
        <span v-else-if="success" class="form-feedback text-success">{{ success }}</span>
        <span v-else-if="feedback" class="form-feedback text-neutral-500">{{ feedback }}</span>

        <!-- Floating Dropdown Teleport -->
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
                    class="fixed z-[9999] rounded-xl bg-white p-1.5 shadow-xl ring-1 ring-black/5 focus:outline-none border border-gray-200 text-xs sm:text-sm space-y-1"
                    :class="dropdownClass"
                    :style="menuStyle"
                >
                    <!-- Search input inside dropdown -->
                    <div v-if="isSearchable" class="px-1 pt-1 pb-1">
                        <div class="relative flex items-center">
                            <input
                                ref="searchInputRef"
                                v-model="searchQuery"
                                type="text"
                                class="form sm w-full pr-7"
                                :placeholder="searchPlaceholder"
                                @keydown.esc.stop="close"
                            />
                            <button
                                v-if="searchQuery"
                                type="button"
                                class="absolute right-2 text-neutral-400 hover:text-neutral-600 text-xs p-0.5 cursor-pointer"
                                @click="searchQuery = ''"
                            >
                                ✕
                            </button>
                        </div>
                    </div>

                    <!-- Option list -->
                    <div class="max-h-60 overflow-y-auto space-y-0.5 overscroll-contain">
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

                        <button
                            v-for="option in filteredOptions"
                            :key="option.value"
                            type="button"
                            class="flex w-full items-center justify-between rounded-lg px-2.5 py-1.5 text-left transition cursor-pointer"
                            :class="[
                                isSelected(option.value)
                                    ? 'bg-primary-50 text-primary-700 font-semibold'
                                    : 'text-neutral-700 hover:bg-gray-50',
                                { 'opacity-50 cursor-not-allowed': option.disabled },
                            ]"
                            :disabled="option.disabled"
                            @click="selectOption(option.value)"
                        >
                            <div class="flex items-center gap-2 min-w-0 flex-1">
                                <FontAwesomeIcon
                                    v-if="option.icon"
                                    :icon="option.icon"
                                    class="text-xs text-neutral-400 shrink-0"
                                />
                                <div class="min-w-0 flex-1">
                                    <div class="truncate">{{ option.label }}</div>
                                    <div
                                        v-if="option.description"
                                        class="text-[11px] text-neutral-400 truncate"
                                    >
                                        {{ option.description }}
                                    </div>
                                </div>
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
                            class="px-3 py-3 text-center text-xs text-neutral-400"
                        >
                            Pencarian tidak ditemukan
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

defineOptions({
    inheritAttrs: false,
})

const props = defineProps({
    id: {
        type: String,
        default: () => `searchable-dropdown-${Math.random().toString(36).substring(2, 9)}`,
    },
    modelValue: {
        type: [String, Number, Boolean],
        default: '',
    },
    label: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Pilih opsi...',
    },
    searchPlaceholder: {
        type: String,
        default: 'Cari...',
    },
    options: {
        type: Array,
        default: () => [],
    },
    icon: {
        type: [Object, Array],
        default: null,
    },
    feedback: {
        type: String,
        default: '',
    },
    error: {
        type: String,
        default: '',
    },
    success: {
        type: String,
        default: '',
    },
    required: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    clearable: {
        type: Boolean,
        default: false,
    },
    searchable: {
        type: Boolean,
        default: null,
    },
    showAllOption: {
        type: Boolean,
        default: false,
    },
    allOptionLabel: {
        type: String,
        default: 'Semua',
    },
    dropdownClass: {
        type: String,
        default: '',
    },
    size: {
        type: String,
        default: 'base',
        validator: v => ['sm', 'base', 'adaptive', 'lg'].includes(v),
    },
})

const emit = defineEmits(['update:modelValue', 'change', 'clear'])

const inputId = computed(() => props.id)
const isOpen = ref(false)
const searchQuery = ref('')
const containerRef = ref(null)
const triggerRef = ref(null)
const menuRef = ref(null)
const searchInputRef = ref(null)
const menuStyle = ref({})

const isSearchable = computed(() => {
    if (props.searchable !== null) return props.searchable
    return props.options.length >= 5
})

const hasActiveValue = computed(() => {
    return props.modelValue !== '' && props.modelValue !== null && props.modelValue !== undefined
})

const normalizedOptions = computed(() => {
    return props.options.map(opt => {
        if (typeof opt === 'object' && opt !== null) {
            return {
                value: opt.value !== undefined ? opt.value : opt.id,
                label: opt.label || opt.name || String(opt.value ?? ''),
                icon: opt.icon || null,
                description: opt.description || null,
                count: opt.count,
                disabled: Boolean(opt.disabled),
            }
        }
        return {
            value: opt,
            label: String(opt),
            icon: null,
            description: null,
            disabled: false,
        }
    })
})

const selectedOption = computed(() => {
    return normalizedOptions.value.find(o => String(o.value) === String(props.modelValue))
})

const displayLabel = computed(() => {
    if (selectedOption.value) {
        return selectedOption.value.label
    }
    return props.placeholder
})

const filteredOptions = computed(() => {
    if (!searchQuery.value) return normalizedOptions.value
    const query = searchQuery.value.toLowerCase().trim()
    return normalizedOptions.value.filter(opt => {
        const matchLabel = String(opt.label).toLowerCase().includes(query)
        const matchDesc = opt.description
            ? String(opt.description).toLowerCase().includes(query)
            : false
        return matchLabel || matchDesc
    })
})

const isSelected = val => {
    return String(props.modelValue ?? '') === String(val ?? '')
}

const updatePosition = () => {
    if (!triggerRef.value) return
    const rect = triggerRef.value.getBoundingClientRect()
    const viewportWidth = window.innerWidth
    const viewportHeight = window.innerHeight

    const width = Math.max(rect.width, 220)
    const estimatedHeight = 260

    let top = rect.bottom + 4
    if (top + estimatedHeight > viewportHeight && rect.top > estimatedHeight) {
        top = Math.max(8, rect.top - estimatedHeight - 4)
    }

    let left = rect.left
    if (left + width > viewportWidth - 8) {
        left = Math.max(8, viewportWidth - width - 8)
    }
    if (left < 8) left = 8

    menuStyle.value = {
        top: `${top}px`,
        left: `${left}px`,
        width: `${width}px`,
        maxWidth: `${viewportWidth - 16}px`,
    }
}

const open = () => {
    if (props.disabled) return
    isOpen.value = true
    searchQuery.value = ''
    nextTick(() => {
        updatePosition()
        if (isSearchable.value && searchInputRef.value) {
            searchInputRef.value.focus()
        }
    })
}

const close = () => {
    isOpen.value = false
}

const toggle = () => {
    if (isOpen.value) {
        close()
    } else {
        open()
    }
}

const openAndFocus = () => {
    if (!isOpen.value) {
        open()
    }
}

const selectOption = val => {
    emit('update:modelValue', val)
    emit('change', val)
    close()
}

const clearSelection = () => {
    emit('update:modelValue', '')
    emit('change', '')
    emit('clear')
}

const handleClickOutside = event => {
    if (!isOpen.value) return
    if (
        triggerRef.value &&
        !triggerRef.value.contains(event.target) &&
        menuRef.value &&
        !menuRef.value.contains(event.target)
    ) {
        close()
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
