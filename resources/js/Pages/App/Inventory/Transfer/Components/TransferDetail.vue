<template>
    <div class="space-y-3">
        <div v-if="loading" class="p-8 text-center text-slate-500">
            <div
                class="inline-block animate-spin rounded-full h-6 w-6 border-2 border-primary border-t-transparent mb-2"
            ></div>
            <p class="text-xs">Memuat rincian transfer...</p>
        </div>

        <div v-else-if="transferData" class="space-y-3">
            <!-- Header Info Card -->
            <div
                class="grid grid-cols-1 sm:grid-cols-3 gap-2 bg-slate-50 p-3 rounded-lg border border-slate-200 text-xs"
            >
                <div>
                    <span class="text-slate-500 font-medium">No. Dokumen Transfer</span>
                    <p class="font-bold text-sm text-slate-900 mt-0.5">
                        {{ transferData.transfer_number }}
                    </p>
                </div>
                <div>
                    <span class="text-slate-500 font-medium">Mode Mutasi</span>
                    <div class="mt-0.5">
                        <span
                            class="badge text-xs"
                            :class="transferData.transfer_mode === 'two_step' ? 'badge-purple' : 'badge-info'"
                        >
                            {{ transferData.transfer_mode === 'two_step' ? 'Kirim & Terima (2-Langkah)' : 'Transfer Langsung (1-Langkah)' }}
                        </span>
                    </div>
                </div>
                <div>
                    <span class="text-slate-500 font-medium">Status Dokumen</span>
                    <div class="mt-0.5">
                        <span
                            class="badge text-xs"
                            :class="getColor('StockTransferStatus', transferData.status)"
                        >
                            {{ getLabel('StockTransferStatus', transferData.status) }}
                        </span>
                    </div>
                </div>
                <div>
                    <span class="text-slate-500 font-medium">Outlet Asal (Pengirim)</span>
                    <p class="font-semibold text-slate-800 mt-0.5">
                        {{ transferData.from_outlet?.name || transferData.fromOutlet?.name || '-' }}
                    </p>
                </div>
                <div class="sm:col-span-2">
                    <span class="text-slate-500 font-medium">Outlet Tujuan (Penerima)</span>
                    <p class="font-semibold text-slate-800 mt-0.5">
                        {{ transferData.to_outlet?.name || transferData.toOutlet?.name || '-' }}
                    </p>
                </div>
                <div v-if="transferData.notes" class="col-span-full pt-1 border-t border-slate-200">
                    <span class="text-slate-500 font-medium">Catatan Transfer</span>
                    <p class="text-slate-700 mt-0.5 whitespace-pre-line">
                        {{ transferData.notes }}
                    </p>
                </div>
            </div>

            <!-- Voided Alert Banner -->
            <div
                v-if="transferData.status === 'void'"
                class="bg-rose-50 border border-rose-200 rounded-lg p-3 text-xs text-rose-800 space-y-1"
            >
                <div class="flex items-center gap-1.5 font-bold text-rose-900">
                    <span>⚠️ Mutasi Stok Telah Dibatalkan (Void)</span>
                </div>
                <div v-if="transferData.void_reason" class="text-rose-700">
                    <span class="font-semibold">Alasan Pembatalan:</span> {{ transferData.void_reason }}
                </div>
                <div class="text-[11px] text-rose-500">
                    Dibatalkan pada {{ formatDate(transferData.voided_at) }}
                    <span v-if="transferData.voided_by || transferData.voidedBy">
                        oleh {{ transferData.voided_by?.name || transferData.voidedBy?.name }}
                    </span>
                </div>
            <!-- Visual Timeline -->
            <div class="bg-white p-3 rounded-lg border border-slate-200">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block mb-2">
                    Tahapan Siklus Transfer
                </span>
                <div class="flex items-center justify-between text-xs">
                    <template v-if="transferData.transfer_mode === 'two_step'">
                        <div class="flex flex-col items-center flex-1">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-[11px] bg-primary text-white">1</div>
                            <span class="mt-1 font-semibold text-slate-800">Draf</span>
                        </div>
                        <div class="h-0.5 flex-1" :class="isStepApproved ? 'bg-primary' : 'bg-slate-200'"></div>
                        <div class="flex flex-col items-center flex-1">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-[11px]" :class="isStepApproved ? 'bg-primary text-white' : 'bg-slate-100 text-slate-400 border border-slate-300'">2</div>
                            <span class="mt-1 font-semibold" :class="isStepApproved ? 'text-slate-800' : 'text-slate-400'">Disetujui</span>
                        </div>
                        <div class="h-0.5 flex-1" :class="isStepShipped ? 'bg-primary' : 'bg-slate-200'"></div>
                        <div class="flex flex-col items-center flex-1">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-[11px]" :class="isStepShipped ? 'bg-primary text-white' : 'bg-slate-100 text-slate-400 border border-slate-300'">3</div>
                            <span class="mt-1 font-semibold" :class="isStepShipped ? 'text-slate-800' : 'text-slate-400'">Dikirim</span>
                        </div>
                        <div class="h-0.5 flex-1" :class="isStepReceived ? 'bg-emerald-600' : 'bg-slate-200'"></div>
                        <div class="flex flex-col items-center flex-1">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-[11px]" :class="isStepReceived ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-400 border border-slate-300'">4</div>
                            <span class="mt-1 font-semibold" :class="isStepReceived ? 'text-emerald-700' : 'text-slate-400'">Diterima</span>
                        </div>
                    </template>
                    <template v-else>
                        <div class="flex flex-col items-center flex-1">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-[11px] bg-primary text-white">1</div>
                            <span class="mt-1 font-semibold text-slate-800">Draf Dibuat</span>
                        </div>
                        <div class="h-0.5 flex-1" :class="isStepReceived ? 'bg-emerald-600' : 'bg-slate-200'"></div>
                        <div class="flex flex-col items-center flex-1">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-[11px]" :class="isStepReceived ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-400 border border-slate-300'">2</div>
                            <span class="mt-1 font-semibold" :class="isStepReceived ? 'text-emerald-700' : 'text-slate-400'">Transfer Selesai</span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Audit Trail -->
            <div
                class="grid grid-cols-2 sm:grid-cols-4 gap-2 bg-white p-3 rounded-lg border border-slate-200 text-xs text-slate-600"
            >
                <div>
                    <span class="text-slate-400 block mb-0.5 font-medium">Diajukan Oleh</span>
                    <p class="font-semibold text-slate-800">
                        {{ transferData.requester?.name || '-' }}
                    </p>
                    <p class="text-[11px] text-slate-500">
                        {{ formatDate(transferData.created_at) }}
                    </p>
                </div>
                <div>
                    <span class="text-slate-400 block mb-0.5 font-medium">Disetujui Oleh</span>
                    <p class="font-semibold text-slate-800">
                        {{ transferData.approver?.name || '-' }}
                    </p>
                    <p v-if="transferData.approved_at" class="text-[11px] text-slate-500">
                        {{ formatDate(transferData.approved_at) }}
                    </p>
                </div>
                <div>
                    <span class="text-slate-400 block mb-0.5 font-medium">Dikirim Oleh</span>
                    <p class="font-semibold text-slate-800">
                        {{ transferData.shipper?.name || '-' }}
                    </p>
                    <p v-if="transferData.shipped_at" class="text-[11px] text-slate-500">
                        {{ formatDate(transferData.shipped_at) }}
                    </p>
                </div>
                <div>
                    <span class="text-slate-400 block mb-0.5 font-medium">Diterima Oleh</span>
                    <p class="font-semibold text-slate-800">
                        {{ transferData.receiver?.name || '-' }}
                    </p>
                    <p v-if="transferData.received_at" class="text-[11px] text-slate-500">
                        {{ formatDate(transferData.received_at) }}
                    </p>
                </div>
            </div>

            <!-- Summary KPI Widgets -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50 text-center">
                    <span class="text-[11px] text-slate-500 font-medium block"
                        >Total Jenis Item</span
                    >
                    <span class="text-base font-bold text-slate-800">{{
                        transferData.items?.length || 0
                    }}</span>
                </div>
                <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50 text-center">
                    <span class="text-[11px] text-slate-500 font-medium block"
                        >Total Qty Dikirim</span
                    >
                    <span class="text-base font-bold text-slate-800">{{ totalQtySent }}</span>
                </div>
                <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50 text-center">
                    <span class="text-[11px] text-slate-500 font-medium block"
                        >Total Qty Diterima</span
                    >
                    <span
                        class="text-base font-bold"
                        :class="isReceived ? 'text-emerald-600' : 'text-slate-400'"
                    >
                        {{ isReceived ? totalQtyReceived : '-' }}
                    </span>
                </div>
                <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50 text-center">
                    <span class="text-[11px] text-slate-500 font-medium block">Total Selisih</span>
                    <span
                        class="text-base font-bold"
                        :class="totalDiscrepancy > 0 ? 'text-rose-600' : 'text-slate-400'"
                    >
                        {{ isReceived ? totalDiscrepancy : '-' }}
                    </span>
                </div>
            </div>

            <!-- Items List Table -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                        Rincian Item Transfer ({{ transferData.items?.length || 0 }})
                    </h3>
                </div>

                <div class="border border-slate-200 rounded-lg overflow-hidden">
                    <table class="w-full text-xs text-left">
                        <thead
                            class="bg-slate-100 text-slate-600 font-medium border-b border-slate-200"
                        >
                            <tr>
                                <th class="py-2 px-3 w-10 text-center">No</th>
                                <th class="py-2 px-3">Item / Bahan</th>
                                <th class="py-2 px-3 w-20 text-center">Satuan</th>
                                <th class="py-2 px-3 w-24 text-right">Qty Dikirim</th>
                                <th v-if="isReceived" class="py-2 px-3 w-24 text-right">
                                    Qty Diterima
                                </th>
                                <th v-if="isReceived" class="py-2 px-3 w-20 text-right">Selisih</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr
                                v-for="(item, idx) in transferData.items"
                                :key="item.id"
                                class="hover:bg-slate-50 transition-colors"
                            >
                                <td class="py-2 px-3 text-center text-slate-400">{{ idx + 1 }}</td>
                                <td class="py-2 px-3">
                                    <div class="font-semibold text-slate-800">
                                        {{
                                            item.inventory_item?.name ||
                                            item.inventoryItem?.name ||
                                            '-'
                                        }}
                                    </div>
                                    <div
                                        v-if="item.inventory_item?.sku || item.inventoryItem?.sku"
                                        class="text-[11px] text-slate-400"
                                    >
                                        SKU:
                                        {{ item.inventory_item?.sku || item.inventoryItem?.sku }}
                                    </div>
                                </td>
                                <td class="py-2 px-3 text-center text-slate-600">
                                    {{
                                        item.inventory_item?.uom?.name ||
                                        item.inventoryItem?.uom?.name ||
                                        '-'
                                    }}
                                </td>
                                <td class="py-2 px-3 text-right font-medium text-slate-800">
                                    {{ item.qty_formatted }}
                                </td>
                                <td
                                    v-if="isReceived"
                                    class="py-2 px-3 text-right font-medium text-emerald-700"
                                >
                                    {{ item.qty_received_formatted || '0' }}
                                </td>
                                <td v-if="isReceived" class="py-2 px-3 text-right">
                                    <span
                                        class="font-semibold"
                                        :class="
                                            (item.qty_difference ?? (item.qty - item.qty_received)) > 0
                                                ? 'text-rose-600'
                                                : 'text-slate-500'
                                        "
                                    >
                                        {{ formatNumber(item.qty_difference ?? (item.qty - item.qty_received)) }}
                                    </span>
                                    <div
                                        v-if="item.difference_reason"
                                        class="text-[10px] text-rose-500 italic mt-0.5"
                                    >
                                        {{ item.difference_reason }}
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- In-place Reject Form -->
            <div
                v-if="showRejectForm"
                class="bg-rose-50 p-3 rounded-lg border border-rose-200 space-y-2"
            >
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-bold text-rose-800 uppercase tracking-wider">
                        Form Penolakan Transfer Stok
                    </h4>
                </div>
                <p class="text-xs text-rose-600">
                    Tuliskan alasan penolakan transfer stok ini secara jelas agar pemohon dapat
                    mengetahuinya.
                </p>
                <TextareaField
                    id="reject_notes"
                    v-model="rejectForm.notes"
                    label="Alasan Penolakan"
                    placeholder="Misal: Stok di outlet asal sedang dibutuhkan untuk pesanan katering mendadak."
                    rows="2"
                    required
                />
                <div class="flex justify-end gap-2 pt-1">
                    <button
                        type="button"
                        class="btn btn-flat btn-sm"
                        :disabled="rejectForm.processing"
                        @click="showRejectForm = false"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        class="btn btn-danger btn-sm"
                        :disabled="rejectForm.processing || !rejectForm.notes?.trim()"
                        @click="submitReject"
                    >
                        Konfirmasi Tolak Transfer
                    </button>
                </div>
            </div>

            <!-- In-place Void Form -->
            <div
                v-if="showVoidForm"
                class="bg-rose-50 p-3 rounded-lg border border-rose-200 space-y-2"
            >
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-bold text-rose-800 uppercase tracking-wider">
                        Form Pembatalan (Void) Transfer Stok
                    </h4>
                </div>
                <p class="text-xs text-rose-600">
                    Membatalkan transfer stok ini akan menghasilkan entri pembalik simetris (storno) yang aman untuk audit.
                </p>
                <TextareaField
                    id="void_reason"
                    v-model="voidForm.void_reason"
                    label="Alasan Pembatalan"
                    placeholder="Misal: Salah memilih outlet tujuan atau pesanan barang dibatalkan."
                    rows="2"
                    required
                />
                <div class="flex justify-end gap-2 pt-1">
                    <button
                        type="button"
                        class="btn btn-flat btn-sm"
                        :disabled="voidForm.processing"
                        @click="showVoidForm = false"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        class="btn btn-danger btn-sm"
                        :disabled="voidForm.processing || !voidForm.void_reason?.trim()"
                        @click="submitVoid"
                    >
                        Konfirmasi Void Transfer
                    </button>
                </div>
            </div>
        </div>

        <!-- Teleport Drawer Footer -->
        <Teleport v-if="isMounted && transferData" to="#popUpFooter">
            <div class="flex justify-between w-full">
                <div class="flex items-center gap-2">
                    <button type="button" class="btn btn-flat btn-sm" @click="close">Tutup</button>
                    <a
                        :href="route('inventory.transfers.export.pdf', transferId)"
                        target="_blank"
                        class="btn btn-flat text-rose-600 btn-sm hover:bg-rose-50"
                    >
                        <FontAwesomeIcon :icon="faFilePdf" /> Cetak PDF
                    </a>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Actions for PENDING -->
                    <template v-if="transferData.status === 'pending'">
                        <button
                            v-if="canApprove && !showRejectForm && !showVoidForm"
                            type="button"
                            class="btn btn-danger btn-sm"
                            :disabled="actionForm.processing"
                            @click="showRejectForm = true"
                        >
                            Tolak
                        </button>
                        <button
                            v-if="canApprove && !showRejectForm && !showVoidForm"
                            type="button"
                            class="btn btn-main btn-sm"
                            :disabled="actionForm.processing"
                            @click="confirmApprove"
                        >
                            {{ transferData.transfer_mode === 'two_step' ? 'Setujui Transfer' : 'Setujui & Selesaikan' }}
                        </button>
                    </template>

                    <!-- Actions for APPROVED -->
                    <template
                        v-else-if="transferData.status === 'approved'"
                    >
                        <button
                            v-if="canShip && !showVoidForm"
                            type="button"
                            class="btn btn-main btn-sm"
                            :disabled="actionForm.processing"
                            @click="confirmShip"
                        >
                            Kirim Barang
                        </button>
                    </template>

                    <!-- Actions for IN_TRANSIT -->
                    <template
                        v-else-if="transferData.status === 'in_transit'"
                    >
                        <button
                            v-if="canReceive && !showVoidForm"
                            type="button"
                            class="btn btn-main btn-sm"
                            @click="emit('openReceive', transferData)"
                        >
                            Terima Barang
                        </button>
                    </template>

                    <!-- Void Action for non-void, non-rejected statuses -->
                    <button
                        v-if="canVoid && !['void', 'rejected'].includes(transferData.status) && !showVoidForm && !showRejectForm"
                        type="button"
                        class="btn btn-outline-danger btn-sm"
                        :disabled="actionForm.processing"
                        @click="showVoidForm = true"
                    >
                        Void / Batalkan
                    </button>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import axios from 'axios'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faFilePdf } from '@fortawesome/free-solid-svg-icons'
import { usePopUpStore } from '@/store/popup'
import { useModalStore } from '@/store/notification'
import { useAuth } from '@/Composable/useAuth'
import { useEnum } from '@/Composable/useEnum'
import TextareaField from '@/Components/Form/TextareaField.vue'

const props = defineProps({
    transferId: String,
})

const emit = defineEmits(['openReceive', 'refresh'])

const popUpStore = usePopUpStore()
const modalStore = useModalStore()
const { getLabel, getColor } = useEnum()
const { canAny } = useAuth()

const isMounted = ref(false)
const loading = ref(false)
const transferData = ref(null)
const showRejectForm = ref(false)
const showVoidForm = ref(false)

const actionForm = useForm({})
const rejectForm = useForm({ notes: '' })
const voidForm = useForm({ void_reason: '' })

// SoD & RBAC Checks
const canApprove = computed(() => {
    return canAny([
        'inventory.transfer.validate',
        'inventory.transfer.approve',
        'inventory.*',
        'business.*',
    ])
})

const canShip = computed(() =>
    canAny(['inventory.transfer.update', 'inventory.transfer.ship', 'inventory.*', 'business.*'])
)
const canReceive = computed(() =>
    canAny(['inventory.transfer.receive', 'inventory.*', 'business.*'])
)
const canVoid = computed(() =>
    canAny(['inventory.transfer.validate', 'inventory.*', 'business.*'])
)

const isStepApproved = computed(() => {
    const s = transferData.value?.status
    return ['approved', 'in_transit', 'completed'].includes(s) || !!transferData.value?.approved_by
})

const isStepShipped = computed(() => {
    const s = transferData.value?.status
    return ['in_transit', 'completed'].includes(s) || !!transferData.value?.shipped_at
})

const isStepReceived = computed(() => {
    const s = transferData.value?.status
    return s === 'completed' || !!transferData.value?.received_at
})

const isReceived = computed(() => {
    const status = transferData.value?.status
    return status === 'completed' || (status === 'void' && !!transferData.value?.received_at)
})

const totalQtySent = computed(() => {
    if (!transferData.value?.items) return '0'
    const sum = transferData.value.items.reduce((acc, item) => acc + Number(item.qty || 0), 0)
    return formatNumber(sum)
})

const totalQtyReceived = computed(() => {
    if (!transferData.value?.items) return '0'
    const sum = transferData.value.items.reduce(
        (acc, item) => acc + Number(item.qty_received || 0),
        0
    )
    return formatNumber(sum)
})

const totalDiscrepancy = computed(() => {
    if (!transferData.value?.items) return '0'
    const sum = transferData.value.items.reduce((acc, item) => {
        const diff = Number(item.qty || 0) - Number(item.qty_received || 0)
        return acc + Math.max(0, diff)
    }, 0)
    return formatNumber(sum)
})

const fetchDetail = async () => {
    if (!props.transferId) return
    loading.value = true
    try {
        const response = await axios.get(route('inventory.transfers.show', props.transferId))
        transferData.value = response.data.data
    } catch (error) {
        console.error('Gagal mengambil detail transfer', error)
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    isMounted.value = true
    showRejectForm.value = false
    showVoidForm.value = false
    rejectForm.reset()
    voidForm.reset()
    fetchDetail()
})

const close = () => {
    popUpStore.close()
}

const confirmApprove = () => {
    const isDirect = transferData.value?.transfer_mode === 'direct'
    modalStore.open({
        title: isDirect ? 'Setujui & Selesaikan Transfer Langsung?' : 'Setujui Dokumen Transfer?',
        message: isDirect
            ? 'Transfer 1-langkah akan langsung memotong stok dari outlet pengirim dan menambah stok di outlet tujuan seketika.'
            : 'Transfer stok yang disetujui akan siap untuk dikirimkan dari outlet asal ke outlet tujuan.',
        confirmText: isDirect ? 'Ya, Setujui & Selesaikan' : 'Ya, Setujui',
        confirmButtonClass: 'btn btn-main',
        onConfirm: () => {
            actionForm.post(route('inventory.transfers.approve', props.transferId), {
                preserveScroll: true,
                onSuccess: () => {
                    fetchDetail()
                    emit('refresh')
                },
            })
        },
    })
}

const confirmShip = () => {
    modalStore.open({
        title: 'Kirim Barang Transfer?',
        message:
            'Stok pada outlet asal akan langsung dipotong dari saldo aktif dan status dokumen berubah menjadi Dalam Perjalanan.',
        confirmText: 'Ya, Kirim Sekarang',
        confirmButtonClass: 'btn btn-main',
        onConfirm: () => {
            actionForm.post(route('inventory.transfers.ship', props.transferId), {
                preserveScroll: true,
                onSuccess: () => {
                    fetchDetail()
                    emit('refresh')
                },
            })
        },
    })
}

const submitReject = () => {
    modalStore.open({
        title: 'Konfirmasi Tolak Transfer?',
        message:
            'Transfer stok yang ditolak tidak dapat diproses lebih lanjut dan tidak akan memotong saldo stok.',
        confirmText: 'Ya, Tolak Transfer',
        confirmButtonClass: 'btn btn-danger',
        onConfirm: () => {
            rejectForm.post(route('inventory.transfers.reject', props.transferId), {
                preserveScroll: true,
                onSuccess: () => {
                    showRejectForm.value = false
                    fetchDetail()
                    emit('refresh')
                },
            })
        },
    })
}

const submitVoid = () => {
    modalStore.open({
        title: 'Konfirmasi Pembatalan (Void) Transfer?',
        message:
            'Mutasi pembalik (storno) akan otomatis diterbitkan untuk mengembalikan saldo stok ke kondisi semula. Tindakan ini tidak dapat dibatalkan.',
        confirmText: 'Ya, Batalkan (Void)',
        confirmButtonClass: 'btn btn-danger',
        onConfirm: () => {
            voidForm.post(route('inventory.transfers.void', props.transferId), {
                preserveScroll: true,
                onSuccess: () => {
                    showVoidForm.value = false
                    fetchDetail()
                    emit('refresh')
                },
            })
        },
    })
}

const formatDate = dateString => {
    if (!dateString) return '-'
    const date = new Date(dateString)
    return date.toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

const formatNumber = num => {
    return Number(num || 0).toLocaleString('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })
}
</script>
