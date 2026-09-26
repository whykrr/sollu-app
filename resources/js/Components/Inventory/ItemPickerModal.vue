<template>
    <Modal
        :show="show"
        :title="title"
        size="max-w-2xl"
        :show-close="true"
        :close-on-backdrop="true"
        @close="handleClose"
    >
        <!-- Modal Content Container with Stable Height -->
        <div
            class="flex flex-col h-[520px] max-h-[72vh] sm:h-[550px] sm:max-h-[78vh] space-y-2.5 text-left"
        >
            <!-- 1. Search Bar & Filter Header (Menggunakan FilterSearch terstandarisasi) -->
            <div class="shrink-0 space-y-2">
                <FilterSearch
                    id="item_picker_search"
                    v-model="searchQuery"
                    :placeholder="searchPlaceholder"
                    width-class="w-full"
                    @update:model-value="onSearchInput"
                    @clear="clearSearch"
                />

                <!-- Sub-bar: Info Count & Quick Batch Actions -->
                <div class="flex items-center justify-between text-xs px-0.5">
                    <div class="text-slate-500 flex items-center gap-1.5">
                        <span
                            >Menampilkan
                            <strong class="text-slate-700 font-semibold">{{ items.length }}</strong>
                            barang</span
                        >
                        <span
                            v-if="newlySelectedCount > 0"
                            class="inline-flex items-center font-semibold text-emerald-600"
                        >
                            • {{ newlySelectedCount }} baru dipilih
                        </span>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <button
                            type="button"
                            class="btn btn-outline-secondary btn-sm !h-[26px] !py-0 !px-2 text-[11px] inline-flex items-center gap-1 cursor-pointer"
                            :disabled="isLoading || selectableVisibleItems.length === 0"
                            @click="selectAllVisible"
                        >
                            <FontAwesomeIcon :icon="faCheckDouble" class="text-[10px]" />
                            <span>Pilih Semua</span>
                        </button>
                        <button
                            v-if="newlySelectedCount > 0"
                            type="button"
                            class="btn btn-outline-secondary btn-sm !h-[26px] !py-0 !px-2 text-[11px] inline-flex items-center gap-1 cursor-pointer text-slate-600"
                            @click="clearSelections"
                        >
                            <FontAwesomeIcon :icon="faRotateRight" class="text-[10px]" />
                            <span>Reset</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. Scrollable Products List Container (Fixed Stable Height & Smooth Scroll) -->
            <div
                class="flex-1 min-h-[260px] max-h-[340px] overflow-y-auto overscroll-contain floating-scroll border border-slate-200 rounded-xl bg-slate-50/40 p-2 space-y-1.5"
            >
                <!-- Loading Skeleton State -->
                <div v-if="isLoading" class="py-8 space-y-3 text-center">
                    <div class="flex items-center justify-center gap-2 text-xs text-slate-500">
                        <FontAwesomeIcon :icon="faSpinner" class="animate-spin text-main text-sm" />
                        <span>Memuat daftar barang inventori...</span>
                    </div>
                </div>

                <!-- Empty State (No items found) -->
                <div
                    v-else-if="items.length === 0"
                    class="h-full min-h-[220px] flex flex-col items-center justify-center p-6 text-center text-slate-400"
                >
                    <div
                        class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-2"
                    >
                        <FontAwesomeIcon :icon="faBoxesStacked" class="text-xl" />
                    </div>
                    <div class="text-xs font-semibold text-slate-700">
                        {{
                            searchQuery
                                ? 'Barang tidak ditemukan'
                                : 'Belum ada barang pada outlet ini'
                        }}
                    </div>
                    <p class="text-[11px] text-slate-400 max-w-xs mt-0.5">
                        {{
                            searchQuery
                                ? 'Coba periksa kembali ejaan nama barang, SKU, atau barcode yang kamu cari.'
                                : 'Pastikan master produk & inventori sudah dibuat dan dialokasikan ke outlet ini.'
                        }}
                    </p>
                </div>

                <!-- List of Items -->
                <div
                    v-for="item in items"
                    v-else
                    :key="item.id"
                    class="flex items-center justify-between p-2.5 rounded-lg border transition-all select-none"
                    :class="[
                        isLocked(item.id)
                            ? 'bg-slate-100/90 border-slate-200 opacity-80 cursor-not-allowed'
                            : isNewlySelected(item.id)
                              ? 'bg-main/5 border-main/60 ring-1 ring-main/20 cursor-pointer'
                              : 'bg-white border-slate-200 hover:border-slate-300 hover:bg-slate-50/80 cursor-pointer',
                    ]"
                    @click="toggleItem(item)"
                >
                    <!-- Checkbox & Product Information -->
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <div class="flex items-center justify-center shrink-0">
                            <input
                                :id="'item_chk_' + item.id"
                                type="checkbox"
                                class="form-check-input h-4 w-4 rounded border-slate-300 text-main focus:ring-main"
                                :checked="isLocked(item.id) || isNewlySelected(item.id)"
                                :disabled="isLocked(item.id)"
                                @click.stop="toggleItem(item)"
                            />
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <label
                                    :for="'item_chk_' + item.id"
                                    class="font-bold text-xs sm:text-sm text-slate-800 truncate"
                                    :class="
                                        isLocked(item.id) ? 'cursor-not-allowed' : 'cursor-pointer'
                                    "
                                    @click.stop="toggleItem(item)"
                                >
                                    {{ item.name }}
                                </label>

                                <!-- Lock Badge for items already in the main form -->
                                <span
                                    v-if="isLocked(item.id)"
                                    class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-200/80 text-slate-600 border border-slate-300/60 shrink-0"
                                    title="Barang sudah ada di formulir (Terkunci)"
                                >
                                    <FontAwesomeIcon :icon="faLock" class="text-[9px]" />
                                    <span>Sudah di Form</span>
                                </span>

                                <!-- Newly Selected Badge -->
                                <span
                                    v-else-if="isNewlySelected(item.id)"
                                    class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-700 border border-emerald-200/80 shrink-0"
                                >
                                    <FontAwesomeIcon :icon="faCheck" class="text-[9px]" />
                                    <span>Dipilih</span>
                                </span>
                            </div>

                            <div
                                class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px] text-slate-500 mt-0.5"
                            >
                                <span>
                                    SKU:
                                    <strong class="font-medium text-slate-600">{{
                                        item.sku || '-'
                                    }}</strong>
                                </span>
                                <span class="text-slate-300">•</span>
                                <span>
                                    Satuan:
                                    <strong class="font-medium text-slate-600">{{
                                        item.uom?.name || item.uom_name || '-'
                                    }}</strong>
                                </span>
                                <template v-if="item.barcode">
                                    <span class="text-slate-300">•</span>
                                    <span>Barcode: {{ item.barcode }}</span>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Current Stock Display (unless hideStock is true) -->
                    <div v-if="!hideStock" class="text-right shrink-0 pl-3">
                        <div class="text-[10px] text-slate-400">Stok Saat Ini</div>
                        <div class="font-bold text-xs text-slate-700">
                            {{ formatStock(item.current_stock) }}
                            <span class="text-[11px] font-normal text-slate-500">
                                {{ item.uom?.name || item.uom_name || '' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Bottom Selected Items Container (Selection Tray) with Fixed Min-Height -->
            <div
                class="shrink-0 border border-slate-200 bg-slate-50/90 rounded-xl p-2.5 min-h-[76px] flex flex-col justify-center space-y-1.5"
            >
                <div v-if="newlySelectedList.length > 0" class="space-y-1.5">
                    <div class="flex items-center justify-between text-xs px-0.5">
                        <span class="font-bold text-slate-700 flex items-center gap-1.5">
                            <FontAwesomeIcon :icon="faBoxesStacked" class="text-main" />
                            <span>{{ newlySelectedList.length }} Barang Baru Terpilih</span>
                        </span>
                        <button
                            type="button"
                            class="text-xs text-rose-600 hover:text-rose-700 font-medium cursor-pointer hover:underline transition"
                            @click="clearSelections"
                        >
                            Hapus Semua
                        </button>
                    </div>

                    <!-- Selected Items Horizontal / Wrap Chips -->
                    <div class="flex flex-wrap items-center gap-1.5 max-h-16 overflow-y-auto pr-1">
                        <div
                            v-for="selected in newlySelectedList"
                            :key="selected.id"
                            class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg text-xs font-medium bg-white border border-slate-200 text-slate-700 shadow-2xs group"
                        >
                            <span class="truncate max-w-[160px] sm:max-w-[200px]">{{
                                selected.name
                            }}</span>
                            <button
                                type="button"
                                class="text-slate-400 hover:text-rose-600 rounded-full w-4 h-4 flex items-center justify-center cursor-pointer transition"
                                title="Batal pilih"
                                aria-label="Batal pilih item"
                                @click.stop="unselectItem(selected.id)"
                            >
                                <FontAwesomeIcon :icon="faXmark" class="text-[10px]" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Empty Selection Tray Placeholder (Maintains same container height) -->
                <div
                    v-else
                    class="text-xs text-slate-400 text-center py-2 flex items-center justify-center gap-1.5 select-none"
                >
                    <FontAwesomeIcon :icon="faBoxesStacked" class="text-slate-300" />
                    <span>Centang barang pada daftar di atas untuk menambahkan ke formulir.</span>
                </div>
            </div>
        </div>

        <!-- Modal Footer Actions (Teleported into Modal #footer) -->
        <template #footer>
            <div class="flex items-center justify-between w-full">
                <div class="text-xs text-slate-500 font-medium">
                    <span v-if="newlySelectedCount > 0" class="text-slate-700">
                        <strong class="text-main font-bold">{{ newlySelectedCount }}</strong> barang
                        siap ditambahkan
                    </span>
                    <span v-else class="text-slate-400"> Belum ada barang baru yang dipilih </span>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm h-[30px]"
                        @click="handleClose"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        class="btn btn-main btn-sm h-[30px] inline-flex items-center gap-1.5"
                        :disabled="newlySelectedCount === 0"
                        @click="confirmSelection"
                    >
                        <FontAwesomeIcon :icon="faPlus" />
                        <span>
                            {{
                                newlySelectedCount > 0
                                    ? `Tambahkan ${newlySelectedCount} Item`
                                    : 'Tambahkan Item'
                            }}
                        </span>
                    </button>
                </div>
            </div>
        </template>
    </Modal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'
import { debounce } from 'lodash'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faXmark,
    faCheck,
    faLock,
    faBoxesStacked,
    faRotateRight,
    faSpinner,
    faPlus,
    faCheckDouble,
} from '@fortawesome/free-solid-svg-icons'
import Modal from '@/Components/Notifications/Modal.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    outletId: {
        type: [String, Number, null],
        default: null,
    },
    supplierId: {
        type: [String, Number, null],
        default: null,
    },
    apiUrl: {
        type: String,
        default: () => {
            try {
                return route('api.internal.inventory-items.search')
            } catch {
                return '/api/internal/inventory-items/search'
            }
        },
    },
    alreadySelectedIds: {
        type: Array,
        default: () => [],
    },
    title: {
        type: String,
        default: 'Pilih Barang Inventori',
    },
    searchPlaceholder: {
        type: String,
        default: 'Cari nama barang, SKU, atau barcode...',
    },
    hideStock: {
        type: Boolean,
        default: false,
    },
    itemType: {
        type: String,
        default: '',
    },
})

const emit = defineEmits(['close', 'selected'])

const searchQuery = ref('')
const items = ref([])
const isLoading = ref(false)
const selectedItemsMap = ref(new Map())

// Check if an item is already present in the parent form (Locked)
const isLocked = itemId => {
    if (!props.alreadySelectedIds || !Array.isArray(props.alreadySelectedIds)) {
        return false
    }
    return props.alreadySelectedIds.some(id => String(id) === String(itemId))
}

// Check if an item is selected newly in this modal session
const isNewlySelected = itemId => {
    return selectedItemsMap.value.has(String(itemId)) && !isLocked(itemId)
}

// Array of newly selected item objects
const newlySelectedList = computed(() => {
    return Array.from(selectedItemsMap.value.values()).filter(item => !isLocked(item.id))
})

const newlySelectedCount = computed(() => newlySelectedList.value.length)

// Visible items in the search results that are not locked
const selectableVisibleItems = computed(() => {
    return items.value.filter(item => !isLocked(item.id))
})

const formatStock = val => {
    const num = Number(val ?? 0)
    return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(num)
}

// Fetch items from the search endpoint
const fetchItems = async () => {
    isLoading.value = true
    try {
        const params = {
            limit: 100,
        }

        if (searchQuery.value?.trim()) {
            params.search = searchQuery.value.trim()
            params.query = searchQuery.value.trim()
        }

        if (props.outletId) {
            params.outlet_id = props.outletId
        }

        if (props.supplierId) {
            params.supplier_id = props.supplierId
        }

        if (props.itemType) {
            params.item_type = props.itemType
        }

        const response = await axios.get(props.apiUrl, { params })
        const rawData = response.data

        if (Array.isArray(rawData)) {
            items.value = rawData
        } else if (Array.isArray(rawData?.data)) {
            items.value = rawData.data
        } else {
            items.value = []
        }
    } catch (error) {
        console.error('Error fetching inventory items for picker:', error)
        items.value = []
    } finally {
        isLoading.value = false
    }
}

const onSearchInput = debounce(() => {
    fetchItems()
}, 300)

const clearSearch = () => {
    searchQuery.value = ''
    fetchItems()
}

const toggleItem = item => {
    if (isLocked(item.id)) return

    const key = String(item.id)
    const nextMap = new Map(selectedItemsMap.value)

    if (nextMap.has(key)) {
        nextMap.delete(key)
    } else {
        nextMap.set(key, item)
    }

    selectedItemsMap.value = nextMap
}

const unselectItem = itemId => {
    const key = String(itemId)
    const nextMap = new Map(selectedItemsMap.value)
    nextMap.delete(key)
    selectedItemsMap.value = nextMap
}

const selectAllVisible = () => {
    const nextMap = new Map(selectedItemsMap.value)
    selectableVisibleItems.value.forEach(item => {
        nextMap.set(String(item.id), item)
    })
    selectedItemsMap.value = nextMap
}

const clearSelections = () => {
    selectedItemsMap.value = new Map()
}

const handleClose = () => {
    emit('close')
}

const confirmSelection = () => {
    if (newlySelectedCount.value === 0) return

    emit('selected', newlySelectedList.value)
    selectedItemsMap.value = new Map()
    emit('close')
}

// Watch modal visibility
watch(
    () => props.show,
    isOpen => {
        if (isOpen) {
            searchQuery.value = ''
            selectedItemsMap.value = new Map()
            fetchItems()
        }
    }
)
</script>
