<template>
    <div v-if="purchase" class="space-y-3">
        <!-- Informasi Header Dokumen & Status -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <div class="bg-slate-50 border border-slate-200 p-3 rounded-lg space-y-2">
                <div>
                    <div class="text-[11px] font-medium text-slate-400">Nomor Dokumen PO</div>
                    <div class="font-bold text-sm text-slate-800">{{ purchase.po_number }}</div>
                    <div v-if="purchase.reference_number" class="text-[11px] text-slate-500">
                        No. Referensi:
                        <span class="font-medium text-slate-700">{{
                            purchase.reference_number
                        }}</span>
                    </div>
                </div>
                <div>
                    <div class="text-[11px] font-medium text-slate-400">Status Pembelian</div>
                    <div class="mt-0.5">
                        <span
                            class="badge"
                            :class="
                                $enums.PurchaseOrderStatus._meta[purchase.status]?.color ||
                                'badge-gray'
                            "
                        >
                            {{
                                $enums.PurchaseOrderStatus._meta[purchase.status]?.label ||
                                purchase.status
                            }}
                        </span>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs pt-1 border-t border-slate-200/60">
                    <div>
                        <div class="text-[11px] font-medium text-slate-400">Tanggal Pesan</div>
                        <div class="font-medium text-slate-700">
                            {{ formatDateID(purchase.order_date || purchase.created_at) }}
                        </div>
                    </div>
                    <div>
                        <div class="text-[11px] font-medium text-slate-400">Dibuat Oleh</div>
                        <div class="font-medium text-slate-700">
                            {{ purchase.creator?.name || '-' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-slate-50 border border-slate-200 p-3 rounded-lg space-y-2">
                <div>
                    <div class="text-[11px] font-medium text-slate-400">Pemasok (Supplier)</div>
                    <div class="font-bold text-xs text-slate-800">
                        {{ purchase.supplier?.name || '-' }}
                    </div>
                    <div v-if="purchase.supplier?.phone" class="text-[11px] text-slate-500">
                        Telp: {{ purchase.supplier?.phone }}
                    </div>
                    <div v-if="purchase.supplier?.email" class="text-[11px] text-slate-500">
                        Email: {{ purchase.supplier?.email }}
                    </div>
                </div>
                <div class="border-t border-slate-200/60 pt-1.5">
                    <div class="text-[11px] font-medium text-slate-400">Outlet Tujuan</div>
                    <div class="font-bold text-xs text-slate-800">
                        {{ purchase.outlet?.name || '-' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Catatan Pembelian Jika Ada -->
        <div
            v-if="purchase.notes"
            class="bg-amber-50/60 p-2.5 rounded-lg border border-amber-200/60 text-xs"
        >
            <span class="font-bold text-amber-900 mr-1">Catatan:</span>
            <span class="text-amber-800">{{ purchase.notes }}</span>
        </div>

        <!-- Navigasi Tab / Segmented (Rincian Barang, Surat Jalan Penerimaan, Riwayat Retur) -->
        <div class="border-b border-slate-200 flex items-center gap-4 text-xs font-semibold">
            <button
                type="button"
                class="pb-2 relative cursor-pointer transition-colors"
                :class="
                    activeTab === 'items'
                        ? 'text-main font-bold border-b-2 border-main'
                        : 'text-slate-500 hover:text-slate-800'
                "
                @click="activeTab = 'items'"
            >
                Daftar Barang ({{ purchase.items?.length || 0 }})
            </button>
            <button
                type="button"
                class="pb-2 relative cursor-pointer transition-colors"
                :class="
                    activeTab === 'receipts'
                        ? 'text-main font-bold border-b-2 border-main'
                        : 'text-slate-500 hover:text-slate-800'
                "
                @click="activeTab = 'receipts'"
            >
                Surat Jalan Penerimaan ({{ purchase.goods_receipts?.length || 0 }})
            </button>
            <button
                type="button"
                class="pb-2 relative cursor-pointer transition-colors"
                :class="
                    activeTab === 'returns'
                        ? 'text-main font-bold border-b-2 border-main'
                        : 'text-slate-500 hover:text-slate-800'
                "
                @click="activeTab = 'returns'"
            >
                Riwayat Retur ({{ purchase.purchase_returns?.length || 0 }})
            </button>
        </div>

        <!-- TAB 1: RINCIAN BARANG -->
        <div
            v-if="activeTab === 'items'"
            class="border border-slate-200 rounded-lg overflow-hidden bg-white"
        >
            <div class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
                <div
                    v-for="(item, index) in purchase.items"
                    :key="item.id || index"
                    class="p-2.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2 hover:bg-slate-50/50"
                >
                    <div class="min-w-0">
                        <div class="font-bold text-xs text-slate-800 truncate">
                            {{ item.inventory_item?.name || '-' }}
                        </div>
                        <div class="text-[11px] text-slate-400">
                            SKU: {{ item.inventory_item?.sku || '-' }} | Satuan:
                            <span class="text-slate-600 font-medium">{{
                                item.uom?.name || '-'
                            }}</span>
                        </div>
                        <!-- Status Pemenuhan Fisik -->
                        <div class="text-[11px] mt-0.5 flex flex-wrap gap-2">
                            <span class="text-slate-600">
                                Pesan: <strong>{{ formatQuantity(item.qty_ordered) }}</strong>
                            </span>
                            <span class="text-emerald-700">
                                Diterima: <strong>{{ formatQuantity(item.qty_received) }}</strong>
                            </span>
                            <span
                                v-if="Number(item.qty_ordered) > Number(item.qty_received)"
                                class="text-amber-600"
                            >
                                Sisa:
                                <strong>{{
                                    formatQuantity(
                                        Number(item.qty_ordered) - Number(item.qty_received)
                                    )
                                }}</strong>
                            </span>
                        </div>
                    </div>

                    <div class="text-right shrink-0 border-t sm:border-t-0 pt-1 sm:pt-0">
                        <div class="text-[11px] text-slate-500">
                            {{ formatQuantity(item.qty_ordered) }} ×
                            {{ formatCurrency(item.purchase_price) }}
                        </div>
                        <div
                            v-if="Number(item.discount_amount) > 0"
                            class="text-[10px] text-emerald-600"
                        >
                            Diskon: -{{ formatCurrency(item.discount_amount) }}
                        </div>
                        <div v-if="Number(item.tax_amount) > 0" class="text-[10px] text-slate-500">
                            Pajak: +{{ formatCurrency(item.tax_amount) }}
                        </div>
                        <div class="font-bold text-xs text-slate-800 mt-0.5">
                            {{ formatCurrency(item.subtotal) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Keseluruhan -->
            <div
                class="p-3 bg-slate-50 border-t border-slate-200 flex items-center justify-between"
            >
                <span class="font-bold text-xs text-slate-700">Total Pembelian</span>
                <span class="font-bold text-sm text-main">
                    {{ formatCurrency(purchase.total_amount) }}
                </span>
            </div>
        </div>

        <!-- TAB 2: SURAT JALAN PENERIMAAN (GOODS RECEIPTS) -->
        <div v-else-if="activeTab === 'receipts'" class="space-y-2">
            <div
                v-if="!purchase.goods_receipts || purchase.goods_receipts.length === 0"
                class="text-center py-8 text-xs text-slate-500 border border-dashed border-slate-200 rounded-lg bg-slate-50/50"
            >
                Belum ada surat jalan penerimaan barang yang dicatat.
            </div>

            <div v-else class="space-y-2 max-h-80 overflow-y-auto pr-1">
                <div
                    v-for="receipt in purchase.goods_receipts"
                    :key="receipt.id"
                    class="p-3 border border-slate-200 rounded-lg bg-white space-y-2"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-bold text-xs text-slate-800">
                                {{ receipt.receipt_number }}
                                <span
                                    v-if="receipt.delivery_order_number"
                                    class="text-slate-500 font-normal ml-1"
                                >
                                    (SJ: {{ receipt.delivery_order_number }})
                                </span>
                            </div>
                            <div
                                class="text-[11px] text-slate-400 flex flex-wrap items-center gap-1.5 mt-0.5"
                            >
                                <span>Tgl Terima: {{ formatDateID(receipt.received_at) }}</span>
                                <span>•</span>
                                <span>Penerima: {{ receipt.receiver?.name || '-' }}</span>
                                <template
                                    v-if="
                                        receipt.status === 'completed' ||
                                        receipt.status === $enums.GoodsReceiptStatus?.Completed
                                    "
                                >
                                    <span>•</span>
                                    <span
                                        v-if="receipt.is_returnable"
                                        class="badge badge-info text-[10px] !py-0 !px-1.5"
                                        :title="
                                            'Batas akhir retur: ' +
                                            (receipt.return_deadline
                                                ? formatDateID(receipt.return_deadline)
                                                : '-')
                                        "
                                    >
                                        Masa Retur: {{ receipt.remaining_return_days }} hari lagi
                                    </span>
                                    <span
                                        v-else
                                        class="badge badge-gray text-[10px] !py-0 !px-1.5"
                                        title="Batas waktu retur untuk surat jalan ini telah berakhir"
                                    >
                                        Masa Retur Berakhir
                                    </span>
                                </template>
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <span
                                class="badge"
                                :class="
                                    $enums.GoodsReceiptStatus._meta[receipt.status]?.color ||
                                    'badge-gray'
                                "
                            >
                                {{
                                    $enums.GoodsReceiptStatus._meta[receipt.status]?.label ||
                                    receipt.status
                                }}
                            </span>

                            <!-- Unduh PDF Surat Jalan -->
                            <a
                                :href="route('inventory.purchases.receipts.pdf', receipt.id)"
                                target="_blank"
                                class="btn btn-flat btn-sm text-slate-600 hover:text-red-600 h-7 w-7 !p-0 inline-flex items-center justify-center cursor-pointer"
                                title="Unduh Berkas Surat Jalan (PDF)"
                            >
                                <FontAwesomeIcon :icon="faFilePdf" />
                            </a>

                            <!-- Tombol Retur Barang dari Surat Jalan ini (Icon Only) -->
                            <button
                                v-if="
                                    receipt.status === $enums.GoodsReceiptStatus.Completed &&
                                    receipt.is_returnable &&
                                    purchase.status !== $enums.PurchaseOrderStatus.Cancelled
                                "
                                v-can="$enums.PermissionEnum?.PURCHASE_ORDER_RETURN"
                                type="button"
                                class="btn btn-flat btn-sm text-danger hover:text-red-700 h-7 w-7 !p-0 inline-flex items-center justify-center cursor-pointer"
                                title="Retur barang dari surat jalan ini ke pemasok"
                                @click="$emit('open-return', purchase, receipt)"
                            >
                                <FontAwesomeIcon :icon="faReply" />
                            </button>

                            <!-- Tombol Void Surat Jalan (Hanya jika belum batal dan PO belum dibatalkan) -->
                            <button
                                v-if="
                                    receipt.status === $enums.GoodsReceiptStatus.Completed &&
                                    purchase.status !== $enums.PurchaseOrderStatus.Cancelled
                                "
                                v-can="$enums.PermissionEnum?.PURCHASE_ORDER_VOID"
                                type="button"
                                class="btn btn-flat btn-sm text-danger h-7 w-7 !p-0 inline-flex items-center justify-center cursor-pointer"
                                title="Batalkan (Void) Surat Jalan ini"
                                @click="confirmVoidReceipt(receipt)"
                            >
                                <FontAwesomeIcon :icon="faTrashCan" />
                            </button>
                        </div>
                    </div>

                    <!-- Rincian Item yang Diterima pada SJ ini -->
                    <div class="border-t border-slate-100 pt-1.5 text-xs">
                        <div
                            v-for="rItem in receipt.items"
                            :key="rItem.id"
                            class="flex justify-between items-center py-1 text-[11px]"
                        >
                            <span class="text-slate-700 font-medium">
                                {{ rItem.inventory_item?.name || 'Item' }}
                            </span>
                            <span class="font-semibold text-emerald-700">
                                {{ formatQuantity(rItem.received_purchase_qty) }}
                                {{ rItem.uom?.name || rItem.inventory_item?.uom?.name || '-' }}
                                <span
                                    v-if="Number(rItem.conversion_factor) !== 1"
                                    class="text-slate-400 font-normal ml-1"
                                >
                                    (Masuk: {{ formatQuantity(rItem.received_inventory_qty) }}
                                    {{ rItem.inventory_item?.uom?.name || '-' }})
                                </span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: RIWAYAT RETUR (PURCHASE RETURNS) -->
        <div v-else-if="activeTab === 'returns'" class="space-y-2">
            <div
                v-if="!purchase.purchase_returns || purchase.purchase_returns.length === 0"
                class="text-center py-8 text-xs text-slate-500 border border-dashed border-slate-200 rounded-lg bg-slate-50/50"
            >
                Belum ada pengembalian (retur) barang ke pemasok.
            </div>

            <div v-else class="space-y-2 max-h-80 overflow-y-auto pr-1">
                <div
                    v-for="ret in purchase.purchase_returns"
                    :key="ret.id"
                    class="p-3 border border-red-100 rounded-lg bg-red-50/20 space-y-2"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-bold text-xs text-slate-800">
                                {{ ret.return_number }}
                            </div>
                            <div class="text-[11px] text-slate-400">
                                Tgl: {{ formatDateID(ret.return_date) }} | Alasan: {{ ret.reason }}
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <span
                                class="badge"
                                :class="
                                    $enums.PurchaseReturnStatus._meta[ret.status]?.color ||
                                    'badge-gray'
                                "
                            >
                                {{
                                    $enums.PurchaseReturnStatus._meta[ret.status]?.label ||
                                    ret.status
                                }}
                            </span>

                            <!-- Unduh PDF Retur -->
                            <a
                                :href="route('inventory.purchases.returns.pdf', ret.id)"
                                target="_blank"
                                class="btn btn-flat btn-sm text-slate-600 hover:text-red-600 h-7 w-7 !p-0 inline-flex items-center justify-center cursor-pointer"
                                title="Unduh Berkas Retur (PDF)"
                            >
                                <FontAwesomeIcon :icon="faFilePdf" />
                            </a>

                            <!-- Tombol Void Retur -->
                            <button
                                v-if="
                                    ret.status === $enums.PurchaseReturnStatus.Completed &&
                                    purchase.status !== $enums.PurchaseOrderStatus.Cancelled
                                "
                                v-can="$enums.PermissionEnum?.PURCHASE_ORDER_VOID"
                                type="button"
                                class="btn btn-flat btn-sm text-danger h-7 w-7 !p-0 inline-flex items-center justify-center cursor-pointer"
                                title="Batalkan (Void) Retur ini"
                                @click="confirmVoidReturn(ret)"
                            >
                                <FontAwesomeIcon :icon="faTrashCan" />
                            </button>
                        </div>
                    </div>

                    <!-- Item yang Diretur -->
                    <div class="border-t border-slate-100 pt-1.5 text-xs">
                        <div
                            v-for="retItem in ret.items"
                            :key="retItem.id"
                            class="flex justify-between items-center py-0.5 text-[11px]"
                        >
                            <span class="text-slate-700 font-medium">{{
                                retItem.inventory_item?.name || 'Item'
                            }}</span>
                            <span class="font-semibold text-danger">
                                -{{ formatQuantity(retItem.return_purchase_qty) }}
                                {{ retItem.uom?.name || retItem.inventory_item?.uom?.name || '-' }}
                                ({{ formatCurrency(retItem.subtotal) }})
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <Teleport v-if="isMounted" to="#popUpFooter">
        <button type="button" class="btn btn-flat" @click="close">Tutup</button>

        <a
            v-if="purchase"
            :href="route('inventory.purchases.pdf', purchase.id)"
            target="_blank"
            class="btn btn-outline-main inline-flex items-center gap-1"
        >
            <FontAwesomeIcon :icon="faFilePdf" />
            <span>Cetak Dokumen PO</span>
        </a>

        <!-- Tombol Batalkan (Void) Pembelian (Jika berstatus Received / PartialReceived) -->
        <button
            v-if="canVoidPurchase"
            v-can="$enums.PermissionEnum?.PURCHASE_ORDER_VOID"
            type="button"
            class="btn btn-outline-danger inline-flex items-center gap-1"
            @click="confirmVoidPurchase"
        >
            <FontAwesomeIcon :icon="faBan" />
            <span>Batalkan (Void) Pembelian</span>
        </button>

        <!-- Tombol Terima Barang (Jika masih ada yang belum diterima) -->
        <button
            v-if="canReceive"
            type="button"
            class="btn btn-main inline-flex items-center gap-1"
            @click="$emit('open-receive', purchase)"
        >
            <FontAwesomeIcon :icon="faBoxOpen" />
            <span>+ Terima Barang</span>
        </button>
    </Teleport>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faFilePdf, faBoxOpen, faReply, faTrashCan, faBan } from '@fortawesome/free-solid-svg-icons'
import { formatDateID } from '@/Composable/date'
import { usePopUpStore } from '@/store/popup'
import { useModalStore } from '@/store/notification'
import { useEnum } from '@/Composable/useEnum'

const popUpStore = usePopUpStore()
const modalStore = useModalStore()
const { enums } = useEnum()

defineEmits(['open-receive', 'open-return'])

const props = defineProps({
    purchase: {
        type: Object,
        default: null,
    },
})

const isMounted = ref(false)
const activeTab = ref('items')

onMounted(() => {
    isMounted.value = true
})

const canReceive = computed(() => {
    if (!props.purchase) return false
    const status = props.purchase.status
    return (
        status === enums.PurchaseOrderStatus?.Ordered ||
        status === enums.PurchaseOrderStatus?.PartialReceived
    )
})

const canVoidPurchase = computed(() => {
    if (!props.purchase) return false
    const status = props.purchase.status
    return (
        status === enums.PurchaseOrderStatus?.Received ||
        status === enums.PurchaseOrderStatus?.PartialReceived
    )
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

const confirmVoidReceipt = receipt => {
    modalStore.confirm({
        title: 'Batalkan Penerimaan Barang',
        message: `Yakin ingin membatalkan penerimaan surat jalan ${receipt.delivery_order_number || receipt.receipt_number}? Stok barang yang telah masuk akan ditarik kembali dari inventori outlet.`,
        type: 'danger',
        confirmText: 'Ya, Batalkan Penerimaan',
        onConfirm: () => {
            router.post(
                route('inventory.purchases.receipts.void', receipt.id),
                {},
                {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        popUpStore.close()
                    },
                }
            )
        },
    })
}

const confirmVoidReturn = ret => {
    modalStore.confirm({
        title: 'Batalkan Retur Barang',
        message: `Yakin ingin membatalkan retur ${ret.return_number}? Stok barang yang sebelumnya dikembalikan ke pemasok akan ditambahkan kembali ke inventori outlet.`,
        type: 'danger',
        confirmText: 'Ya, Batalkan Retur',
        onConfirm: () => {
            router.post(
                route('inventory.purchases.returns.void', ret.id),
                {},
                {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        popUpStore.close()
                    },
                }
            )
        },
    })
}

const confirmVoidPurchase = () => {
    if (!props.purchase) return
    modalStore.confirm({
        title: 'Batalkan (Void) Seluruh Pembelian',
        message: `Yakin ingin membatalkan seluruh transaksi pembelian ${props.purchase.po_number}? Seluruh surat jalan penerimaan barang akan dibatalkan, stok ditarik kembali, dan transaksi akan dikunci permanen.`,
        type: 'danger',
        confirmText: 'Ya, Batalkan Seluruh Pembelian',
        onConfirm: () => {
            router.post(
                route('inventory.purchases.void', props.purchase.id),
                {},
                {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        popUpStore.close()
                    },
                }
            )
        },
    })
}

const close = () => {
    popUpStore.close()
}
</script>
