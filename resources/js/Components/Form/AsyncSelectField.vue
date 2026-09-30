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
                <!-- Display Section -->
                <div class="flex items-center gap-1.5 min-w-0 flex-1 truncate">
                    <FontAwesomeIcon
                        v-if="icon"
                        :icon="icon"
                        class="text-xs text-neutral-400 shrink-0"
                    />

                    <!-- Single Mode Display -->
                    <template v-if="!multiple">
                        <span
                            class="truncate"
                            :class="{ 'text-slate-800 font-medium': hasActiveValue }"
                        >
                            {{ displayLabel }}
                        </span>
                    </template>

                    <!-- Multiple Mode Display -->
                    <template v-else>
                        <div
                            v-if="selectedChips.length > 0"
                            class="flex items-center gap-1 flex-wrap min-w-0"
                        >
                            <span
                                v-for="chip in visibleChips"
                                :key="chip.value"
                                class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-slate-100 text-slate-800 text-[11px] font-medium rounded border border-slate-200 max-w-[130px] truncate"
                            >
                                <span class="truncate">{{ chip.label }}</span>
                                <button
                                    v-if="!disabled"
                                    type="button"
                                    class="text-neutral-400 hover:text-danger cursor-pointer shrink-0"
                                    title="Hapus item"
                                    @click.stop="removeChip(chip.value)"
                                >
                                    ✕
                                </button>
                            </span>
                            <span
                                v-if="overflowCount > 0"
                                class="inline-flex items-center px-1.5 py-0.5 bg-slate-100 text-slate-600 text-[11px] font-medium rounded border border-slate-200 shrink-0"
                            >
                                +{{ overflowCount }} lainnya
                            </span>
                        </div>
                        <span v-else class="text-slate-400 truncate">
                            {{ placeholder }}
                        </span>
                    </template>
                </div>

                <!-- Right Actions: Clear button & Chevron -->
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
        <span v-else-if="feedbackMessage" class="form-feedback text-neutral-500">{{
            feedbackMessage
        }}</span>

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
                    <div class="px-1 pt-1 pb-1">
                        <div class="relative flex items-center">
                            <input
                                ref="searchInputRef"
                                v-model="searchQuery"
                                type="text"
                                class="form sm w-full pr-7"
                                :placeholder="searchPlaceholder"
                                autocomplete="off"
                                @input="debouncedSearch"
                                @keydown.esc.stop="close"
                            />
                            <div
                                v-if="isLoading"
                                class="absolute right-2 text-neutral-400 flex items-center"
                            >
                                <FontAwesomeIcon :icon="faSpinner" class="animate-spin text-xs" />
                            </div>
                            <button
                                v-else-if="searchQuery"
                                type="button"
                                class="absolute right-2 text-neutral-400 hover:text-neutral-600 text-xs p-0.5 cursor-pointer"
                                @click="clearSearch"
                            >
                                ✕
                            </button>
                        </div>
                    </div>

                    <!-- Options List -->
                    <div class="max-h-60 overflow-y-auto space-y-0.5 overscroll-contain">
                        <!-- Loading State for Initial Load -->
                        <div
                            v-if="isLoading && results.length === 0"
                            class="px-3 py-4 text-center text-xs text-neutral-400 flex items-center justify-center gap-2"
                        >
                            <FontAwesomeIcon :icon="faSpinner" class="animate-spin text-sm" />
                            <span>Memuat data...</span>
                        </div>

                        <!-- Results List -->
                        <template v-else-if="results.length > 0">
                            <button
                                v-for="(item, index) in results"
                                :key="getItemKey(item, index)"
                                type="button"
                                class="flex w-full items-center justify-between rounded-lg px-2.5 py-1.5 text-left transition cursor-pointer"
                                :class="[
                                    isItemSelected(item)
                                        ? 'bg-primary-50 text-primary-700 font-semibold'
                                        : 'text-neutral-700 hover:bg-gray-50',
                                    { 'opacity-50 cursor-not-allowed': item.disabled },
                                ]"
                                :disabled="item.disabled"
                                @click="selectItem(item)"
                            >
                                <div class="min-w-0 flex-1">
                                    <slot
                                        name="option"
                                        :item="item"
                                        :selected="isItemSelected(item)"
                                    >
                                        <div class="truncate">{{ getOptionLabel(item) }}</div>
                                        <div
                                            v-if="item.description"
                                            class="text-[11px] text-neutral-400 truncate"
                                        >
                                            {{ item.description }}
                                        </div>
                                    </slot>
                                </div>

                                <div class="flex items-center gap-1.5 shrink-0 ml-2">
                                    <FontAwesomeIcon
                                        v-if="isItemSelected(item)"
                                        :icon="faCheck"
                                        class="text-xs text-primary-600"
                                    />
                                </div>
                            </button>
                        </template>

                        <!-- Empty State -->
                        <div
                            v-else-if="!isLoading"
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
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import axios from 'axios'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faChevronDown, faCheck, faSpinner } from '@fortawesome/free-solid-svg-icons'

defineOptions({
    inheritAttrs: false,
})

const props = defineProps({
    id: {
        type: String,
        default: () => `async-select-${Math.random().toString(36).substring(2, 9)}`,
    },
    modelValue: {
        type: [String, Number, Array, Object],
        default: null,
    },
    selectedLabel: {
        type: [String, Array, Object],
        default: '',
    },
    multiple: {
        type: Boolean,
        default: false,
    },
    closeOnSelect: {
        type: Boolean,
        default: null,
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
        default: true,
    },
    resetOnSelect: {
        type: Boolean,
        default: false,
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

    // API Configuration
    apiUrl: {
        type: String,
        required: true,
    },
    apiParams: {
        type: Object,
        default: () => ({}),
    },
    searchParamName: {
        type: String,
        default: 'query',
    },
    limitParamName: {
        type: String,
        default: 'limit',
    },
    limit: {
        type: Number,
        default: 10,
    },
    debounceTime: {
        type: Number,
        default: 300,
    },
    minChars: {
        type: Number,
        default: 0,
    },

    // Value & Label Resolvers
    optionValue: {
        type: [String, Function],
        default: 'value',
    },
    optionLabel: {
        type: [String, Function],
        default: 'label',
    },
})

const emit = defineEmits([
    'update:modelValue',
    'update:selectedLabel',
    'select',
    'remove',
    'change',
    'clear',
])

const inputId = computed(() => props.id)
const isOpen = ref(false)
const searchQuery = ref('')
const results = ref([])
const initialResults = ref([])
const isInitialLoaded = ref(false)
const isLoading = ref(false)

const containerRef = ref(null)
const triggerRef = ref(null)
const menuRef = ref(null)
const searchInputRef = ref(null)
const menuStyle = ref({})

let searchTimeout = null

// Internal tracked items for labels
const internalSelectedMap = ref(new Map())

const feedbackMessage = computed(() => props.feedback || '')

const getOptionValue = item => {
    if (typeof props.optionValue === 'function') {
        return props.optionValue(item)
    }
    if (typeof item === 'object' && item !== null) {
        if (item[props.optionValue] !== undefined) return item[props.optionValue]
        if (item.value !== undefined) return item.value
        if (item.id !== undefined) return item.id
        return item
    }
    return item
}

const getOptionLabel = item => {
    if (typeof props.optionLabel === 'function') {
        return props.optionLabel(item)
    }
    if (typeof item === 'object' && item !== null) {
        if (item[props.optionLabel] !== undefined) return item[props.optionLabel]
        if (item.label !== undefined) return item.label
        if (item.name !== undefined) return item.name
        return String(getOptionValue(item))
    }
    return String(item ?? '')
}

const getItemKey = (item, index) => {
    const val = getOptionValue(item)
    return val !== undefined && val !== null ? String(val) : index
}

// Check whether an item is selected
const isItemSelected = item => {
    const val = getOptionValue(item)
    if (props.multiple) {
        if (!Array.isArray(props.modelValue)) return false
        return props.modelValue.some(v => String(v) === String(val))
    }
    if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') {
        return false
    }
    return String(props.modelValue) === String(val)
}

const hasActiveValue = computed(() => {
    if (props.multiple) {
        return Array.isArray(props.modelValue) && props.modelValue.length > 0
    }
    return props.modelValue !== null && props.modelValue !== undefined && props.modelValue !== ''
})

// Display label for Single Mode
const displayLabel = computed(() => {
    if (!hasActiveValue.value) return props.placeholder

    // 1. External selectedLabel prop has highest priority
    if (typeof props.selectedLabel === 'string' && props.selectedLabel.trim() !== '') {
        return props.selectedLabel
    }
    if (
        typeof props.selectedLabel === 'object' &&
        props.selectedLabel !== null &&
        !Array.isArray(props.selectedLabel)
    ) {
        return getOptionLabel(props.selectedLabel)
    }

    // 2. Tracked item in map
    const valKey = String(props.modelValue)
    if (internalSelectedMap.value.has(valKey)) {
        return internalSelectedMap.value.get(valKey)
    }

    // 3. Current results or initial results
    const found =
        results.value.find(item => String(getOptionValue(item)) === valKey) ||
        initialResults.value.find(item => String(getOptionValue(item)) === valKey)
    if (found) {
        return getOptionLabel(found)
    }

    return props.placeholder
})

// Chips list for Multiple Mode
const selectedChips = computed(() => {
    if (!props.multiple || !Array.isArray(props.modelValue)) return []

    return props.modelValue.map(val => {
        const valKey = String(val)
        let label = valKey

        if (internalSelectedMap.value.has(valKey)) {
            label = internalSelectedMap.value.get(valKey)
        } else {
            const found =
                results.value.find(item => String(getOptionValue(item)) === valKey) ||
                initialResults.value.find(item => String(getOptionValue(item)) === valKey)
            if (found) {
                label = getOptionLabel(found)
            } else if (Array.isArray(props.selectedLabel)) {
                const ext = props.selectedLabel.find(
                    s => String(typeof s === 'object' ? getOptionValue(s) : s) === valKey
                )
                if (ext) {
                    label = typeof ext === 'object' ? getOptionLabel(ext) : String(ext)
                }
            }
        }

        return { value: val, label }
    })
})

const visibleChips = computed(() => selectedChips.value.slice(0, 2))
const overflowCount = computed(() => Math.max(0, selectedChips.value.length - 2))

// Parse API response
const parseResponseData = data => {
    if (Array.isArray(data)) return data
    if (data && Array.isArray(data.data)) return data.data
    return []
}

// Fetch Initial 10 Latest Items
const fetchInitial = async () => {
    isLoading.value = true
    try {
        const params = {
            ...props.apiParams,
            [props.searchParamName]: '',
            [props.limitParamName]: props.limit,
        }
        const res = await axios.get(props.apiUrl, { params })
        const parsed = parseResponseData(res.data)
        initialResults.value = parsed
        results.value = parsed
        isInitialLoaded.value = true

        // Cache any item that matches current modelValue
        parsed.forEach(item => {
            const v = String(getOptionValue(item))
            internalSelectedMap.value.set(v, getOptionLabel(item))
        })
    } catch (e) {
        console.error('AsyncSelectField initial load error:', e)
        results.value = []
    } finally {
        isLoading.value = false
    }
}

// Fetch Search Results (Debounced)
const fetchSearch = async query => {
    isLoading.value = true
    try {
        const params = {
            ...props.apiParams,
            [props.searchParamName]: query,
            [props.limitParamName]: props.limit,
        }
        const res = await axios.get(props.apiUrl, { params })
        results.value = parseResponseData(res.data)

        results.value.forEach(item => {
            const v = String(getOptionValue(item))
            internalSelectedMap.value.set(v, getOptionLabel(item))
        })
    } catch (e) {
        console.error('AsyncSelectField search error:', e)
        results.value = []
    } finally {
        isLoading.value = false
    }
}

const debouncedSearch = () => {
    clearTimeout(searchTimeout)

    const query = searchQuery.value.trim()
    if (!query) {
        // Instant restore to initial latest 10 items
        results.value = initialResults.value
        isLoading.value = false
        return
    }

    isLoading.value = true
    searchTimeout = setTimeout(() => {
        fetchSearch(query)
    }, props.debounceTime)
}

const clearSearch = () => {
    searchQuery.value = ''
    results.value = initialResults.value
    isLoading.value = false
}

// Position Floating Menu
const updatePosition = () => {
    if (!triggerRef.value) return
    const rect = triggerRef.value.getBoundingClientRect()
    const viewportWidth = window.innerWidth
    const viewportHeight = window.innerHeight

    const width = Math.max(rect.width, 240)
    const estimatedHeight = 280

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

    if (!isInitialLoaded.value) {
        fetchInitial()
    } else {
        results.value = initialResults.value
    }

    nextTick(() => {
        updatePosition()
        if (searchInputRef.value) {
            searchInputRef.value.focus()
        }
    })
}

const close = () => {
    isOpen.value = false
    searchQuery.value = ''
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

// Select / Deselect Items
const selectItem = item => {
    const val = getOptionValue(item)
    const lbl = getOptionLabel(item)

    if (val !== undefined && val !== null) {
        internalSelectedMap.value.set(String(val), lbl)
    }

    if (props.multiple) {
        const currentArr = Array.isArray(props.modelValue) ? [...props.modelValue] : []
        const existingIndex = currentArr.findIndex(v => String(v) === String(val))

        if (existingIndex > -1) {
            currentArr.splice(existingIndex, 1)
            emit('update:modelValue', currentArr)
            emit('change', currentArr)
            emit('remove', item)
        } else {
            currentArr.push(val)
            emit('update:modelValue', currentArr)
            emit('change', currentArr)
            emit('select', item)
        }

        const updatedLabels = currentArr.map(
            v => internalSelectedMap.value.get(String(v)) || String(v)
        )
        emit('update:selectedLabel', updatedLabels)

        const shouldClose = props.closeOnSelect !== null ? props.closeOnSelect : false
        if (shouldClose) {
            close()
        }
    } else {
        emit('update:modelValue', val)
        emit('update:selectedLabel', lbl)
        emit('select', item)
        emit('change', val)

        if (props.resetOnSelect) {
            emit('update:modelValue', null)
            emit('update:selectedLabel', '')
        }

        const shouldClose = props.closeOnSelect !== null ? props.closeOnSelect : true
        if (shouldClose) {
            close()
        }
    }
}

const removeChip = val => {
    if (!props.multiple || !Array.isArray(props.modelValue)) return
    const updated = props.modelValue.filter(v => String(v) !== String(val))
    emit('update:modelValue', updated)
    emit('change', updated)
    const updatedLabels = updated.map(v => internalSelectedMap.value.get(String(v)) || String(v))
    emit('update:selectedLabel', updatedLabels)
    emit('remove', { value: val })
}

const clearSelection = () => {
    if (props.multiple) {
        emit('update:modelValue', [])
        emit('update:selectedLabel', [])
        emit('change', [])
    } else {
        emit('update:modelValue', null)
        emit('update:selectedLabel', '')
        emit('change', null)
    }
    emit('clear')
}

// Synchronize external selectedLabel updates
watch(
    () => props.selectedLabel,
    newLabel => {
        if (typeof newLabel === 'string' && newLabel && props.modelValue !== null) {
            internalSelectedMap.value.set(String(props.modelValue), newLabel)
        } else if (Array.isArray(newLabel)) {
            newLabel.forEach(item => {
                if (typeof item === 'object' && item !== null) {
                    const v = String(getOptionValue(item))
                    internalSelectedMap.value.set(v, getOptionLabel(item))
                }
            })
        }
    },
    { immediate: true }
)

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
    clearTimeout(searchTimeout)
    document.removeEventListener('click', handleClickOutside)
    window.removeEventListener('scroll', handleScrollOrResize, true)
    window.removeEventListener('resize', handleScrollOrResize)
})
</script>
