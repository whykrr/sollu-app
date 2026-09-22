<template>
    <div>
        <form class="space-y-3" @submit.prevent="confirmSubmit(opname ? 'submit' : 'save')">
            <!-- Info Dokumen Jika Mode Edit / Update -->
            <div
                v-if="opname"
                class="flex flex-col sm:flex-row sm:items-center justify-between p-3 bg-slate-50 border border-slate-200 rounded-lg gap-2 text-xs"
            >
                <div>
                    <div class="text-slate-500">Nomor Opname</div>
                    <div class="font-bold text-slate-800 text-sm">
                        {{ opname.opname_number }}
                    </div>
                </div>
                <div>
                    <div class="text-slate-500">Outlet</div>
                    <div class="font-semibold text-slate-800">
                        {{ opname.outlet?.name || '-' }}
                    </div>
                </div>
                <div>
                    <div class="text-slate-500">Status</div>
                    <span
                        class="badge"
                        :class="getColor('StockOpnameStatus', opname.status) || 'badge-warning'"
                    >
                        {{ getLabel('StockOpnameStatus', opname.status) }}
                    </span>
                </div>
            </div>

            <!-- Pilihan Outlet: HANYA jika buat baru, outlet user > 1 DAN tidak ada selectedOutlet aktif -->
            <div
                v-if="!opname && outletOptions.length > 1 && !selectedOutlet"
                class="grid grid-cols-1 gap-2"
            >
                <SearchableDropdownField
                    id="outlet_id"
                    v-model="form.outlet_id"
                    label="Pilih Outlet"
                    placeholder="Pilih Outlet..."
                    search-placeholder="Cari outlet..."
                    :options="outletOptions"
                    :error="form.errors.outlet_id"
                    required
                />
            </div>

            <TextareaField
                id="notes"
                v-model="form.notes"
                label="Catatan Sesi (Opsional)"
                placeholder="Tambahkan keterangan kondisi lapangan atau catatan opname..."
                :class="{ 'is-invalid': form.errors.notes }"
                :error="form.errors.notes"
                rows="2"
            />

            <!-- Section Daftar Barang & Input Fisik -->
            <div class="border-t border-slate-200 pt-3 space-y-2">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">Pencatatan Fisik Stok</h3>
                        <p class="text-xs text-slate-500">
                            Hitung dan masukkan jumlah fisik aktual untuk masing-masing barang.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="w-full sm:w-72">
                            <AsyncSelectField
                                id="search_item"
                                label="Cari Barang"
                                placeholder="Cari nama, SKU, barcode..."
                                class="sm"
                                :api-url="route('api.internal.inventory-items.search')"
                                :api-params="{
                                    outlet_id: opname ? opname.outlet_id : form.outlet_id,
                                }"
                                :min-chars="2"
                                :disabled="!activeOutletId"
                                @select="addItemFromSearch"
                            >
                                <template #option="{ item }">
                                    <div class="flex items-center justify-between w-full">
                                        <div>
                                            <div class="font-semibold text-xs text-slate-800">
                                                {{ item.name }}
                                            </div>
                                            <div class="text-[11px] text-slate-400">
                                                SKU: {{ item.sku || '-' }}
                                            </div>
                                        </div>
                                        <div class="text-right text-[11px] text-slate-500">
                                            Sistem:
                                            <span class="font-medium text-slate-700">
                                                {{ Number(item.current_stock ?? 0) }}
                                            </span>
                                            {{ item.uom?.name || '' }}
                                        </div>
                                    </div>
                                </template>
                            </AsyncSelectField>
                        </div>
                        <button
                            type="button"
                            class="btn btn-outline-secondary btn-sm h-[30px] inline-flex items-center gap-1.5 cursor-pointer"
                            :disabled="!activeOutletId || isLoadingItems"
                            @click="loadAllItems(false)"
                        >
                            <span>{{ isLoadingItems ? 'Memuat...' : 'Muat Semua Barang' }}</span>
                        </button>
                    </div>
                </div>

                <div v-if="form.errors.items" class="text-danger text-xs mb-1">
                    {{ form.errors.items }}
                </div>

                <!-- Petunjuk Jika Outlet Belum Terpilih -->
                <div
                    v-if="!activeOutletId"
                    class="text-center py-6 text-xs text-slate-500 border border-dashed border-slate-300 rounded-lg bg-slate-50/50"
                >
                    Pilih outlet terlebih dahulu untuk memulai pencatatan fisik stok.
                </div>

                <!-- Empty State Jika Belum Ada Item -->
                <div
                    v-else-if="form.items.length === 0"
                    class="text-center py-6 text-xs text-slate-500 border border-dashed border-slate-300 rounded-lg bg-slate-50/50"
                >
                    Belum ada barang ditambahkan. Silakan cari barang atau klik tombol <strong>Muat Semua Barang</strong> di atas.
                </div>

                <!-- Daftar Item Opname -->
                <div v-else class="space-y-2 max-h-96 overflow-y-auto pr-1">
                    <div
                        v-for="(item, index) in form.items"
                        :key="item.inventory_item_id || index"
                        class="p-3 border border-slate-200 rounded-lg bg-white space-y-2"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="text-xs font-bold text-slate-400 w-5 text-center">
                                    {{ index + 1 }}.
                                </span>
                                <div class="min-w-0">
                                    <div class="font-bold text-xs text-slate-800 truncate">
                                        {{ item.name }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 truncate">
                                        SKU: {{ item.sku || '-' }} | Satuan:
                                        <span class="font-medium text-slate-600">{{ item.uom || '-' }}</span>
                                    </div>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="btn btn-flat btn-sm text-danger h-7 w-7 !p-0 inline-flex items-center justify-center cursor-pointer"
                                title="Hapus item"
                                @click="removeItem(index)"
                            >
                                <FontAwesomeIcon :icon="faTrash" />
                            </button>
                        </div>

                        <!-- Grid Perhitungan: Stok Sistem, Stok Fisik, Selisih -->
                        <div class="grid grid-cols-12 gap-2 pt-1 border-t border-slate-100 items-center">
                            <div class="col-span-12 sm:col-span-4">
                                <div class="text-[11px] text-slate-500 mb-1">Stok Sistem</div>
                                <div class="bg-slate-50 border border-slate-200 text-slate-700 font-semibold text-center py-1.5 rounded text-xs">
                                    {{ item.system_qty }} {{ item.uom || '' }}
                                </div>
                            </div>

                            <div class="col-span-12 sm:col-span-4">
                                <NumberField
                                    :id="'actual_' + index"
                                    v-model="item.actual_qty"
                                    type="number"
                                    label="Stok Fisik"
                                    placeholder="0"
                                    min="0"
                                    step="any"
                                    class="sm"
                                    :class="{
                                        'is-invalid': form.errors[`items.${index}.actual_qty`],
                                    }"
                                    :error="form.errors[`items.${index}.actual_qty`]"
                                    required
                                />
                            </div>

                            <div class="col-span-12 sm:col-span-4">
                                <div class="text-[11px] text-slate-500 mb-1 text-center">Selisih Fisik</div>
                                <div
                                    class="font-bold text-sm text-center py-1 rounded bg-slate-50/50 border border-dashed border-slate-200"
                                    :class="differenceColor(item.actual_qty, item.system_qty)"
                                >
                                    {{ formatDifference(item.actual_qty, item.system_qty) }} {{ item.uom || '' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tombol Muat Lebih Banyak Jika Paginasi Masih Ada -->
                <div v-if="hasMoreItems" class="text-center pt-2">
                    <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm h-[30px]"
                        :disabled="isLoadingItems"
                        @click="loadAllItems(true)"
                    >
                        {{ isLoadingItems ? 'Memuat...' : 'Muat Lebih Banyak Barang' }}
                    </button>
                </div>

                <!-- Ringkasan Total Hasil Opname -->
                <div
                    v-if="form.items.length > 0"
                    class="grid grid-cols-2 sm:grid-cols-5 gap-2 p-3 bg-slate-50 border border-slate-200 rounded-lg text-xs mt-2"
                >
                    <div class="text-center">
                        <div class="text-slate-500 text-[11px]">Total Barang</div>
                        <div class="font-bold text-slate-800 text-sm mt-0.5">
                            {{ summary.totalItems }}
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="text-slate-500 text-[11px]">Cocok</div>
                        <div class="font-bold text-slate-700 text-sm mt-0.5">
                            {{ summary.matched }}
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="text-slate-500 text-[11px]">Berselisih</div>
                        <div class="font-bold text-slate-800 text-sm mt-0.5">
                            {{ summary.diff }}
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="text-slate-500 text-[11px]">Total Surplus</div>
                        <div class="font-bold text-emerald-600 text-sm mt-0.5">
                            +{{ summary.surplus }}
                        </div>
                    </div>
                    <div class="text-center col-span-2 sm:col-span-1">
                        <div class="text-slate-500 text-[11px]">Total Shortage</div>
                        <div class="font-bold text-danger text-sm mt-0.5">
                            -{{ summary.shortage }}
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <Teleport v-if="isMounted" to="#popUpFooter">
            <button
                type="button"
                class="btn btn-flat"
                :disabled="form.processing"
                @click="close"
            >
                Batal
            </button>
            <button
                v-if="!opname"
                type="button"
                class="btn btn-main"
                :disabled="form.processing || form.items.length === 0 || !activeOutletId"
                @click="confirmSubmit('save')"
            >
                Mulai Opname
            </button>
            <button
                v-if="opname"
                type="button"
                class="btn btn-info"
                :disabled="form.processing || form.items.length === 0"
                @click="confirmSubmit('submit')"
            >
                Ajukan Persetujuan
            </button>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import axios from 'axios'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faTrash } from '@fortawesome/free-solid-svg-icons'
import { useAuth } from '@/Composable/useAuth'
import { useEnum } from '@/Composable/useEnum'
import { useModalStore } from '@/store/notification'
import { usePopUpStore } from '@/store/popup'
import TextareaField from '@/Components/Form/TextareaField.vue'
import SearchableDropdownField from '@/Components/Form/SearchableDropdownField.vue'
import AsyncSelectField from '@/Components/Form/AsyncSelectField.vue'
import NumberField from '@/Components/Form/NumberField.vue'

const props = defineProps({
    opname: {
        type: Object,
        default: null,
    },
})

const emit = defineEmits(['close'])
const modalStore = useModalStore()
const popUpStore = usePopUpStore()
const { outlets: userOutlets, selectedOutlet } = useAuth()
const { getLabel, getColor } = useEnum()

const isMounted = ref(false)
const currentPage = ref(1)
const hasMoreItems = ref(false)
const isLoadingItems = ref(false)

const outletOptions = computed(() =>
    (userOutlets.value || []).map(store => ({
        label: store.name,
        value: store.id,
    }))
)

const initialOutletId = props.opname
    ? props.opname.outlet_id
    : selectedOutlet.value?.id || (userOutlets.value?.length === 1 ? userOutlets.value[0].id : '')

const form = useForm({
    outlet_id: initialOutletId,
    notes: props.opname?.notes || '',
    items: [],
})

const activeOutletId = computed(() => props.opname?.outlet_id || form.outlet_id)

onMounted(() => {
    isMounted.value = true
    currentPage.value = 1
    hasMoreItems.value = false

    if (props.opname && props.opname.items) {
        form.items = props.opname.items.map(i => ({
            inventory_item_id: i.inventory_item_id,
            name: i.inventory_item?.name || '-',
            sku: i.inventory_item?.sku || '-',
            uom: i.inventory_item?.uom?.name || '-',
            system_qty: Number(i.system_qty ?? 0),
            actual_qty: Number(i.actual_qty ?? i.system_qty ?? 0),
        }))
    }
})

// Watch outlet change on create mode: reset items
watch(
    () => form.outlet_id,
    (newVal, oldVal) => {
        if (oldVal && newVal !== oldVal && !props.opname) {
            form.items = []
        }
    }
)

const summary = computed(() => {
    let totalItems = form.items.length
    let matched = 0
    let diffCount = 0
    let surplus = 0
    let shortage = 0

    form.items.forEach(item => {
        const diff = Number(item.actual_qty || 0) - Number(item.system_qty || 0)
        if (diff === 0) {
            matched++
        } else {
            diffCount++
            if (diff > 0) surplus += diff
            if (diff < 0) shortage += Math.abs(diff)
        }
    })

    return {
        totalItems,
        matched,
        diff: diffCount,
        surplus: Number(surplus.toFixed(2)),
        shortage: Number(shortage.toFixed(2)),
    }
})

const addItemFromSearch = item => {
    const exists = form.items.find(i => i.inventory_item_id === item.id)
    if (!exists) {
        form.items.unshift({
            inventory_item_id: item.id,
            name: item.name,
            sku: item.sku || '-',
            uom: item.uom?.name || '-',
            system_qty: Number(item.current_stock ?? 0),
            actual_qty: Number(item.current_stock ?? 0),
        })
    }
}

const loadAllItems = async (isLoadMore = false) => {
    const outletId = activeOutletId.value
    if (!outletId) return

    if (!isLoadMore) {
        currentPage.value = 1
    }

    isLoadingItems.value = true
    try {
        const response = await axios.get(route('api.internal.inventory-items.partial'), {
            params: {
                outlet_id: outletId,
                page: currentPage.value,
                limit: 50,
            },
        })

        const newItems = response.data.data || []
        const meta = response.data.meta || {}

        newItems.forEach(i => {
            if (!form.items.find(existing => existing.inventory_item_id === i.id)) {
                form.items.push({
                    inventory_item_id: i.id,
                    name: i.name,
                    sku: i.sku || '-',
                    uom: i.uom?.name || '-',
                    system_qty: Number(i.current_stock ?? 0),
                    actual_qty: Number(i.current_stock ?? 0),
                })
            }
        })

        hasMoreItems.value = meta.current_page < meta.last_page
        if (hasMoreItems.value) {
            currentPage.value++
        }
    } catch (error) {
        console.error('Error loading items:', error)
    } finally {
        isLoadingItems.value = false
    }
}

const removeItem = index => {
    form.items.splice(index, 1)
}

const differenceColor = (actual, system) => {
    const diff = Number(actual || 0) - Number(system || 0)
    if (diff > 0) return 'text-emerald-600'
    if (diff < 0) return 'text-danger'
    return 'text-slate-400'
}

const formatDifference = (actual, system) => {
    const diff = Number(actual || 0) - Number(system || 0)
    const formatted = Math.abs(diff)
    if (diff > 0) return '+' + formatted
    if (diff < 0) return '-' + formatted
    return '0'
}

const close = () => {
    form.clearErrors()
    form.reset()
    popUpStore.close()
    emit('close')
}

const confirmSubmit = type => {
    if (type === 'save') {
        modalStore.open({
            title: 'Mulai Sesi Stok Opname',
            message:
                'Sesi stok opname akan dibuat dengan status Sedang Berjalan. Kamu dapat mencatat dan melengkapi penghitungan fisik.',
            confirmText: 'Mulai Opname',
            confirmButtonClass: 'btn btn-main',
            onConfirm: () => executeSubmit(type),
        })
    } else {
        modalStore.open({
            title: 'Ajukan Hasil Opname',
            message:
                'Hasil penghitungan fisik akan diajukan untuk ditinjau dan disetujui oleh supervisor/pemilik usaha. Lanjutkan?',
            confirmText: 'Ajukan Persetujuan',
            confirmButtonClass: 'btn btn-info',
            onConfirm: () => executeSubmit(type),
        })
    }
}

const executeSubmit = () => {
    const options = {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => close(),
    }

    if (props.opname) {
        form.put(route('inventory.opnames.update', props.opname.id), options)
    } else {
        form.post(route('inventory.opnames.store'), options)
    }
}
</script>
