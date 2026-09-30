<template>
    <Modal
        :show="show"
        title="Pilih Produk & Item Penjualan"
        size="max-w-3xl"
        :show-close="true"
        :close-on-backdrop="true"
        @close="handleClose"
    >
        <!-- Modal Content Container -->
        <div class="flex flex-col h-[540px] max-h-[75vh] space-y-2.5 text-left">
            <!-- 1. Search Bar & View Mode Toggle Header -->
            <div class="shrink-0 space-y-2">
                <div class="flex items-center gap-2">
                    <div class="flex-1">
                        <FilterSearch
                            id="sales_product_picker_search"
                            v-model="searchQuery"
                            placeholder="Cari nama barang, varian, SKU, atau barcode..."
                            width-class="w-full"
                            @update:model-value="onSearchInput"
                            @clear="clearSearch"
                        />
                    </div>

                    <!-- Dual View Mode Toggle -->
                    <div
                        class="flex items-center bg-slate-100 p-0.5 rounded-lg border border-slate-200 shrink-0"
                    >
                        <button
                            type="button"
                            class="px-2.5 py-1 text-xs font-medium rounded-md transition-all cursor-pointer"
                            :class="
                                viewMode === 'product'
                                    ? 'bg-white text-slate-800 shadow-2xs font-semibold'
                                    : 'text-slate-500 hover:text-slate-700'
                            "
                            @click="viewMode = 'product'"
                        >
                            Base on Produk
                        </button>
                        <button
                            type="button"
                            class="px-2.5 py-1 text-xs font-medium rounded-md transition-all cursor-pointer"
                            :class="
                                viewMode === 'variant'
                                    ? 'bg-white text-slate-800 shadow-2xs font-semibold'
                                    : 'text-slate-500 hover:text-slate-700'
                            "
                            @click="viewMode = 'variant'"
                        >
                            Base on Varian
                        </button>
                    </div>
                </div>

                <!-- Sub-bar: Info Count & Quick Batch Actions -->
                <div class="flex items-center justify-between text-xs px-0.5">
                    <div class="text-slate-500 flex items-center gap-1.5">
                        <span>
                            Menampilkan
                            <strong class="text-slate-700 font-semibold">{{
                                flatItems.length
                            }}</strong>
                            item
                        </span>
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

            <!-- 2. Products List Container -->
            <div
                class="flex-1 min-h-[250px] max-h-[320px] overflow-y-auto overscroll-contain floating-scroll border border-slate-200 rounded-xl bg-slate-50/40 p-2 space-y-1.5"
            >
                <!-- Loading State -->
                <div v-if="isLoading" class="py-10 space-y-3 text-center">
                    <div class="flex items-center justify-center gap-2 text-xs text-slate-500">
                        <FontAwesomeIcon :icon="faSpinner" class="animate-spin text-main text-sm" />
                        <span>Memuat katalog produk...</span>
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-else-if="flatItems.length === 0"
                    class="h-full min-h-[200px] flex flex-col items-center justify-center p-6 text-center text-slate-400"
                >
                    <div
                        class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-2"
                    >
                        <FontAwesomeIcon :icon="faBoxesStacked" class="text-xl" />
                    </div>
                    <div class="text-xs font-semibold text-slate-700">
                        {{
                            searchQuery
                                ? 'Produk tidak ditemukan'
                                : 'Belum ada produk pada outlet ini'
                        }}
                    </div>
                    <p class="text-[11px] text-slate-400 max-w-xs mt-0.5">
                        {{
                            searchQuery
                                ? 'Coba periksa kembali ejaan nama produk, SKU, atau barcode.'
                                : 'Pastikan produk sudah dibuat dan dialokasikan ke outlet yang dipilih.'
                        }}
                    </p>
                </div>

                <!-- Mode 1: Base on Product (Grouped by Parent Product) -->
                <template v-else-if="viewMode === 'product'">
                    <div
                        v-for="group in groupedProducts"
                        :key="group.productId"
                        class="border border-slate-200 rounded-lg bg-white overflow-hidden"
                    >
                        <!-- Group Header -->
                        <div
                            class="flex items-center justify-between p-2.5 bg-slate-50 hover:bg-slate-100/80 cursor-pointer select-none transition"
                            @click="toggleGroupExpand(group.productId)"
                        >
                            <div class="flex items-center gap-2">
                                <FontAwesomeIcon
                                    :icon="
                                        isGroupExpanded(group.productId)
                                            ? faChevronDown
                                            : faChevronRight
                                    "
                                    class="text-xs text-slate-400 transition-transform"
                                />
                                <span class="font-bold text-xs sm:text-sm text-slate-800">
                                    {{ group.productName }}
                                </span>
                                <span
                                    class="text-[10px] px-1.5 py-0.5 rounded bg-slate-200 text-slate-600 font-medium"
                                >
                                    {{ group.items.length }} varian/item
                                </span>
                            </div>
                            <div
                                class="text-xs text-slate-500 font-medium flex items-center gap-1.5"
                            >
                                <span
                                    v-if="group.items[0]?.category_name"
                                    class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200"
                                >
                                    {{ group.items[0].category_name }}
                                </span>
                                <span
                                    v-if="group.items[0]?.product_type === 'service'"
                                    class="text-[10px] px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200 font-medium"
                                >
                                    Jasa
                                </span>
                                <span
                                    v-else-if="group.items[0]?.product_type === 'bundle'"
                                    class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200 font-medium"
                                >
                                    Paket
                                </span>
                                <span v-if="group.items.length > 1" class="text-slate-400">
                                    (Klik untuk expand)
                                </span>
                            </div>
                        </div>

                        <!-- Variant Rows inside Group -->
                        <div
                            v-show="isGroupExpanded(group.productId)"
                            class="divide-y divide-slate-100 p-1"
                        >
                            <div
                                v-for="item in group.items"
                                :key="item.id"
                                class="flex items-center justify-between p-2 rounded-md transition select-none"
                                :class="[
                                    isLocked(item.id)
                                        ? 'bg-slate-100/90 opacity-80 cursor-not-allowed'
                                        : isNewlySelected(item.id)
                                          ? 'bg-main/5 border border-main/40'
                                          : 'hover:bg-slate-50 cursor-pointer',
                                ]"
                                @click="toggleItem(item)"
                            >
                                <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                    <input
                                        type="checkbox"
                                        class="form-check-input h-4 w-4 rounded border-slate-300 text-main focus:ring-main shrink-0"
                                        :checked="isLocked(item.id) || isNewlySelected(item.id)"
                                        :disabled="isLocked(item.id)"
                                        @click.stop="toggleItem(item)"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-xs font-semibold text-slate-800 truncate"
                                            >
                                                {{ item.name }}
                                            </span>
                                            <span
                                                v-if="isLocked(item.id)"
                                                class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded text-[9px] font-semibold bg-slate-200 text-slate-600 shrink-0"
                                            >
                                                <FontAwesomeIcon
                                                    :icon="faLock"
                                                    class="text-[8px]"
                                                />
                                                Sudah di Form
                                            </span>
                                            <span
                                                v-else-if="isNewlySelected(item.id)"
                                                class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded text-[9px] font-semibold bg-emerald-100 text-emerald-700 shrink-0"
                                            >
                                                <FontAwesomeIcon
                                                    :icon="faCheck"
                                                    class="text-[8px]"
                                                />
                                                Dipilih
                                            </span>
                                        </div>
                                        <div
                                            class="flex items-center gap-2 text-[10px] text-slate-500"
                                        >
                                            <span>SKU: {{ item.sku || '-' }}</span>
                                            <span>•</span>
                                            <span>Satuan: {{ item.uom || 'Pcs' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-right shrink-0 pl-2">
                                    <div class="font-bold text-xs text-slate-800">
                                        {{ formatCurrency(item.price) }}
                                    </div>
                                    <div
                                        v-if="item.active_promos && item.active_promos.length > 0"
                                        class="text-[10px] text-emerald-600 font-medium"
                                    >
                                        {{ item.active_promos[0].name }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Mode 2: Base on Variant (Flat list including single/non-variant products) -->
                <template v-else>
                    <div
                        v-for="item in flatItems"
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
                        <div class="flex items-center gap-3 min-w-0 flex-1">
                            <input
                                type="checkbox"
                                class="form-check-input h-4 w-4 rounded border-slate-300 text-main focus:ring-main shrink-0"
                                :checked="isLocked(item.id) || isNewlySelected(item.id)"
                                :disabled="isLocked(item.id)"
                                @click.stop="toggleItem(item)"
                            />

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="font-bold text-xs sm:text-sm text-slate-800 truncate"
                                    >
                                        {{ item.name }}
                                    </span>

                                    <span
                                        v-if="isLocked(item.id)"
                                        class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-200/80 text-slate-600 border border-slate-300/60 shrink-0"
                                    >
                                        <FontAwesomeIcon :icon="faLock" class="text-[9px]" />
                                        Sudah di Form
                                    </span>

                                    <span
                                        v-else-if="isNewlySelected(item.id)"
                                        class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-700 border border-emerald-200/80 shrink-0"
                                    >
                                        <FontAwesomeIcon :icon="faCheck" class="text-[9px]" />
                                        Dipilih
                                    </span>

                                    <span
                                        v-if="item.product_type === 'service'"
                                        class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-medium bg-amber-50 text-amber-700 border border-amber-200 shrink-0"
                                    >
                                        Jasa
                                    </span>
                                    <span
                                        v-else-if="item.product_type === 'bundle'"
                                        class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-medium bg-indigo-50 text-indigo-700 border border-indigo-200 shrink-0"
                                    >
                                        Paket
                                    </span>
                                </div>

                                <div
                                    class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px] text-slate-500 mt-0.5"
                                >
                                    <span
                                        >SKU:
                                        <strong class="font-medium text-slate-600">{{
                                            item.sku || '-'
                                        }}</strong></span
                                    >
                                    <span class="text-slate-300">•</span>
                                    <span
                                        >Satuan:
                                        <strong class="font-medium text-slate-600">{{
                                            item.uom || 'Pcs'
                                        }}</strong></span
                                    >
                                    <template v-if="item.category_name">
                                        <span class="text-slate-300">•</span>
                                        <span
                                            >Kategori:
                                            <strong class="font-medium text-slate-600">{{
                                                item.category_name
                                            }}</strong></span
                                        >
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div class="text-right shrink-0 pl-3">
                            <div class="font-bold text-xs text-slate-800">
                                {{ formatCurrency(item.price) }}
                            </div>
                            <div
                                v-if="item.active_promos && item.active_promos.length > 0"
                                class="text-[10px] text-emerald-600 font-medium"
                            >
                                {{ item.active_promos[0].name }}
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- 3. Selection Tray -->
            <div
                class="shrink-0 border border-slate-200 bg-slate-50/90 rounded-xl p-2.5 min-h-[76px] flex flex-col justify-center space-y-1.5"
            >
                <div v-if="newlySelectedList.length > 0" class="space-y-1.5">
                    <div class="flex items-center justify-between text-xs px-0.5">
                        <span class="font-bold text-slate-700 flex items-center gap-1.5">
                            <FontAwesomeIcon :icon="faBoxesStacked" class="text-main" />
                            <span>{{ newlySelectedList.length }} Item Baru Terpilih</span>
                        </span>
                        <button
                            type="button"
                            class="text-xs text-rose-600 hover:text-rose-700 font-medium cursor-pointer hover:underline transition"
                            @click="clearSelections"
                        >
                            Hapus Semua
                        </button>
                    </div>

                    <div class="flex flex-wrap items-center gap-1.5 max-h-16 overflow-y-auto pr-1">
                        <div
                            v-for="selected in newlySelectedList"
                            :key="selected.id"
                            class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg text-xs font-medium bg-white border border-slate-200 text-slate-700 shadow-2xs group"
                        >
                            <span class="truncate max-w-[160px] sm:max-w-[220px]">{{
                                selected.name
                            }}</span>
                            <button
                                type="button"
                                class="text-slate-400 hover:text-rose-600 rounded-full w-4 h-4 flex items-center justify-center cursor-pointer transition"
                                title="Batal pilih"
                                @click.stop="unselectItem(selected.id)"
                            >
                                <FontAwesomeIcon :icon="faXmark" class="text-[10px]" />
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="text-xs text-slate-400 text-center py-2 flex items-center justify-center gap-1.5 select-none"
                >
                    <FontAwesomeIcon :icon="faBoxesStacked" class="text-slate-300" />
                    <span
                        >Centang barang pada daftar di atas untuk menambahkan ke formulir
                        penjualan.</span
                    >
                </div>
            </div>
        </div>

        <!-- 4. Modal Footer -->
        <template #footer>
            <div class="flex items-center justify-between w-full">
                <div class="text-xs text-slate-500 font-medium">
                    <span v-if="newlySelectedCount > 0" class="text-slate-700">
                        <strong class="text-main font-bold">{{ newlySelectedCount }}</strong> item
                        siap ditambahkan
                    </span>
                    <span v-else class="text-slate-400">Belum ada item baru yang dipilih</span>
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
                        <span>{{
                            newlySelectedCount > 0
                                ? `Tambahkan ${newlySelectedCount} Item`
                                : 'Tambahkan Item'
                        }}</span>
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
    faChevronDown,
    faChevronRight,
} from '@fortawesome/free-solid-svg-icons'
import Modal from '@/Components/Notifications/Modal.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'
import { formatIDR as formatCurrency } from '@/Composable/currency-format.js'

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    outletId: {
        type: [String, Number, null],
        default: null,
    },
    alreadySelectedIds: {
        type: Array,
        default: () => [],
    },
    apiUrl: {
        type: String,
        default: () => {
            try {
                return route('api.internal.products.items')
            } catch {
                return '/api/internal/products/items'
            }
        },
    },
})

const emit = defineEmits(['close', 'selected'])

const viewMode = ref('variant') // 'product' | 'variant'
const searchQuery = ref('')
const flatItems = ref([])
const isLoading = ref(false)
const selectedItemsMap = ref(new Map())
const expandedGroups = ref(new Set())

const isLocked = itemId => {
    if (!props.alreadySelectedIds || !Array.isArray(props.alreadySelectedIds)) {
        return false
    }
    return props.alreadySelectedIds.some(id => String(id) === String(itemId))
}

const isNewlySelected = itemId => {
    return selectedItemsMap.value.has(String(itemId)) && !isLocked(itemId)
}

const newlySelectedList = computed(() => {
    return Array.from(selectedItemsMap.value.values()).filter(item => !isLocked(item.id))
})

const newlySelectedCount = computed(() => newlySelectedList.value.length)

const selectableVisibleItems = computed(() => {
    return flatItems.value.filter(item => !isLocked(item.id))
})

const groupedProducts = computed(() => {
    const groups = {}
    flatItems.value.forEach(item => {
        const pId = item.product_id || item.id
        if (!groups[pId]) {
            groups[pId] = {
                productId: pId,
                productName: item.product_name || item.name,
                items: [],
            }
        }
        groups[pId].items.push(item)
    })
    return Object.values(groups)
})

const isGroupExpanded = productId => {
    return expandedGroups.value.has(String(productId))
}

const toggleGroupExpand = productId => {
    const key = String(productId)
    const nextSet = new Set(expandedGroups.value)
    if (nextSet.has(key)) {
        nextSet.delete(key)
    } else {
        nextSet.add(key)
    }
    expandedGroups.value = nextSet
}

let abortController = null

const fetchItems = async () => {
    if (abortController) {
        abortController.abort()
    }
    abortController = new AbortController()

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

        const response = await axios.get(props.apiUrl, {
            params,
            signal: abortController.signal,
        })
        const rawData = response.data

        if (Array.isArray(rawData)) {
            flatItems.value = rawData
        } else if (Array.isArray(rawData?.data)) {
            flatItems.value = rawData.data
        } else {
            flatItems.value = []
        }

        // Auto-expand all groups on initial fetch or search
        expandedGroups.value = new Set(flatItems.value.map(i => String(i.product_id || i.id)))
    } catch (error) {
        if (axios.isCancel(error) || error?.name === 'CanceledError' || error?.name === 'AbortError') {
            return
        }
        console.error('Error fetching products for picker:', error)
        flatItems.value = []
    } finally {
        if (!abortController?.signal?.aborted) {
            isLoading.value = false
        }
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
