<template>
    <form class="space-y-3" @submit.prevent="submit">
        <!-- Pilihan Mode Pembelian (Hanya saat Tambah Baru & memiliki fitur PO) -->
        <div v-if="!purchase?.id && hasPOFeature" class="p-2.5 bg-slate-50 border border-slate-200 rounded-lg space-y-1.5">
            <SelectionGroupField
                id="purchase_mode"
                v-model="purchaseMode"
                label="Jenis Dokumen Pembelian"
                :options="[
                    { label: 'Pesanan Pembelian (PO Draf)', value: 'po' },
                    { label: 'Beli Langsung (Terima Stok & Surat Jalan)', value: 'direct' },
                ]"
            />
            <p class="text-[11px] text-slate-500">
                <span v-if="purchaseMode === 'po'">
                    PO Draf: Dokumen pemesanan formal ke pemasok. Stok inventori baru akan bertambah saat barang tiba dan dicatat melalui alur penerimaan.
                </span>
                <span v-else>
                    Beli Langsung: Mencatat pembelian yang langsung disertai pengiriman fisik barang/surat jalan. Stok inventori otomatis bertambah saat disimpan.
                </span>
            </p>
        </div>

        <!-- Informasi Utama Pembelian -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <DropdownField
                id="supplier_id"
                v-model="form.supplier_id"
                label="Pemasok (Supplier)"
                placeholder="Pilih Pemasok..."
                :options="supplierOptions"
                :class="{ 'is-invalid': form.errors.supplier_id }"
                :error="form.errors.supplier_id"
            />

            <!-- Pilihan Outlet: HANYA jika tidak ada selectedOutlet aktif di sidebar -->
            <div v-if="!selectedOutlet">
                <DropdownField
                    id="outlet_id"
                    v-model="form.outlet_id"
                    label="Outlet Tujuan"
                    placeholder="Pilih Outlet..."
                    :options="outletOptions"
                    :class="{ 'is-invalid': form.errors.outlet_id }"
                    :error="form.errors.outlet_id"
                    required
                />
            </div>

            <TextField
                id="reference_number"
                v-model="form.reference_number"
                label="No. Referensi / Invoice Eksternal (Opsional)"
                placeholder="Misal: INV-SUP-2026/09/01"
                :class="{ 'is-invalid': form.errors.reference_number }"
                :error="form.errors.reference_number"
            />

            <!-- Nomor Surat Jalan (Wajib diisi jika Beli Langsung) -->
            <TextField
                v-if="purchaseMode === 'direct'"
                id="delivery_order_number"
                v-model="form.delivery_order_number"
                label="No. Surat Jalan Pemasok"
                placeholder="Misal: SJ-2026-00891"
                :class="{ 'is-invalid': form.errors.delivery_order_number }"
                :error="form.errors.delivery_order_number"
                required
            />

            <TextField
                id="order_date"
                v-model="form.order_date"
                type="date"
                label="Tanggal Pesan"
                :class="{ 'is-invalid': form.errors.order_date }"
                :error="form.errors.order_date"
                required
            />

            <TextField
                v-if="purchaseMode === 'po'"
                id="expected_date"
                v-model="form.expected_date"
                type="date"
                label="Tanggal Estimasi Tiba (Opsional)"
                :class="{ 'is-invalid': form.errors.expected_date }"
                :error="form.errors.expected_date"
            />
        </div>

        <TextareaField
            id="notes"
            v-model="form.notes"
            label="Catatan Pembelian"
            placeholder="Tambahkan instruksi khusus atau catatan pengiriman..."
            :class="{ 'is-invalid': form.errors.notes }"
            :error="form.errors.notes"
            rows="2"
        />

        <!-- Section Daftar Barang yang Dipesan -->
        <div class="border-t border-slate-200 pt-3 space-y-2">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Daftar Barang</h3>
                    <p class="text-xs text-slate-500">
                        Pilih barang, tentukan kemasan satuan beli, serta sesuaikan harga dan diskon/pajak.
                    </p>
                </div>
                <div class="w-full sm:w-80">
                    <AsyncSelectField
                        id="search_item"
                        placeholder="Cari nama, SKU, atau barcode..."
                        class="sm"
                        :api-url="route('inventory.purchases.search-items')"
                        :api-params="{
                            outlet_id: form.outlet_id,
                            supplier_id: form.supplier_id || undefined,
                        }"
                        search-param-name="query"
                        :min-chars="2"
                        :disabled="!form.outlet_id"
                        @select="addItemFromSearch"
                    >
                        <template #option="{ item }">
                            <div class="flex items-center justify-between w-full">
                                <div>
                                    <div class="font-semibold text-xs text-slate-800">
                                        {{ item.name }}
                                        <span class="text-slate-500 font-normal">
                                            ({{ item.uom?.name || '-' }})
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-slate-400">
                                        SKU: {{ item.sku || '-' }}
                                    </div>
                                </div>
                                <div class="text-right flex flex-col items-end">
                                    <span
                                        v-if="item.is_supplied"
                                        class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-green-100 text-green-800 gap-1"
                                    >
                                        <FontAwesomeIcon :icon="faCheck" /> Supplier
                                    </span>
                                    <span class="text-[11px] text-slate-500 mt-0.5">
                                        Stok: {{ Number(item.current_stock || 0) }}
                                    </span>
                                </div>
                            </div>
                        </template>
                    </AsyncSelectField>
                </div>
            </div>

            <!-- Petunjuk Jika Outlet Belum Terpilih -->
            <div
                v-if="!form.outlet_id"
                class="text-center py-6 text-xs text-slate-500 border border-dashed border-slate-300 rounded-lg bg-slate-50/50"
            >
                Pilih outlet tujuan terlebih dahulu untuk mencari barang.
            </div>

            <!-- Empty State Jika Belum Ada Barang -->
            <div
                v-else-if="form.items.length === 0"
                class="text-center py-6 text-xs text-slate-500 border border-dashed border-slate-300 rounded-lg bg-slate-50/50"
            >
                Belum ada barang ditambahkan. Cari dan pilih barang pada kolom pencarian di atas.
            </div>

            <!-- Daftar Item -->
            <div v-else class="space-y-2 max-h-96 overflow-y-auto pr-1">
                <div
                    v-for="(item, index) in form.items"
                    :key="item.inventory_item_id || index"
                    class="p-2.5 border border-slate-200 rounded-lg bg-white space-y-2"
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
                                    SKU: {{ item.sku || '-' }} | Satuan Dasar:
                                    <span class="font-medium text-slate-600">{{ item.base_uom_name || '-' }}</span>
                                </div>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="btn btn-flat btn-sm text-danger h-7 w-7 !p-0 inline-flex items-center justify-center cursor-pointer"
                            title="Hapus barang"
                            @click="removeItem(index)"
                        >
                            <FontAwesomeIcon :icon="faTrash" />
                        </button>
                    </div>

                    <!-- Input Grid: Satuan Beli, Kuantitas, Harga Beli, Diskon, Pajak -->
                    <div class="grid grid-cols-12 gap-2 items-end pt-1.5 border-t border-slate-100">
                        <!-- Satuan Pembelian (UOM) -->
                        <div class="col-span-12 sm:col-span-3">
                            <DropdownField
                                :id="'uom_' + index"
                                v-model="item.uom_id"
                                label="Satuan Beli"
                                class="sm"
                                :options="uomOptions"
                                :class="{
                                    'is-invalid': form.errors[`items.${index}.uom_id`],
                                }"
                                :error="form.errors[`items.${index}.uom_id`]"
                                required
                            />
                        </div>

                        <!-- Kuantitas Pesan -->
                        <div class="col-span-6 sm:col-span-2">
                            <NumberField
                                :id="'qty_' + index"
                                v-model="item.qty_ordered"
                                label="Kuantitas"
                                class="sm"
                                min="0.0001"
                                step="any"
                                :class="{
                                    'is-invalid': form.errors[`items.${index}.qty_ordered`],
                                }"
                                :error="form.errors[`items.${index}.qty_ordered`]"
                                required
                            />
                        </div>

                        <!-- Harga Satuan Beli -->
                        <div class="col-span-6 sm:col-span-3">
                            <NumberField
                                :id="'price_' + index"
                                v-model="item.purchase_price"
                                label="Harga Satuan (Rp)"
                                class="sm"
                                min="0"
                                step="any"
                                :class="{
                                    'is-invalid': form.errors[`items.${index}.purchase_price`],
                                }"
                                :error="form.errors[`items.${index}.purchase_price`]"
                                required
                            />
                        </div>

                        <!-- Potongan Diskon (Rp) -->
                        <div class="col-span-6 sm:col-span-2">
                            <NumberField
                                :id="'disc_' + index"
                                v-model="item.discount_amount"
                                label="Diskon (Rp)"
                                class="sm"
                                min="0"
                                step="any"
                                :class="{
                                    'is-invalid': form.errors[`items.${index}.discount_amount`],
                                }"
                                :error="form.errors[`items.${index}.discount_amount`]"
                            />
                        </div>

                        <!-- Pajak (Rp) -->
                        <div class="col-span-6 sm:col-span-2">
                            <NumberField
                                :id="'tax_' + index"
                                v-model="item.tax_amount"
                                label="Pajak (Rp)"
                                class="sm"
                                min="0"
                                step="any"
                                :class="{
                                    'is-invalid': form.errors[`items.${index}.tax_amount`],
                                }"
                                :error="form.errors[`items.${index}.tax_amount`]"
                            />
                        </div>
                    </div>

                    <!-- Konversi Satuan untuk Mode Beli Langsung & Subtotal Row -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pt-1 border-t border-dashed border-slate-100 bg-slate-50/50 p-2 rounded">
                        <div v-if="purchaseMode === 'direct'" class="flex items-center gap-2">
                            <div class="w-32">
                                <NumberField
                                    :id="'conv_' + index"
                                    v-model="item.conversion_factor"
                                    label="Faktor Konversi"
                                    class="sm"
                                    min="0.0001"
                                    step="any"
                                    :class="{
                                        'is-invalid': form.errors[`items.${index}.conversion_factor`],
                                    }"
                                    :error="form.errors[`items.${index}.conversion_factor`]"
                                    required
                                />
                            </div>
                            <div class="text-[11px] text-slate-500 self-end pb-1">
                                Masuk Stok: <span class="font-bold text-emerald-600">{{ formatQuantity(Number(item.qty_ordered || 0) * Number(item.conversion_factor || 1)) }} {{ item.base_uom_name }}</span>
                            </div>
                        </div>
                        <div v-else class="text-[11px] text-slate-400">
                            Subtotal = (Qty × Harga) - Diskon + Pajak
                        </div>

                        <div class="text-right">
                            <span class="text-[11px] text-slate-400 mr-2">Subtotal Baris:</span>
                            <span class="font-bold text-xs text-slate-800">
                                {{ formatCurrency(calculateRowSubtotal(item)) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Error Global Items Jika Ada -->
                <div v-if="form.errors.items" class="text-danger text-xs mt-1">
                    {{ form.errors.items }}
                </div>
            </div>

            <!-- Ringkasan Total Pembelian -->
            <div
                v-if="form.items.length > 0"
                class="flex items-center justify-between p-3 bg-slate-50 border border-slate-200 rounded-lg mt-2"
            >
                <div>
                    <div class="text-xs text-slate-500">Total Barang:</div>
                    <div class="font-bold text-xs text-slate-800">
                        {{ form.items.length }} Item ({{ totalQty }} Unit)
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-xs text-slate-500">Total Akhir Pembelian:</div>
                    <div class="font-bold text-base text-main">
                        {{ formatCurrency(totalAmount) }}
                    </div>
                </div>
            </div>
        </div>
    </form>

    <Teleport v-if="isMounted" to="#popUpFooter">
        <button type="button" class="btn btn-flat" :disabled="form.processing" @click="close">
            Batal
        </button>
        <button
            type="button"
            class="btn btn-main"
            :disabled="form.processing || form.items.length === 0 || !form.outlet_id"
            @click="submit"
        >
            {{ submitButtonText }}
        </button>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCheck, faTrash } from '@fortawesome/free-solid-svg-icons'
import { useAuth } from '@/Composable/useAuth'
import { useEnum } from '@/Composable/useEnum'
import { usePlanFeature } from '@/Composable/usePlanFeature'
import { usePopUpStore } from '@/store/popup'
import TextField from '@/Components/Form/TextField.vue'
import NumberField from '@/Components/Form/NumberField.vue'
import DropdownField from '@/Components/Form/DropdownField.vue'
import TextareaField from '@/Components/Form/TextareaField.vue'
import AsyncSelectField from '@/Components/Form/AsyncSelectField.vue'
import SelectionGroupField from '@/Components/Form/SelectionGroupField.vue'

const popUpStore = usePopUpStore()
const { outlets: userOutlets, selectedOutlet } = useAuth()
const { enums } = useEnum()
const { hasFeature } = usePlanFeature()

const hasPOFeature = computed(() => {
    return hasFeature(enums.FeatureEnum?.PURCHASE_ORDERS || 'purchase_orders')
})

const props = defineProps({
    purchase: {
        type: Object,
        default: null,
    },
    suppliers: {
        type: Array,
        default: () => [],
    },
    uoms: {
        type: Array,
        default: () => [],
    },
    initialMode: {
        type: String,
        default: 'po',
    },
})

const isMounted = ref(false)
const purchaseMode = ref(
    !hasPOFeature.value
        ? 'direct'
        : props.initialMode || 'po'
)

onMounted(() => {
    isMounted.value = true
})

const supplierOptions = computed(() =>
    props.suppliers.map(s => ({
        label: s.name,
        value: s.id,
    }))
)

const outletOptions = computed(() =>
    (userOutlets.value || []).map(store => ({
        label: store.name,
        value: store.id,
    }))
)

const uomOptions = computed(() =>
    props.uoms.map(u => ({
        label: `${u.name} (${u.code})`,
        value: u.id,
    }))
)

const form = useForm({
    supplier_id: '',
    outlet_id: selectedOutlet.value?.id || '',
    reference_number: '',
    delivery_order_number: '',
    order_date: new Date().toISOString().split('T')[0],
    expected_date: '',
    notes: '',
    items: [],
})

// Watch outlet changes: reset items if outlet changes
watch(
    () => form.outlet_id,
    (newVal, oldVal) => {
        if (oldVal && newVal !== oldVal && !props.purchase) {
            form.items = []
        }
    }
)

const calculateRowSubtotal = item => {
    const qty = Number(item.qty_ordered || 0)
    const price = Number(item.purchase_price || 0)
    const discount = Number(item.discount_amount || 0)
    const tax = Number(item.tax_amount || 0)
    const subtotal = qty * price - discount + tax
    return Math.max(0, subtotal)
}

const totalAmount = computed(() => {
    return form.items.reduce((sum, item) => sum + calculateRowSubtotal(item), 0)
})

const totalQty = computed(() => {
    return form.items.reduce((sum, item) => sum + Number(item.qty_ordered || 0), 0)
})

const formatCurrency = value => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value || 0)
}

const formatQuantity = value => {
    return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(Number(value || 0))
}

const addItemFromSearch = item => {
    const exists = form.items.find(i => i.inventory_item_id === item.id)
    if (exists) {
        exists.qty_ordered = Number(exists.qty_ordered || 0) + 1
    } else {
        form.items.unshift({
            inventory_item_id: item.id,
            name: item.name,
            sku: item.sku || '-',
            base_uom_id: item.uom_id || '',
            base_uom_name: item.uom?.name || '-',
            uom_id: item.uom_id || (props.uoms[0]?.id ?? ''),
            qty_ordered: 1,
            purchase_price: 0,
            discount_amount: 0,
            tax_amount: 0,
            conversion_factor: 1,
        })
    }
}

const removeItem = index => {
    form.items.splice(index, 1)
}

// Inisialisasi Data jika Mode Edit
watch(
    () => props.purchase,
    data => {
        form.reset()

        if (data) {
            form.supplier_id = data.supplier_id || ''
            form.outlet_id = data.outlet_id || selectedOutlet.value?.id || ''
            form.reference_number = data.reference_number || ''
            form.order_date = data.order_date || new Date().toISOString().split('T')[0]
            form.expected_date = data.expected_date || ''
            form.notes = data.notes || ''

            if (data.items && data.items.length > 0) {
                form.items = data.items.map(i => ({
                    inventory_item_id: i.inventory_item_id,
                    name: i.inventory_item?.name || 'Item',
                    sku: i.inventory_item?.sku || '-',
                    base_uom_id: i.inventory_item?.uom_id || '',
                    base_uom_name: i.inventory_item?.uom?.name || '-',
                    uom_id: i.uom_id || i.inventory_item?.uom_id || '',
                    qty_ordered: i.qty_ordered,
                    purchase_price: i.purchase_price,
                    discount_amount: i.discount_amount || 0,
                    tax_amount: i.tax_amount || 0,
                    conversion_factor: i.conversion_factor || 1,
                }))
            } else {
                form.items = []
            }
        } else {
            // Mode Tambah Baru
            form.outlet_id = selectedOutlet.value?.id || (userOutlets.value?.length === 1 ? userOutlets.value[0].id : '')
        }
    },
    { immediate: true }
)

const submitButtonText = computed(() => {
    if (props.purchase?.id) {
        return 'Simpan Perubahan'
    }
    return purchaseMode.value === 'direct' ? 'Simpan & Terima Stok' : 'Simpan PO Draf'
})

const close = () => {
    form.clearErrors()
    popUpStore.close()
}

const submit = () => {
    if (props.purchase?.id) {
        form.put(route('inventory.purchases.update', props.purchase.id), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => close(),
        })
    } else if (purchaseMode.value === 'direct') {
        form.post(route('inventory.purchases.direct'), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => close(),
        })
    } else {
        form.post(route('inventory.purchases.store'), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => close(),
        })
    }
}
</script>
