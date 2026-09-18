<template>
    <div class="flex items-center gap-1.5 select-none shrink-0">
        <!-- Preset Selector Dropdown -->
        <div ref="presetDropdownRef" class="relative inline-block text-left">
            <button
                ref="presetTriggerRef"
                type="button"
                class="btn btn-sm h-[30px] bg-white border border-gray-200 hover:border-gray-300 text-neutral-700 font-medium rounded-lg inline-flex items-center gap-1.5 transition cursor-pointer"
                @click="togglePresetDropdown"
            >
                <span class="text-xs leading-4">{{ activePresetLabel }}</span>
                <FontAwesomeIcon
                    :icon="faChevronDown"
                    class="text-[10px] text-neutral-400 transition-transform duration-200"
                    :class="{ 'rotate-180': isPresetOpen }"
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
                        v-if="isPresetOpen"
                        ref="presetMenuRef"
                        class="fixed z-[9999] w-44 rounded-xl bg-white p-1 shadow-lg ring-1 ring-black/5 focus:outline-none border border-gray-200 text-xs sm:text-sm"
                        :style="presetStyle"
                    >
                        <button
                            v-for="preset in presetOptions"
                            :key="preset.value"
                            type="button"
                            class="flex w-full items-center justify-between rounded-lg px-2.5 py-1.5 text-left transition cursor-pointer"
                            :class="
                                currentPreset === preset.value
                                    ? 'bg-primary-50 text-primary-700 font-semibold'
                                    : 'text-neutral-700 hover:bg-gray-50'
                            "
                            @click="selectPreset(preset.value)"
                        >
                            <span>{{ preset.label }}</span>
                            <FontAwesomeIcon
                                v-if="currentPreset === preset.value"
                                :icon="faCheck"
                                class="text-xs text-primary-600"
                            />
                        </button>
                    </div>
                </Transition>
            </Teleport>
        </div>

        <!-- Date Range Display & Popover Trigger -->
        <div v-if="showRangeDisplay" ref="rangeDropdownRef" class="relative inline-block text-left">
            <button
                ref="rangeTriggerRef"
                type="button"
                class="btn btn-sm h-[30px] bg-white border border-gray-200 hover:border-gray-300 text-neutral-700 font-normal rounded-lg inline-flex items-center gap-1.5 transition cursor-pointer"
                @click="toggleRangeDropdown"
            >
                <FontAwesomeIcon :icon="faCalendarAlt" class="text-xs text-neutral-400" />
                <span class="text-xs leading-4">{{ formattedRangeLabel }}</span>
                <FontAwesomeIcon
                    :icon="faChevronDown"
                    class="text-[10px] text-neutral-400 transition-transform duration-200"
                    :class="{ 'rotate-180': isRangeOpen }"
                />
            </button>

            <!-- Custom Date Range Popover -->
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
                        v-if="isRangeOpen"
                        ref="rangeMenuRef"
                        class="fixed z-[9999] w-72 rounded-xl bg-white p-3 shadow-xl ring-1 ring-black/5 focus:outline-none border border-gray-200 space-y-3"
                        :style="rangeStyle"
                    >
                        <div class="text-xs font-bold text-neutral-800 uppercase tracking-wider">
                            Pilih Rentang Tanggal
                        </div>

                        <div class="space-y-2">
                            <div class="space-y-1">
                                <label class="block text-[11px] font-medium text-neutral-500">
                                    Dari Tanggal
                                </label>
                                <input v-model="tempStartDate" type="date" class="form sm" />
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[11px] font-medium text-neutral-500">
                                    Sampai Tanggal
                                </label>
                                <input v-model="tempEndDate" type="date" class="form sm" />
                            </div>
                        </div>

                        <div
                            class="flex items-center justify-end gap-1.5 pt-1 border-t border-gray-100"
                        >
                            <button
                                type="button"
                                class="btn btn-xs border border-gray-200 hover:bg-gray-50 text-neutral-600 rounded-md"
                                @click="isRangeOpen = false"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                class="btn btn-xs btn-highlight-main rounded-md"
                                @click="applyCustomRange"
                            >
                                Terapkan
                            </button>
                        </div>
                    </div>
                </Transition>
            </Teleport>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCalendarAlt, faChevronDown, faCheck } from '@fortawesome/free-solid-svg-icons'

const props = defineProps({
    modelValue: {
        type: String,
        default: 'this_month',
    },
    startDate: {
        type: String,
        default: '',
    },
    endDate: {
        type: String,
        default: '',
    },
    showRangeDisplay: {
        type: Boolean,
        default: true,
    },
})

const emit = defineEmits(['update:modelValue', 'update:startDate', 'update:endDate', 'change'])

const isPresetOpen = ref(false)
const isRangeOpen = ref(false)
const presetDropdownRef = ref(null)
const rangeDropdownRef = ref(null)

const presetTriggerRef = ref(null)
const presetMenuRef = ref(null)
const presetStyle = ref({})

const rangeTriggerRef = ref(null)
const rangeMenuRef = ref(null)
const rangeStyle = ref({})

const currentPreset = ref(props.modelValue || 'this_month')
const activeStartDate = ref(props.startDate || '')
const activeEndDate = ref(props.endDate || '')

const tempStartDate = ref('')
const tempEndDate = ref('')

const presetOptions = [
    { value: 'today', label: 'Hari Ini' },
    { value: 'yesterday', label: 'Kemarin' },
    { value: 'last_7_days', label: '7 Hari Terakhir' },
    { value: 'last_30_days', label: '30 Hari Terakhir' },
    { value: 'this_month', label: 'Bulan Ini' },
    { value: 'last_month', label: 'Bulan Lalu' },
    { value: 'this_year', label: 'Tahun Ini' },
    { value: 'custom', label: 'Kustom' },
]

const activePresetLabel = computed(() => {
    return presetOptions.find(p => p.value === currentPreset.value)?.label || 'Bulan Ini'
})

const formatDateIndo = dateStr => {
    if (!dateStr) return ''
    const parts = dateStr.split('-')
    if (parts.length !== 3) return dateStr
    const [year, month, day] = parts
    const monthNames = [
        'Jan',
        'Feb',
        'Mar',
        'Apr',
        'Mei',
        'Jun',
        'Jul',
        'Agu',
        'Sep',
        'Okt',
        'Nov',
        'Des',
    ]
    const mIdx = parseInt(month, 10) - 1
    return `${parseInt(day, 10)} ${monthNames[mIdx] || month}`
}

const formattedRangeLabel = computed(() => {
    if (activeStartDate.value && activeEndDate.value) {
        return `${formatDateIndo(activeStartDate.value)} - ${formatDateIndo(activeEndDate.value)}`
    }
    if (activeStartDate.value) {
        return `Sejak ${formatDateIndo(activeStartDate.value)}`
    }
    if (activeEndDate.value) {
        return `Hingga ${formatDateIndo(activeEndDate.value)}`
    }
    return 'Pilih Tanggal'
})

const padZero = num => String(num).padStart(2, '0')
const toYmd = date =>
    `${date.getFullYear()}-${padZero(date.getMonth() + 1)}-${padZero(date.getDate())}`

const computeRangeForPreset = presetKey => {
    const now = new Date()
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())

    switch (presetKey) {
        case 'today':
            return {
                start: toYmd(today),
                end: toYmd(today),
            }
        case 'yesterday': {
            const yesterday = new Date(today)
            yesterday.setDate(yesterday.getDate() - 1)
            return {
                start: toYmd(yesterday),
                end: toYmd(yesterday),
            }
        }
        case 'last_7_days': {
            const past7 = new Date(today)
            past7.setDate(past7.getDate() - 6)
            return {
                start: toYmd(past7),
                end: toYmd(today),
            }
        }
        case 'last_30_days': {
            const past30 = new Date(today)
            past30.setDate(past30.getDate() - 29)
            return {
                start: toYmd(past30),
                end: toYmd(today),
            }
        }
        case 'this_month': {
            const startMonth = new Date(today.getFullYear(), today.getMonth(), 1)
            const endMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0)
            return {
                start: toYmd(startMonth),
                end: toYmd(endMonth),
            }
        }
        case 'last_month': {
            const startLastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1)
            const endLastMonth = new Date(today.getFullYear(), today.getMonth(), 0)
            return {
                start: toYmd(startLastMonth),
                end: toYmd(endLastMonth),
            }
        }
        case 'this_year': {
            const startYear = new Date(today.getFullYear(), 0, 1)
            const endYear = new Date(today.getFullYear(), 11, 31)
            return {
                start: toYmd(startYear),
                end: toYmd(endYear),
            }
        }
        case 'custom':
        default:
            return {
                start: activeStartDate.value,
                end: activeEndDate.value,
            }
    }
}

// Inisialisasi awal tanggal jika belum diisi
if (!activeStartDate.value && !activeEndDate.value && currentPreset.value !== 'custom') {
    const initialRange = computeRangeForPreset(currentPreset.value)
    activeStartDate.value = initialRange.start
    activeEndDate.value = initialRange.end
}

const updatePresetPosition = () => {
    if (!presetTriggerRef.value) return
    const rect = presetTriggerRef.value.getBoundingClientRect()
    const viewportWidth = window.innerWidth
    const viewportHeight = window.innerHeight
    const menuWidth = 176
    const menuHeight = 270

    let top = rect.bottom + 6
    if (top + menuHeight > viewportHeight && rect.top > menuHeight) {
        top = Math.max(8, rect.top - menuHeight - 6)
    }

    let left = rect.left
    if (left < 8) left = 8
    if (left + menuWidth > viewportWidth - 8) {
        left = Math.max(8, viewportWidth - menuWidth - 8)
    }

    presetStyle.value = {
        top: `${top}px`,
        left: `${left}px`,
    }
}

const updateRangePosition = () => {
    if (!rangeTriggerRef.value) return
    const rect = rangeTriggerRef.value.getBoundingClientRect()
    const viewportWidth = window.innerWidth
    const viewportHeight = window.innerHeight
    const menuWidth = 288
    const menuHeight = 200

    let top = rect.bottom + 6
    if (top + menuHeight > viewportHeight && rect.top > menuHeight) {
        top = Math.max(8, rect.top - menuHeight - 6)
    }

    let left = rect.left
    if (left < 8) left = 8
    if (left + menuWidth > viewportWidth - 8) {
        left = Math.max(8, viewportWidth - menuWidth - 8)
    }

    rangeStyle.value = {
        top: `${top}px`,
        left: `${left}px`,
    }
}

const togglePresetDropdown = () => {
    isPresetOpen.value = !isPresetOpen.value
    if (isPresetOpen.value) {
        isRangeOpen.value = false
        nextTick(() => {
            updatePresetPosition()
        })
    }
}

const toggleRangeDropdown = () => {
    isRangeOpen.value = !isRangeOpen.value
    if (isRangeOpen.value) {
        isPresetOpen.value = false
        tempStartDate.value = activeStartDate.value
        tempEndDate.value = activeEndDate.value
        nextTick(() => {
            updateRangePosition()
        })
    }
}

const selectPreset = presetVal => {
    currentPreset.value = presetVal
    isPresetOpen.value = false

    if (presetVal === 'custom') {
        tempStartDate.value = activeStartDate.value
        tempEndDate.value = activeEndDate.value
        isRangeOpen.value = true
        nextTick(() => {
            updateRangePosition()
        })
        return
    }

    const { start, end } = computeRangeForPreset(presetVal)
    activeStartDate.value = start
    activeEndDate.value = end

    emit('update:modelValue', presetVal)
    emit('update:startDate', start)
    emit('update:endDate', end)
    emit('change', { preset: presetVal, startDate: start, endDate: end })
}

const applyCustomRange = () => {
    currentPreset.value = 'custom'
    activeStartDate.value = tempStartDate.value
    activeEndDate.value = tempEndDate.value
    isRangeOpen.value = false

    emit('update:modelValue', 'custom')
    emit('update:startDate', activeStartDate.value)
    emit('update:endDate', activeEndDate.value)
    emit('change', {
        preset: 'custom',
        startDate: activeStartDate.value,
        endDate: activeEndDate.value,
    })
}

const handleClickOutside = event => {
    if (
        isPresetOpen.value &&
        presetTriggerRef.value &&
        !presetTriggerRef.value.contains(event.target) &&
        presetMenuRef.value &&
        !presetMenuRef.value.contains(event.target)
    ) {
        isPresetOpen.value = false
    }
    if (
        isRangeOpen.value &&
        rangeTriggerRef.value &&
        !rangeTriggerRef.value.contains(event.target) &&
        rangeMenuRef.value &&
        !rangeMenuRef.value.contains(event.target)
    ) {
        isRangeOpen.value = false
    }
}

const handleScrollOrResize = () => {
    if (isPresetOpen.value) updatePresetPosition()
    if (isRangeOpen.value) updateRangePosition()
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
