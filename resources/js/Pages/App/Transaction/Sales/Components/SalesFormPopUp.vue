<template>
    <div v-if="isLoadingDraft" class="flex flex-col items-center justify-center py-24 space-y-3">
        <div
            class="h-8 w-8 animate-spin rounded-full border-3 border-main border-t-transparent"
        ></div>
        <div class="text-xs text-slate-500 font-medium">Memuat data draf transaksi...</div>
    </div>
    <form
        v-else
        id="sales_transaction_form"
        class="space-y-5 pb-20 text-left"
        @submit.prevent="submitDraft"
    >
        <!-- Tier 1: Pelanggan & Info Faktur -->
        <div class="border border-slate-200 rounded-xl bg-white p-4 space-y-3.5 shadow-2xs">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                    1. Informasi Dokumen & Pelanggan
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                <AsyncOutletDropdown
                    v-model="form.outlet_id"
                    label="Outlet / Cabang"
                    placeholder="Pilih Outlet"
                    :error="form.errors.outlet_id"
                    size="sm"
                    required
                />

                <AsyncSelectField
                    id="sales_customer_id"
                    v-model="form.customer_id"
                    label="Pelanggan (B2B / Reseller)"
                    placeholder="Cari Pelanggan..."
                    api-url="/api/internal/customers/search"
                    :error="form.errors.customer_id"
                    size="sm"
                    @select="onCustomerSelected"
                />

                <DropdownField
                    v-model="form.channel"
                    label="Saluran Penjualan"
                    :options="channelOptions"
                    :error="form.errors.channel"
                    size="sm"
                    required
                />

                <TextField
                    v-model="form.transaction_date"
                    label="Tanggal Transaksi"
                    type="date"
                    :error="form.errors.transaction_date"
                    size="sm"
                    required
                    @update:model-value="calculateDueDate"
                />

                <DropdownField
                    v-model="form.payment_term"
                    label="Termin Pembayaran"
                    :options="paymentTermOptions"
                    :error="form.errors.payment_term"
                    size="sm"
                    required
                />

                <template v-if="form.payment_term === 'credit'">
                    <div>
                        <TextField
                            v-model="form.due_date"
                            label="Tanggal Jatuh Tempo"
                            type="date"
                            :error="form.errors.due_date"
                            size="sm"
                            :disabled="true"
                            required
                        />
                        <span class="text-[11px] text-slate-500 mt-1 block">
                            Terkunci otomatis sesuai pengaturan outlet (Tenor
                            {{ outletDueDays }} hari).
                        </span>
                    </div>
                </template>
            </div>
        </div>

        <!-- Tier 2: Daftar Item Produk -->
        <div class="border border-slate-200 rounded-xl bg-white p-4 space-y-3.5 shadow-2xs">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                        2. Rincian Item Penjualan
                    </span>
                    <span
                        class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 text-[10px] font-semibold"
                    >
                        {{ form.items.length }} Baris
                    </span>
                </div>

                <button
                    type="button"
                    class="btn btn-main btn-sm h-[30px] inline-flex items-center gap-1.5 cursor-pointer"
                    :disabled="!form.outlet_id"
                    @click="openItemPicker"
                >
                    <FontAwesomeIcon :icon="faPlus" />
                    <span>Item</span>
                </button>
            </div>

            <div
                v-if="!form.outlet_id"
                class="text-xs text-amber-600 bg-amber-50 p-2.5 rounded-lg border border-amber-200"
            >
                Pilih outlet penjualan terlebih dahulu untuk mencari dan memilih produk.
            </div>

            <!-- Items Table -->
            <div class="overflow-x-auto border border-slate-200 rounded-lg">
                <table class="table-compact w-full text-xs">
                    <thead
                        class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200"
                    >
                        <tr>
                            <th class="w-8 text-center py-2 px-2">No</th>
                            <th class="py-2 px-3">Deskripsi Produk</th>
                            <th class="w-32 py-2 px-2">Harga Satuan (Rp)</th>
                            <th class="w-20 py-2 px-2 text-center">Qty</th>
                            <th class="w-36 py-2 px-2 text-center">Diskon Baris</th>
                            <th class="w-32 py-2 px-3 text-right">Subtotal</th>
                            <th class="w-10 text-center py-2 px-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="form.items.length === 0">
                            <td colspan="7" class="text-center text-slate-400 py-8">
                                <FontAwesomeIcon
                                    :icon="faBoxesStacked"
                                    class="text-2xl text-slate-300 mb-1"
                                />
                                <div>
                                    Belum ada item ditambahkan. Klik tombol di atas untuk memilih
                                    barang.
                                </div>
                            </td>
                        </tr>

                        <tr
                            v-for="(item, index) in form.items"
                            :key="index"
                            class="hover:bg-slate-50/70"
                        >
                            <td class="text-center text-slate-400 font-medium px-2">
                                {{ index + 1 }}
                            </td>
                            <td class="px-3 py-2">
                                <div class="font-semibold text-slate-800">
                                    {{ item.product_name }}
                                </div>
                                <div class="text-[10px] text-slate-500 flex items-center gap-1.5">
                                    <span>SKU: {{ item.sku || '-' }}</span>
                                    <span>•</span>
                                    <span>Satuan: {{ item.uom_name || 'Pcs' }}</span>
                                    <template v-if="item.product_type === 'service'">
                                        <span>•</span>
                                        <span class="text-amber-600 font-medium"
                                            >Layanan / Jasa</span
                                        >
                                    </template>
                                    <template v-else-if="item.product_type === 'bundle'">
                                        <span>•</span>
                                        <span class="text-indigo-600 font-medium"
                                            >Paket / Bundle</span
                                        >
                                    </template>
                                </div>
                                <!-- Auto promo badge if applied -->
                                <div v-if="item.auto_promo_name" class="mt-0.5">
                                    <span
                                        class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded text-[9px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200"
                                    >
                                        <FontAwesomeIcon :icon="faTag" class="text-[8px]" />
                                        {{ item.auto_promo_name }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                <span class="font-semibold text-slate-800 text-xs">
                                    {{ formatCurrency(item.price) }}
                                </span>
                            </td>
                            <td class="px-2 py-1.5">
                                <NumberField
                                    v-model="item.qty"
                                    size="sm"
                                    class="w-full"
                                    @update:model-value="onItemFinancialChange"
                                />
                            </td>
                            <td class="px-2 py-1.5 text-center">
                                <button
                                    type="button"
                                    class="w-full py-1 px-2 rounded-lg text-xs font-medium transition cursor-pointer flex items-center justify-between border"
                                    :class="
                                        item.auto_promo_name
                                            ? 'bg-emerald-50 text-emerald-700 border-emerald-300 font-semibold ring-1 ring-emerald-200'
                                            : Number(item.discount_amount) > 0
                                              ? 'bg-emerald-50 text-emerald-700 border-emerald-200 font-semibold'
                                              : 'bg-slate-50 text-slate-500 hover:bg-slate-100 border-slate-200'
                                    "
                                    :title="
                                        item.auto_promo_name
                                            ? 'Promo otomatis aktif (Terkunci)'
                                            : 'Klik untuk mengatur diskon baris item'
                                    "
                                    @click="openItemDiscountModal(item, index)"
                                >
                                    <span v-if="Number(item.discount_amount) > 0" class="truncate">
                                        - {{ formatCurrency(item.discount_amount) }}
                                    </span>
                                    <span v-else class="text-slate-400"> + Diskon </span>
                                    <FontAwesomeIcon
                                        :icon="item.auto_promo_name ? faLock : faPencil"
                                        class="text-[10px] opacity-70 ml-1"
                                    />
                                </button>
                            </td>
                            <td class="px-3 py-2 text-right font-bold text-slate-800">
                                {{
                                    formatCurrency(
                                        Math.max(
                                            0,
                                            Number(item.qty) * Number(item.price) -
                                                Number(item.discount_amount || 0)
                                        )
                                    )
                                }}
                            </td>
                            <td class="px-2 py-2 text-center">
                                <button
                                    type="button"
                                    class="text-slate-400 hover:text-rose-600 p-1 rounded-md transition cursor-pointer"
                                    title="Hapus baris"
                                    @click="removeItem(index)"
                                >
                                    <FontAwesomeIcon :icon="faTrash" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="form.errors.items" class="text-xs text-danger font-medium">
                {{ form.errors.items }}
            </div>
        </div>

        <!-- Tier 3: Finansial & Opsi Penerbitan -->
        <div class="border border-slate-200 rounded-xl bg-white p-4 space-y-4 shadow-2xs">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                    3. Rincian Finansial & Pembayaran
                </span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
                <!-- Catatan Dokumen PO (Left Column) -->
                <div class="lg:col-span-5 space-y-2">
                    <TextareaField
                        v-model="form.notes"
                        label="Catatan Dokumen / PO"
                        placeholder="Syarat & Ketentuan pembayaran, instruksi pengiriman khusus, dsb."
                        rows="6"
                        :error="form.errors.notes"
                        size="sm"
                    />
                    <span class="text-[11px] text-slate-400 block leading-tight">
                        Otomatis memuat format standar syarat & ketentuan dari pengaturan outlet.
                    </span>
                </div>

                <!-- Financial Calculation Breakdown (Right Column) -->
                <div
                    class="lg:col-span-7 bg-slate-50/80 border border-slate-200 rounded-xl p-4 space-y-3 text-xs"
                >
                    <!-- Subtotal Item -->
                    <div class="flex items-center justify-between">
                        <span class="text-slate-600 font-medium">Subtotal Item:</span>
                        <span class="font-bold text-slate-800 text-sm">
                            {{ formatCurrency(financials.subtotal) }}
                        </span>
                    </div>

                    <!-- Diskon / Promo Transaksi -->
                    <div
                        class="flex items-center justify-between pt-2.5 border-t border-slate-200/60"
                    >
                        <div class="space-y-0.5">
                            <div class="font-medium text-slate-700">Diskon Transaksi:</div>
                            <div
                                v-if="transactionDiscount.amount > 0"
                                class="text-[11px] text-emerald-600 font-semibold flex items-center gap-1"
                            >
                                <FontAwesomeIcon :icon="faTag" class="text-[10px]" />
                                <span>{{ transactionDiscount.promo_name || 'Diskon Manual' }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <div v-if="transactionDiscount.amount > 0" class="text-right">
                                <span class="font-bold text-emerald-600 text-xs">
                                    - {{ formatCurrency(transactionDiscount.amount) }}
                                </span>
                                <div
                                    class="flex items-center gap-1.5 justify-end text-[10px] mt-0.5"
                                >
                                    <button
                                        type="button"
                                        class="text-slate-500 hover:text-slate-800 underline cursor-pointer"
                                        @click="showTransactionDiscountModal = true"
                                    >
                                        Ubah
                                    </button>
                                    <span>•</span>
                                    <button
                                        type="button"
                                        class="text-rose-600 hover:text-rose-800 underline cursor-pointer"
                                        @click="resetTransactionDiscount"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </div>
                            <button
                                v-else
                                type="button"
                                class="btn btn-outline-secondary btn-sm h-[28px] text-[11px] px-2.5 inline-flex items-center gap-1 cursor-pointer"
                                @click="showTransactionDiscountModal = true"
                            >
                                <FontAwesomeIcon :icon="faTags" class="text-[10px] text-main" />
                                <span>+ Diskon / Promo</span>
                            </button>
                        </div>
                    </div>

                    <!-- Biaya Pengiriman -->
                    <div
                        class="flex items-center justify-between pt-2.5 border-t border-slate-200/60"
                    >
                        <span class="text-slate-600 font-medium">Biaya Pengiriman:</span>
                        <div class="w-40">
                            <NumberField
                                v-model="form.shipping_fee"
                                size="sm"
                                prefix="Rp"
                                class="text-right"
                                @update:model-value="calculateFinancials"
                            />
                        </div>
                    </div>

                    <!-- Pajak (PPN) -->
                    <div
                        class="flex items-start justify-between pt-2.5 border-t border-slate-200/60"
                    >
                        <div class="space-y-0.5">
                            <div class="text-slate-600 font-medium">Pajak (PPN):</div>
                            <div class="text-[10px] text-slate-400">% Dari DPP Netto</div>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <div>
                                <NumberField
                                    v-model="taxRate"
                                    size="sm"
                                    suffix="%"
                                    class="w-12"
                                    :min="0"
                                    :max="100"
                                    @update:model-value="calculateFinancials"
                                />
                            </div>
                            <div class="text-right font-bold text-slate-800">
                                {{ formatCurrency(form.tax_amount) }}
                            </div>
                        </div>
                    </div>

                    <!-- Grand Total Highlight Card -->
                    <div
                        class="p-3 bg-main/5 border border-main/20 rounded-lg flex items-center justify-between"
                    >
                        <span class="font-bold text-slate-800 text-sm">Grand Total:</span>
                        <span class="text-lg font-bold text-main">
                            {{ formatCurrency(financials.grandTotal) }}
                        </span>
                    </div>

                    <!-- Pembayaran Awal / Sisa Tagihan -->
                    <div class="pt-3 border-t border-slate-200 space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="font-semibold text-slate-700 block text-xs">
                                    {{
                                        form.payment_term === 'cash'
                                            ? 'Pembayaran Tunai (Lunas)'
                                            : 'Pembayaran Awal / Uang Muka (DP)'
                                    }}:
                                </span>
                                <span
                                    v-if="form.payment_term === 'cash'"
                                    class="text-[10px] text-emerald-600 font-medium"
                                >
                                    Otomatis lunas penuh saat faktur diterbitkan.
                                </span>
                                <span v-else class="text-[10px] text-slate-500 font-medium">
                                    Opsional, isi jika pelanggan membayar DP di awal.
                                </span>
                            </div>
                            <div class="w-40">
                                <NumberField
                                    v-model="form.payment.amount"
                                    size="sm"
                                    prefix="Rp"
                                    :disabled="form.payment_term === 'cash'"
                                    :error="form.errors['payment.amount']"
                                    @update:model-value="calculateFinancials"
                                />
                            </div>
                        </div>

                        <div
                            v-if="form.payment.amount > 0 || form.payment_term === 'cash'"
                            class="pt-1"
                        >
                            <DropdownField
                                v-model="form.payment.payment_method_id"
                                label="Metode Pembayaran (Kas / Bank Outlet)"
                                placeholder="Pilih Metode Pembayaran..."
                                :options="paymentMethodOptions"
                                :error="form.errors['payment.payment_method_id']"
                                size="sm"
                                :required="form.payment_term === 'cash' || form.payment.amount > 0"
                            />
                        </div>

                        <div
                            class="flex items-center justify-between pt-2 border-t border-slate-200/80"
                        >
                            <span class="font-semibold text-slate-700 text-xs"
                                >Sisa Tagihan (Piutang):</span
                            >
                            <span
                                class="text-sm font-bold"
                                :class="
                                    financials.balanceDue > 0 ? 'text-rose-600' : 'text-emerald-600'
                                "
                            >
                                {{
                                    financials.balanceDue > 0
                                        ? formatCurrency(financials.balanceDue)
                                        : 'Lunas (Rp 0)'
                                }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Teleport Action Footer -->
        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm h-[30px]"
                        :disabled="form.processing"
                        @click="handleCancel()"
                    >
                        Batal
                    </button>

                    <button
                        v-if="isEditMode"
                        type="button"
                        class="btn btn-outline-danger btn-sm h-[30px] inline-flex items-center gap-1.5 cursor-pointer text-xs"
                        :disabled="form.processing"
                        @click="handleDeleteDraft"
                    >
                        <FontAwesomeIcon :icon="faTrash" />
                        <span>Hapus Draf</span>
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm h-[30px] inline-flex items-center gap-1.5 cursor-pointer text-xs"
                        :disabled="form.processing || form.items.length === 0"
                        @click="submitDraft"
                    >
                        <FontAwesomeIcon :icon="faSave" />
                        <span>Simpan Draf</span>
                    </button>

                    <button
                        type="button"
                        class="btn btn-main btn-sm h-[30px] inline-flex items-center gap-1.5 cursor-pointer text-xs"
                        :disabled="form.processing || form.items.length === 0"
                        @click="handleIssueInvoiceClick"
                    >
                        <FontAwesomeIcon :icon="faFileInvoice" />
                        <span>Terbitkan Faktur</span>
                    </button>
                </div>
            </div>
        </Teleport>

        <!-- Product Picker Modal -->
        <ProductPickerModal
            :show="showItemPicker"
            :outlet-id="form.outlet_id"
            :already-selected-ids="selectedItemIds"
            @close="showItemPicker = false"
            @selected="onItemsSelected"
        />

        <!-- Item Discount Modal -->
        <ItemDiscountModal
            :show="showItemDiscountModal"
            :item="activeItemForDiscount"
            @close="showItemDiscountModal = false"
            @apply="onItemDiscountApplied"
        />

        <!-- Unified Transaction Discount & Promotion Modal -->
        <TransactionDiscountModal
            :show="showTransactionDiscountModal"
            :outlet-id="form.outlet_id"
            :channel="form.channel"
            :subtotal="financials.subtotal"
            :total-qty="totalQuantity"
            :current-discount="transactionDiscount"
            @close="showTransactionDiscountModal = false"
            @apply="onTransactionDiscountApplied"
        />
    </form>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch, markRaw } from 'vue'
import { useForm, usePage, router } from '@inertiajs/vue3'
import axios from 'axios'
import { debounce } from 'lodash'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faPlus,
    faTrash,
    faSave,
    faBoxesStacked,
    faPencil,
    faTag,
    faTags,
    faLock,
    faFileInvoice,
} from '@fortawesome/free-solid-svg-icons'
import AsyncOutletDropdown from '@/Components/Form/AsyncOutletDropdown.vue'
import AsyncSelectField from '@/Components/Form/AsyncSelectField.vue'
import DropdownField from '@/Components/Form/DropdownField.vue'
import TextField from '@/Components/Form/TextField.vue'
import NumberField from '@/Components/Form/NumberField.vue'
import TextareaField from '@/Components/Form/TextareaField.vue'
import ProductPickerModal from './ProductPickerModal.vue'
import ItemDiscountModal from './ItemDiscountModal.vue'
import TransactionDiscountModal from './TransactionDiscountModal.vue'
import SalesIssueReviewContent from './SalesIssueReviewContent.vue'
import { formatIDR as formatCurrency } from '@/Composable/currency-format.js'
import { useFormDirtyGuard } from '@/Composable/useFormDirtyGuard.js'
import { useToastStore, useModalStore } from '@/store/notification'
import { addDays, formatISODate } from '@/Composable/date.js'

const props = defineProps({
    transactionId: {
        type: String,
        default: null,
    },
})

const emit = defineEmits(['close', 'success'])

const page = usePage()
const toastStore = useToastStore()
const modalStore = useModalStore()
const isMounted = ref(false)
const isLoadingDraft = ref(false)
const showItemPicker = ref(false)
const showItemDiscountModal = ref(false)
const showTransactionDiscountModal = ref(false)
const selectedCustomerName = ref('')
const activeItemIndex = ref(null)
const isManualOverride = ref(false)
const isEditMode = computed(() => !!props.transactionId)

const outletDueDays = ref(14)
const taxRate = ref(0)
const paymentMethodOptions = ref([])

const defaultChannelLabels = {
    wholesale: 'Grosir (Wholesale)',
    direct: 'Penjualan Langsung (Direct)',
    e_commerce: 'E-Commerce / Marketplace',
    social_media: 'Media Sosial & WhatsApp',
    custom: 'Pesanan Khusus',
}

const channelOptions = ref([
    { label: 'Grosir (Wholesale)', value: 'wholesale' },
    { label: 'Penjualan Langsung (Direct)', value: 'direct' },
    { label: 'E-Commerce / Marketplace', value: 'e_commerce' },
    { label: 'Media Sosial & WhatsApp', value: 'social_media' },
    { label: 'Pesanan Khusus', value: 'custom' },
])

const paymentTermOptions = [
    { label: 'Tunai (Langsung Lunas / COD)', value: 'cash' },
    { label: 'Termin / Kredit (Tempo)', value: 'credit' },
]

const form = useForm({
    outlet_id: null,
    customer_id: null,
    channel: 'wholesale',
    transaction_date: formatISODate(new Date()),
    payment_term: 'cash',
    payment_term_code: 'custom',
    due_date: null,
    notes: '',
    discount_type: 'manual',
    discount_value: 0,
    promo_code: null,
    tax_amount: 0,
    shipping_fee: 0,
    service_charge_amount: 0,
    items: [],
    issue_now: false,
    payment: {
        amount: 0,
        payment_method_id: null,
    },
})

const { handleCancel, forceClose } = useFormDirtyGuard({ form })

const transactionDiscount = reactive({
    type: null, // 'promo' | 'manual' | null
    value: 0,
    amount: 0,
    promo_id: null,
    promo_name: null,
    promo_code: null,
    manual_rate: null,
})

const financials = reactive({
    subtotal: 0,
    grandTotal: 0,
    balanceDue: 0,
})

const totalQuantity = computed(() => {
    return form.items.reduce((sum, item) => sum + Number(item.qty || 0), 0)
})

const selectedItemIds = computed(() => {
    return form.items.map(i => i.selected_id || i.product_item_id || i.product_id).filter(Boolean)
})

const activeItemForDiscount = computed(() => {
    if (activeItemIndex.value === null || !form.items[activeItemIndex.value]) return null
    return form.items[activeItemIndex.value]
})

const onCustomerSelected = customer => {
    if (customer) {
        selectedCustomerName.value = customer.name || customer.label || ''
    } else {
        selectedCustomerName.value = ''
    }
}

const openItemPicker = () => {
    if (!form.outlet_id) return
    showItemPicker.value = true
}

const onItemsSelected = selectedItems => {
    selectedItems.forEach(item => {
        form.items.push({
            selected_id: item.id,
            product_id: item.product_id || item.id,
            product_item_id: item.product_item_id || null,
            inventory_item_id: item.inventory_item_id || null,
            product_name: item.name || item.product_name,
            product_type: item.product_type || 'basic',
            sku: item.sku || '',
            uom_name: item.uom || 'Pcs',
            qty: 1,
            price: Number(item.price) || 0,
            discount_amount: 0,
            discount_type: 'fixed',
            discount_rate: 0,
            auto_promo_name: null,
            notes: '',
        })
    })
    showItemPicker.value = false
    evaluatePromotionsAndRecalculate()
}

const removeItem = index => {
    form.items.splice(index, 1)
    evaluatePromotionsAndRecalculate()
}

const openItemDiscountModal = (item, index) => {
    activeItemIndex.value = index
    showItemDiscountModal.value = true
}

const onItemDiscountApplied = discountData => {
    if (activeItemIndex.value !== null && form.items[activeItemIndex.value]) {
        const item = form.items[activeItemIndex.value]
        item.discount_amount = Number(discountData.discount_amount || 0)
        item.discount_type = discountData.discount_type || 'fixed'
        item.discount_rate = Number(discountData.discount_rate || 0)
        // Clear auto promo name if manually customized
        if (item.discount_amount === 0) {
            item.auto_promo_name = null
        }
        calculateFinancials()
    }
}

const onTransactionDiscountApplied = discountData => {
    transactionDiscount.type = discountData.type
    transactionDiscount.value = Number(discountData.value || 0)
    transactionDiscount.amount = Number(discountData.amount || 0)
    transactionDiscount.promo_id = discountData.promo_id || null
    transactionDiscount.promo_name = discountData.promo_name || null
    transactionDiscount.promo_code = discountData.promo_code || null
    transactionDiscount.manual_rate = discountData.manual_rate || null

    if (discountData.type === 'manual' || discountData.type === null) {
        isManualOverride.value = true
    } else if (discountData.type === 'promo') {
        isManualOverride.value = false
    }

    form.discount_type = discountData.type || 'manual'
    form.discount_value = transactionDiscount.amount
    form.promo_code = discountData.promo_code || null

    calculateFinancials()
}

const resetTransactionDiscount = () => {
    isManualOverride.value = true
    onTransactionDiscountApplied({
        type: null,
        value: 0,
        amount: 0,
        promo_id: null,
        promo_name: null,
        promo_code: null,
        manual_rate: null,
    })
}

const onItemFinancialChange = () => {
    evaluatePromotionsAndRecalculate()
}

const calculateFinancials = () => {
    let sub = 0
    form.items.forEach(item => {
        const lineTotal = Number(item.qty) * Number(item.price) - Number(item.discount_amount || 0)
        sub += Math.max(0, lineTotal)
    })

    financials.subtotal = sub

    // Recalculate transaction discount if manual rate (%) is used
    if (transactionDiscount.type === 'manual' && transactionDiscount.manual_rate) {
        transactionDiscount.amount = Math.round(
            (sub * Number(transactionDiscount.manual_rate)) / 100
        )
        transactionDiscount.value = transactionDiscount.amount
        form.discount_value = transactionDiscount.amount
    }

    const netDpp = Math.max(0, sub - Number(form.discount_value || 0))
    form.tax_amount = Math.round((netDpp * Number(taxRate.value || 0)) / 100)

    financials.grandTotal = Math.max(
        0,
        netDpp +
            Number(form.tax_amount || 0) +
            Number(form.shipping_fee || 0) +
            Number(form.service_charge_amount || 0)
    )

    if (form.payment_term === 'cash') {
        form.payment.amount = financials.grandTotal
        financials.balanceDue = 0
    } else {
        const paid = Number(form.payment.amount || 0)
        financials.balanceDue = Math.max(0, financials.grandTotal - paid)
    }
}

const evaluatePromotionsAndRecalculate = debounce(async () => {
    calculateFinancials()

    if (!form.outlet_id || form.items.length === 0) {
        return
    }

    try {
        const payload = {
            outlet_id: form.outlet_id,
            channel: form.channel,
            promo_code: transactionDiscount.promo_code,
            items: form.items.map((item, idx) => ({
                id: 'item_' + idx,
                product_id: item.product_id,
                product_item_id: item.product_item_id,
                inventory_item_id: item.inventory_item_id,
                price: Number(item.price),
                qty: Number(item.qty),
                discount_amount:
                    item.discount_type === 'percentage' || item.auto_promo_name
                        ? 0
                        : Number(item.discount_amount || 0),
            })),
        }

        const res = await axios.post(route('api.internal.promos.evaluate'), payload)
        const evalData = res.data?.data

        if (evalData) {
            // Filter only item-level promotions
            const itemLevelPromos = (evalData.applied_promotions || []).filter(
                p => p.target_scope !== 'transaction' && p.allocation_level === 'item'
            )

            // Apply auto item discounts if available
            if (itemLevelPromos.length > 0 && evalData.item_discounts && typeof evalData.item_discounts === 'object') {
                form.items.forEach((item, idx) => {
                    const key = 'item_' + idx
                    const autoDisc = Number(evalData.item_discounts[key] || 0)
                    if (autoDisc > 0 && (!item.discount_amount || item.auto_promo_name)) {
                        const matchedPromo = itemLevelPromos.find(
                            p => p.affected_item_ids?.includes(key) || !p.affected_item_ids || p.affected_item_ids.length === 0
                        ) || itemLevelPromos[0]
                        if (matchedPromo) {
                            item.discount_amount = autoDisc
                            item.auto_promo_name = matchedPromo.promotion_name
                        }
                    } else if (autoDisc === 0 && item.auto_promo_name) {
                        item.discount_amount = 0
                        item.auto_promo_name = null
                    }
                })
            } else {
                // Clear any lingering auto promo discounts on items
                form.items.forEach(item => {
                    if (item.auto_promo_name) {
                        item.discount_amount = 0
                        item.auto_promo_name = null
                    }
                })
            }

            // If auto transaction promo was detected and no manual discount / override was set
            if (
                !transactionDiscount.type &&
                !isManualOverride.value &&
                evalData.applied_promotions?.length > 0
            ) {
                const transPromo = evalData.applied_promotions.find(
                    p => p.target_scope === 'transaction'
                )
                if (transPromo) {
                    transactionDiscount.type = 'promo'
                    transactionDiscount.amount = transPromo.discount_amount
                    transactionDiscount.value = transPromo.discount_amount
                    transactionDiscount.promo_id = transPromo.promotion_id
                    transactionDiscount.promo_name = transPromo.promotion_name
                    transactionDiscount.promo_code = transPromo.promo_code

                    form.discount_type = 'promo'
                    form.discount_value = transPromo.discount_amount
                    form.promo_code = transPromo.promo_code
                }
            }
        }
    } catch (e) {
        console.error('Failed to evaluate promotions:', e)
    } finally {
        calculateFinancials()
    }
}, 300)

const calculateDueDate = () => {
    if (form.payment_term !== 'credit' || !form.transaction_date) {
        form.due_date = null
        return
    }

    const days = Number(outletDueDays.value || 14)
    form.due_date = formatISODate(addDays(new Date(form.transaction_date), days))
}

const fetchOutletSettings = async outletId => {
    if (!outletId) return
    try {
        const res = await axios.get(route('api.internal.outlets.sales-settings'), {
            params: { outlet_id: outletId },
        })
        const data = res.data?.data
        if (data) {
            outletDueDays.value = Number(
                data.default_due_days_invoice || data.default_due_days_b2b || 14
            )
            if (!form.notes && data.default_terms_and_conditions_invoice) {
                form.notes = data.default_terms_and_conditions_invoice
            }
            if (taxRate.value === 0 && data.default_tax_rate) {
                taxRate.value = Number(data.default_tax_rate)
            }

            if (
                data.sales_channels_b2b &&
                Array.isArray(data.sales_channels_b2b) &&
                data.sales_channels_b2b.length > 0
            ) {
                const enumChannels = page.props.enums?.SalesChannelEnum?._meta || {}
                channelOptions.value = data.sales_channels_b2b.map(ch => ({
                    value: ch,
                    label: enumChannels[ch]?.label || defaultChannelLabels[ch] || ch,
                }))

                if (!data.sales_channels_b2b.includes(form.channel)) {
                    form.channel = data.sales_channels_b2b[0]
                }
            }
        }
    } catch (e) {
        console.error('Failed to load outlet sales settings:', e)
        outletDueDays.value = 14
    } finally {
        calculateDueDate()
        calculateFinancials()
    }
}

const fetchPaymentMethods = async outletId => {
    if (!outletId) {
        paymentMethodOptions.value = []
        return
    }
    try {
        const res = await axios.get(route('api.internal.payment-methods.index'), {
            params: { outlet_id: outletId },
        })
        const items = res.data?.data || []
        paymentMethodOptions.value = items
            .filter(pm => pm.is_enabled !== false)
            .map(pm => ({
                value: pm.id,
                label: pm.name,
            }))

        if (paymentMethodOptions.value.length > 0 && !form.payment.payment_method_id) {
            form.payment.payment_method_id = paymentMethodOptions.value[0].value
        }
    } catch (e) {
        console.error('Failed to fetch outlet payment methods:', e)
        paymentMethodOptions.value = []
    }
}

watch(
    () => form.outlet_id,
    newOutletId => {
        if (newOutletId) {
            fetchOutletSettings(newOutletId)
            fetchPaymentMethods(newOutletId)
        } else {
            paymentMethodOptions.value = []
        }
    }
)

watch(
    () => form.payment_term,
    val => {
        if (val === 'cash') {
            form.due_date = null
            form.payment.amount = financials.grandTotal
        } else {
            calculateDueDate()
        }
        calculateFinancials()
    }
)

const fetchDraftDetail = async () => {
    if (!props.transactionId) return
    isLoadingDraft.value = true
    try {
        const response = await axios.get(route('transactions.sales.show', props.transactionId))
        const draft = response.data?.data
        if (!draft) return

        if (draft.status !== 'draft') {
            toastStore.error('Hanya transaksi berstatus draf yang dapat diedit.')
            forceClose()
            return
        }

        form.outlet_id = draft.outlet_id
        form.customer_id = draft.customer_id
        selectedCustomerName.value = draft.customer?.name || ''
        form.channel = draft.channel || 'wholesale'
        form.transaction_date = draft.transaction_date
            ? formatISODate(new Date(draft.transaction_date))
            : formatISODate(new Date())
        form.payment_term = draft.payment_term || 'cash'
        form.payment_term_code = draft.payment_term_code || 'custom'
        form.due_date = draft.due_date ? formatISODate(new Date(draft.due_date)) : null
        form.notes = draft.notes || ''
        form.discount_type = draft.discount_type || 'manual'
        form.discount_value = Number(draft.discount_amount || draft.discount_value || 0)
        form.promo_code = draft.promo_code || null
        form.tax_amount = Number(draft.tax_amount || 0)
        form.shipping_fee = Number(draft.shipping_fee || 0)
        form.service_charge_amount = Number(draft.service_charge_amount || 0)

        // Populate items
        form.items = (draft.items || []).map(item => ({
            selected_id: item.product_item_id || item.product_id,
            product_id: item.product_id,
            product_item_id: item.product_item_id || null,
            inventory_item_id: item.inventory_item_id || null,
            product_name: item.product_name,
            product_type: item.product_type || 'basic',
            sku: item.sku || '',
            uom_name: item.uom_name || 'Pcs',
            qty: Number(item.qty || 1),
            price: Number(item.price || 0),
            discount_amount: Number(item.discount_amount || 0),
            discount_type: item.discount_type || 'fixed',
            discount_rate: Number(item.discount_rate || 0),
            auto_promo_name: null,
            notes: item.notes || '',
        }))

        // Populate transactionDiscount
        if (Number(draft.discount_amount || draft.discount_value || 0) > 0) {
            transactionDiscount.type = draft.promo_code ? 'promo' : 'manual'
            transactionDiscount.value = Number(draft.discount_amount || draft.discount_value || 0)
            transactionDiscount.amount = Number(draft.discount_amount || draft.discount_value || 0)
            transactionDiscount.promo_code = draft.promo_code || null
            isManualOverride.value = true
        }

        // Fetch outlet settings & payment methods
        await fetchOutletSettings(draft.outlet_id)
        await fetchPaymentMethods(draft.outlet_id)

        form.defaults()
    } catch (e) {
        console.error('Failed to load draft detail:', e)
        toastStore.error('Gagal memuat data draf transaksi.')
        forceClose()
    } finally {
        isLoadingDraft.value = false
        calculateFinancials()
    }
}

const validateFormBasic = () => {
    if (!form.outlet_id) {
        modalStore.alert({
            type: 'warning',
            title: 'Outlet Belum Dipilih',
            message: 'Harap pilih outlet terlebih dahulu sebelum menyimpan transaksi.',
            confirmText: 'Mengerti',
        })
        return false
    }

    if (form.items.length === 0) {
        modalStore.alert({
            type: 'warning',
            title: 'Item Kosong',
            message: 'Harap tambahkan minimal 1 item produk ke dalam transaksi.',
            confirmText: 'Mengerti',
        })
        return false
    }

    const hasInvalidQty = form.items.some(i => !i.qty || Number(i.qty) <= 0)
    if (hasInvalidQty) {
        modalStore.alert({
            type: 'warning',
            title: 'Kuantitas Tidak Valid',
            message: 'Semua item produk harus memiliki kuantitas lebih dari 0.',
            confirmText: 'Mengerti',
        })
        return false
    }

    return true
}

const submitDraft = () => {
    if (!validateFormBasic()) return
    form.issue_now = false
    executeSubmit()
}

const handleIssueInvoiceClick = () => {
    if (!validateFormBasic()) return

    if (form.payment_term === 'cash') {
        form.payment.amount = financials.grandTotal
        if (!form.payment.payment_method_id) {
            modalStore.alert({
                type: 'warning',
                title: 'Metode Pembayaran Diperlukan',
                message: 'Silakan pilih metode pembayaran (Kas/Bank) untuk transaksi tunai.',
                confirmText: 'Pilih Metode',
            })
            return
        }
    } else if (Number(form.payment.amount || 0) > 0 && !form.payment.payment_method_id) {
        modalStore.alert({
            type: 'warning',
            title: 'Metode Pembayaran Diperlukan',
            message: 'Silakan pilih metode pembayaran (Kas/Bank) untuk pembayaran DP.',
            confirmText: 'Pilih Metode',
        })
        return
    }

    modalStore.open({
        title: 'Konfirmasi Penerbitan Faktur',
        size: 'max-w-lg',
        type: 'info',
        component: markRaw(SalesIssueReviewContent),
        props: {
            customerName: selectedCustomerName.value,
            transactionDate: form.transaction_date,
            paymentTerm: form.payment_term,
            dueDate: form.due_date,
            itemCount: form.items.length,
            grandTotal: financials.grandTotal,
            paymentAmount: form.payment.amount,
            balanceDue: financials.balanceDue,
        },
        confirmText: 'Ya, Terbitkan Faktur',
        cancelText: 'Periksa Kembali',
        confirmClass: 'btn-main',
        showCancel: true,
        showFooter: true,
        onConfirm: executeIssueSubmit,
    })
}

const executeIssueSubmit = () => {
    form.issue_now = true
    executeSubmit()
}

const executeSubmit = () => {
    if (form.issue_now && form.payment_term === 'cash') {
        form.payment.amount = financials.grandTotal
    }

    const submitRoute = isEditMode.value
        ? route('transactions.sales.update', props.transactionId)
        : route('transactions.sales.store')

    const submitMethod = isEditMode.value ? 'put' : 'post'

    form[submitMethod](submitRoute, {
        preserveScroll: true,
        onSuccess: () => {
            emit('success')
            forceClose()
        },
        onError: errors => {
            const errorKeys = Object.keys(errors)
            if (errorKeys.length > 0) {
                const mainError =
                    errors.error ||
                    errors.failed ||
                    errors.message ||
                    errors['payment.amount'] ||
                    errors['payment.payment_method_id'] ||
                    errors.items
                if (mainError) {
                    modalStore.alert({
                        type: 'warning',
                        title: 'Peringatan Transaksi',
                        message: mainError,
                        confirmText: 'Mengerti',
                    })
                }
            }
        },
    })
}

const handleDeleteDraft = () => {
    if (!props.transactionId) return

    modalStore.confirm({
        title: 'Konfirmasi Hapus Draf',
        message:
            'Apakah Anda yakin ingin menghapus draf faktur ini? Tindakan ini tidak dapat dikembalikan.',
        type: 'danger',
        confirmText: 'Ya, Hapus',
        cancelText: 'Batal',
        onConfirm: () => {
            router.delete(route('transactions.sales.destroy', props.transactionId), {
                preserveScroll: true,
                onSuccess: () => {
                    toastStore.success('Draf faktur berhasil dihapus')
                    emit('success')
                    forceClose()
                },
            })
        },
    })
}

onMounted(async () => {
    isMounted.value = true
    if (isEditMode.value) {
        await fetchDraftDetail()
    } else {
        if (form.outlet_id) {
            await fetchOutletSettings(form.outlet_id)
            await fetchPaymentMethods(form.outlet_id)
        }
        calculateFinancials()
    }
})
</script>
