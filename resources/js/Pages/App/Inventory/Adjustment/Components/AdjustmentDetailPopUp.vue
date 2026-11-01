<template>
    <div>
        <div v-if="isLoading" class="space-y-2 animate-pulse">
            <div class="grid grid-cols-2 gap-2">
                <div class="h-12 bg-gray-200 rounded"></div>
                <div class="h-12 bg-gray-200 rounded"></div>
                <div class="h-12 bg-gray-200 rounded"></div>
                <div class="h-12 bg-gray-200 rounded"></div>
                <div class="h-12 bg-gray-200 rounded"></div>
                <div class="h-12 bg-gray-200 rounded"></div>
            </div>
            <div class="mt-2">
                <div class="h-6 bg-gray-200 rounded w-1/4 mb-2"></div>
                <div class="h-32 bg-gray-200 rounded"></div>
            </div>
        </div>
        <div v-else-if="adjustment" class="space-y-3">
            <!-- Stepper Progress & Alert Banner -->
            <DocumentProgressStepper
                :steps="[
                    { key: 'draft', label: 'Draf' },
                    { key: 'approved', label: 'Disetujui' },
                ]"
                :current-step="
                    adjustment.status === $enums.AdjustmentStatus.Approved ? 'approved' : 'draft'
                "
                :is-voided="adjustment.status === $enums.AdjustmentStatus.Voided"
                :void-info="{
                    voided_at: adjustment.voided_at,
                    voided_by: adjustment.voidedBy || adjustment.voided_by,
                    void_reason: adjustment.void_reason,
                }"
                :is-rejected="adjustment.status === $enums.AdjustmentStatus.Rejected"
                :reject-info="{
                    rejected_at: adjustment.approved_at,
                    rejected_by: adjustment.approver,
                    notes: adjustment.notes,
                }"
            />

            <div
                class="grid grid-cols-2 gap-2 text-sm bg-slate-50/50 p-3 rounded-lg border border-slate-200"
            >
                <div>
                    <p class="text-xs text-slate-500">Nomor Dokumen</p>
                    <p class="font-bold text-slate-800">{{ adjustment.adjustment_number }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Status</p>
                    <span
                        class="badge"
                        :class="getColor('AdjustmentStatus', adjustment.status) || 'badge-gray'"
                    >
                        {{ getLabel('AdjustmentStatus', adjustment.status) }}
                    </span>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Tanggal Dibuat</p>
                    <p class="text-slate-700">{{ formatDateTimeSimple(adjustment.created_at) }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Dibuat Oleh</p>
                    <p class="text-slate-700">{{ adjustment.creator?.name || '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Outlet</p>
                    <p class="text-slate-700">{{ adjustment.outlet?.name || '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Alasan</p>
                    <p class="font-medium text-slate-700">
                        {{ getLabel('AdjustmentReason', adjustment.reason) }}
                    </p>
                </div>
                <div
                    v-if="adjustment.approver"
                    class="col-span-2 border-t border-slate-200 pt-2 mt-1"
                >
                    <p class="text-xs text-slate-500">Diproses Oleh</p>
                    <p class="text-slate-700">
                        {{ adjustment.approver.name }}
                        <span v-if="adjustment.approved_at" class="text-xs text-slate-400">
                            ({{ formatDateTimeSimple(adjustment.approved_at) }})
                        </span>
                    </p>
                </div>
                <div v-if="adjustment.notes" class="col-span-2 border-t border-slate-200 pt-2 mt-1">
                    <p class="text-xs text-slate-500">Catatan</p>
                    <p class="whitespace-pre-line text-slate-700 text-xs">{{ adjustment.notes }}</p>
                </div>
            </div>

            <div>
                <h4 class="font-bold text-xs text-slate-700 mb-1.5">Daftar Barang Penyesuaian</h4>
                <div class="border border-slate-200 rounded-lg overflow-x-auto -mx-1 sm:mx-0">
                    <table class="w-full text-xs text-left min-w-[640px]">
                        <thead
                            class="bg-slate-50 border-b border-slate-200 text-slate-600 font-medium"
                        >
                            <tr>
                                <th class="p-2.5">Barang</th>
                                <th class="p-2.5 text-right">Perubahan Qty</th>
                                <th
                                    v-if="
                                        adjustment.status === $enums.AdjustmentStatus.Approved ||
                                        adjustment.status === $enums.AdjustmentStatus.Voided
                                    "
                                    class="p-2.5 text-right"
                                >
                                    Stok Sebelum
                                </th>
                                <th
                                    v-if="
                                        adjustment.status === $enums.AdjustmentStatus.Approved ||
                                        adjustment.status === $enums.AdjustmentStatus.Voided
                                    "
                                    class="p-2.5 text-right"
                                >
                                    Stok Sesudah
                                </th>
                                <th class="p-2.5">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="item in adjustment.items" :key="item.id">
                                <td class="p-2.5">
                                    <div
                                        class="font-semibold text-slate-800 break-words whitespace-normal leading-snug"
                                    >
                                        {{ item.inventory_item?.name }}
                                    </div>
                                    <div class="text-[10px] text-slate-400">
                                        SKU: {{ item.inventory_item?.sku || '-' }} | Satuan:
                                        {{ item.inventory_item?.uom?.name || '-' }}
                                    </div>
                                </td>
                                <td
                                    class="p-2.5 text-right font-bold"
                                    :class="
                                        item.qty_change > 0 ? 'text-emerald-600' : 'text-danger'
                                    "
                                >
                                    {{ item.qty_change > 0 ? '+' : ''
                                    }}{{ item.qty_change_formatted }}
                                </td>
                                <td
                                    v-if="
                                        adjustment.status === $enums.AdjustmentStatus.Approved ||
                                        adjustment.status === $enums.AdjustmentStatus.Voided
                                    "
                                    class="p-2.5 text-right text-slate-600"
                                >
                                    {{ item.stock_before_formatted }}
                                </td>
                                <td
                                    v-if="
                                        adjustment.status === $enums.AdjustmentStatus.Approved ||
                                        adjustment.status === $enums.AdjustmentStatus.Voided
                                    "
                                    class="p-2.5 text-right font-semibold text-slate-800"
                                >
                                    {{ item.stock_after_formatted }}
                                </td>
                                <td class="p-2.5 text-slate-600">{{ item.description || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <Teleport v-if="isMounted" to="#popUpFooter">
            <!-- Left action: Void button when Approved -->
            <button
                v-if="
                    adjustment && adjustment.status === $enums.AdjustmentStatus.Approved && canVoid
                "
                type="button"
                class="btn btn-flat text-danger mr-auto cursor-pointer"
                title="Batalkan (Void) penyesuaian stok ini"
                aria-label="Void"
                :disabled="isProcessing"
                @click="promptVoid"
            >
                <FontAwesomeIcon :icon="faBan" /> Void
            </button>

            <!-- Cetak Berita Acara -->
            <button
                v-if="adjustment && canExport"
                type="button"
                class="btn btn-outline-secondary cursor-pointer inline-flex items-center gap-1.5"
                title="Cetak Berita Acara (PDF)"
                aria-label="Cetak Berita Acara"
                @click="exportPdf"
            >
                <FontAwesomeIcon :icon="faFilePdf" class="text-danger" />
                <span>Cetak Berita Acara</span>
            </button>

            <!-- Close action -->
            <button
                type="button"
                class="btn btn-flat cursor-pointer"
                title="Tutup rincian dokumen"
                aria-label="Tutup"
                :disabled="isProcessing"
                @click="close"
            >
                Tutup
            </button>

            <!-- Right actions: Reject & Approve when Draft -->
            <template
                v-if="
                    adjustment && adjustment.status === $enums.AdjustmentStatus.Draft && canApprove
                "
            >
                <button
                    type="button"
                    class="btn btn-danger cursor-pointer"
                    title="Tolak pengajuan penyesuaian stok ini"
                    aria-label="Tolak"
                    :disabled="isProcessing"
                    @click="promptReject"
                >
                    <FontAwesomeIcon :icon="faTimes" /> Tolak
                </button>
                <button
                    type="button"
                    class="btn btn-main cursor-pointer"
                    title="Setujui dan proses transaksi ini"
                    aria-label="Setujui"
                    :disabled="isProcessing"
                    @click="confirmApprove"
                >
                    <FontAwesomeIcon :icon="faCheck" /> Setujui
                </button>
            </template>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { faCheck, faTimes, faBan, faFilePdf } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import DocumentProgressStepper from '@/Components/UI/DocumentProgressStepper.vue'
import ReasonPromptModal from '@/Components/Modals/ReasonPromptModal.vue'
import { formatDateTimeSimple } from '@/Composable/date.js'
import { useEnum } from '@/Composable/useEnum'
import { usePopUpStore } from '@/store/popup'
import { useModalStore } from '@/store/notification'

const page = usePage()
const popUpStore = usePopUpStore()
const modalStore = useModalStore()
const { getLabel, getColor } = useEnum()

const isMounted = ref(false)
onMounted(() => {
    isMounted.value = true
})

const props = defineProps({
    adjustment: {
        type: Object,
        default: null,
    },
    isLoading: {
        type: Boolean,
        default: false,
    },
})

const isProcessing = ref(false)

const can = permission => {
    return (
        page.props.auth?.permissions?.includes(permission) ||
        page.props.auth?.permissions?.includes('inventory.*') ||
        page.props.auth?.permissions?.includes('business.*') ||
        Boolean(page.props.auth?.user?.is_root_user)
    )
}

const canApprove = computed(
    () =>
        can('inventory.adjust.validate') ||
        can('inventory.adjust.update') ||
        can('inventory.adjustment.approve')
)
const canVoid = computed(() => can('inventory.adjust.delete') || can('inventory.adjustment.void'))
const canExport = computed(() => can('inventory.adjustment.export') || can('inventory.adjust.view'))

const exportPdf = () => {
    if (!props.adjustment?.id) return
    window.open(route('inventory.adjustments.export.pdf', props.adjustment.id), '_blank')
}

const close = () => {
    popUpStore.close()
}

const confirmApprove = () => {
    modalStore.open({
        title: 'Konfirmasi Persetujuan',
        message:
            'Apakah kamu yakin ingin menyetujui penyesuaian ini? Stok barang akan segera diperbarui.',
        confirmText: 'Ya, Setujui',
        confirmButtonClass: 'btn btn-main',
        onConfirm: () => {
            executeApprove()
        },
    })
}

const executeApprove = () => {
    isProcessing.value = true
    router.post(
        route('inventory.adjustments.approve', props.adjustment.id),
        {},
        {
            preserveScroll: true,
            onSuccess: page => {
                const flash = page.props.app?.flash || {}
                if (!flash.failed) {
                    close()
                }
            },
            onFinish: () => {
                isProcessing.value = false
            },
        }
    )
}

const promptReject = () => {
    modalStore.open({
        title: 'Tolak Penyesuaian Stok',
        component: ReasonPromptModal,
        showFooter: false,
        props: {
            description:
                'Penyesuaian stok yang ditolak tidak akan memotong atau menambah stok gudang.',
            label: 'Alasan Penolakan',
            placeholder: 'Tuliskan alasan mengapa penyesuaian ini ditolak...',
            confirmText: 'Tolak Penyesuaian',
            confirmButtonClass: 'btn btn-danger',
            onConfirm: async reason => {
                executeReject(reason)
            },
        },
    })
}

const executeReject = reason => {
    isProcessing.value = true
    router.post(
        route('inventory.adjustments.reject', props.adjustment.id),
        { notes: reason },
        {
            preserveScroll: true,
            onSuccess: page => {
                const flash = page.props.app?.flash || {}
                if (!flash.failed) {
                    close()
                }
            },
            onFinish: () => {
                isProcessing.value = false
            },
        }
    )
}

const promptVoid = () => {
    modalStore.open({
        title: 'Konfirmasi Pembatalan (Void)',
        component: ReasonPromptModal,
        showFooter: false,
        props: {
            description:
                'Membatalkan (Void) penyesuaian stok akan mengembalikan pergerakan stok ke kondisi awal.',
            label: 'Alasan Pembatalan (Void)',
            placeholder: 'Tulis alasan pembatalan dokumen...',
            confirmText: 'Batalkan Dokumen',
            confirmButtonClass: 'btn btn-danger',
            onConfirm: async reason => {
                executeVoid(reason)
            },
        },
    })
}

const executeVoid = reason => {
    isProcessing.value = true
    router.post(
        route('inventory.adjustments.void', props.adjustment.id),
        { reason },
        {
            preserveScroll: true,
            onSuccess: page => {
                const flash = page.props.app?.flash || {}
                if (!flash.failed) {
                    close()
                }
            },
            onFinish: () => {
                isProcessing.value = false
            },
        }
    )
}
</script>
