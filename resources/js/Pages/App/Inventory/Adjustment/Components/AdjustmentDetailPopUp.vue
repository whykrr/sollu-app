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
            <div class="grid grid-cols-2 gap-2 text-sm bg-slate-50/50 p-3 rounded-lg border border-slate-200">
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
                <div v-if="adjustment.approver" class="col-span-2 border-t border-slate-200 pt-2 mt-1">
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
                <div class="border border-slate-200 rounded-lg overflow-hidden">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-medium">
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
                            <tr
                                v-for="item in adjustment.items"
                                :key="item.id"
                            >
                                <td class="p-2.5">
                                    <div class="font-semibold text-slate-800">
                                        {{ item.inventory_item?.name }}
                                    </div>
                                    <div class="text-[10px] text-slate-400">
                                        SKU: {{ item.inventory_item?.sku || '-' }} | Satuan: {{ item.inventory_item?.uom?.name || '-' }}
                                    </div>
                                </td>
                                <td
                                    class="p-2.5 text-right font-bold"
                                    :class="item.qty_change > 0 ? 'text-emerald-600' : 'text-danger'"
                                >
                                    {{ item.qty_change > 0 ? '+' : '' }}{{ item.qty_change_formatted }}
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

            <!-- Approval Section -->
            <div
                v-if="adjustment.status === $enums.AdjustmentStatus.Draft && canApprove"
                class="bg-amber-50 p-3 rounded-lg border border-amber-200 mt-2 space-y-2"
            >
                <div>
                    <h4 class="font-bold text-xs text-amber-900">Tindakan Persetujuan</h4>
                    <p class="text-xs text-amber-800 mt-0.5">
                        Pastikan seluruh data penyesuaian sudah sesuai kondisi fisik barang sebelum menyetujui.
                    </p>
                </div>

                <div v-if="showRejectInput" class="space-y-2">
                    <TextareaField
                        id="reject_notes"
                        v-model="rejectForm.notes"
                        label="Alasan Penolakan"
                        placeholder="Tulis alasan mengapa penyesuaian ini ditolak..."
                        :class="{ 'is-invalid': rejectForm.errors.notes }"
                        :error="rejectForm.errors.notes"
                        rows="2"
                        required
                    />
                </div>

                <div class="flex gap-2">
                    <template v-if="!showRejectInput">
                        <button class="btn btn-main btn-sm" :disabled="isProcessing" @click="confirmApprove">
                            <FontAwesomeIcon :icon="faCheck" /> Setujui Penyesuaian
                        </button>
                        <button
                            class="btn btn-danger btn-sm"
                            :disabled="isProcessing"
                            @click="showRejectInput = true"
                        >
                            <FontAwesomeIcon :icon="faTimes" /> Tolak
                        </button>
                    </template>
                    <template v-else>
                        <button
                            class="btn btn-danger btn-sm"
                            :disabled="rejectForm.processing"
                            @click="reject"
                        >
                            Konfirmasi Tolak
                        </button>
                        <button
                            class="btn btn-flat btn-sm"
                            :disabled="rejectForm.processing"
                            @click="showRejectInput = false"
                        >
                            Batal
                        </button>
                    </template>
                </div>
            </div>

            <div
                v-if="adjustment.status === $enums.AdjustmentStatus.Approved && canVoid"
                class="mt-2 flex justify-end"
            >
                <button
                    class="btn btn-flat btn-sm text-danger cursor-pointer"
                    :disabled="isProcessing"
                    @click="confirmVoid"
                >
                    <FontAwesomeIcon :icon="faBan" /> Batalkan Penyesuaian (Void)
                </button>
            </div>
        </div>

        <Teleport v-if="isMounted" to="#popUpFooter">
            <button type="button" class="btn btn-flat" :disabled="isProcessing" @click="close">
                Tutup
            </button>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useForm, router, usePage } from '@inertiajs/vue3'
import { faCheck, faTimes, faBan } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import TextareaField from '@/Components/Form/TextareaField.vue'
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
const showRejectInput = ref(false)

const rejectForm = useForm({
    notes: '',
})

const can = permission => {
    return (
        page.props.auth?.permissions?.includes(permission) ||
        page.props.auth?.permissions?.includes('inventory.*')
    )
}

const canApprove = computed(() => can('inventory.adjustment.approve'))
const canVoid = computed(() => can('inventory.adjustment.void'))

const close = () => {
    showRejectInput.value = false
    rejectForm.reset()
    popUpStore.close()
}

const confirmApprove = () => {
    modalStore.open({
        title: 'Konfirmasi Persetujuan',
        message: 'Apakah kamu yakin ingin menyetujui penyesuaian ini? Stok barang akan segera diperbarui.',
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

const reject = () => {
    rejectForm.post(route('inventory.adjustments.reject', props.adjustment.id), {
        preserveScroll: true,
        onSuccess: page => {
            const flash = page.props.app?.flash || {}
            if (!flash.failed) {
                close()
            }
        },
    })
}

const confirmVoid = () => {
    modalStore.open({
        title: 'Konfirmasi Pembatalan (Void)',
        message:
            'Apakah kamu yakin ingin membatalkan (VOID) dokumen penyesuaian ini? Pergerakan stok akan dikembalikan.',
        confirmText: 'Ya, Batalkan Dokumen',
        confirmButtonClass: 'btn btn-danger',
        onConfirm: () => {
            executeVoid()
        },
    })
}

const executeVoid = () => {
    isProcessing.value = true
    router.post(
        route('inventory.adjustments.void', props.adjustment.id),
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
</script>
