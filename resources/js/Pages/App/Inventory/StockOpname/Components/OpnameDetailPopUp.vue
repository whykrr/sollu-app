<template>
    <div>
        <div v-if="opname" class="space-y-3">
            <!-- Header Ringkasan Informasi -->
            <div
                class="grid grid-cols-2 gap-2 text-xs bg-slate-50/50 p-3 rounded-lg border border-slate-200"
            >
                <div>
                    <p class="text-slate-500">Nomor Opname</p>
                    <p class="font-bold text-slate-800 text-sm">{{ opname.opname_number }}</p>
                </div>
                <div>
                    <p class="text-slate-500">Status</p>
                    <span
                        class="badge"
                        :class="getColor('StockOpnameStatus', opname.status) || 'badge-gray'"
                    >
                        {{ getLabel('StockOpnameStatus', opname.status) }}
                    </span>
                </div>
                <div>
                    <p class="text-slate-500">Outlet</p>
                    <p class="font-semibold text-slate-700">{{ opname.outlet?.name || '-' }}</p>
                </div>
                <div>
                    <p class="text-slate-500">Tanggal Sesi</p>
                    <p class="text-slate-700">{{ formatDateTimeSimple(opname.created_at) }}</p>
                </div>
                <div>
                    <p class="text-slate-500">Dibuat Oleh</p>
                    <p class="text-slate-700">{{ opname.creator?.name || '-' }}</p>
                </div>
                <div>
                    <p class="text-slate-500">Disetujui / Ditolak Oleh</p>
                    <p class="text-slate-700">{{ opname.approver?.name || '-' }}</p>
                </div>
                <div v-if="opname.notes" class="col-span-2 border-t border-slate-200 pt-2 mt-1">
                    <p class="text-slate-500">Catatan Sesi</p>
                    <p class="whitespace-pre-line text-slate-700 mt-0.5">{{ opname.notes }}</p>
                </div>
            </div>

            <!-- Tabel Data Fisik Stok -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <h4 class="font-bold text-xs text-slate-700">Rincian Fisik Barang</h4>
                    <button
                        v-if="can('inventory.opname.export')"
                        type="button"
                        class="btn btn-outline-secondary btn-sm h-[30px] inline-flex items-center gap-1.5 cursor-pointer"
                        @click="exportPdf(opname.id)"
                    >
                        <FontAwesomeIcon :icon="faFilePdf" class="text-danger" />
                        <span>Ekspor PDF</span>
                    </button>
                </div>

                <div class="border border-slate-200 rounded-lg overflow-hidden">
                    <div class="max-h-80 overflow-y-auto">
                        <table class="w-full text-xs text-left">
                            <thead
                                class="bg-slate-50 border-b border-slate-200 text-slate-600 font-medium sticky top-0"
                            >
                                <tr>
                                    <th class="p-2.5 w-10 text-center">No</th>
                                    <th class="p-2.5">Barang</th>
                                    <th class="p-2.5 text-right">Stok Sistem</th>
                                    <th class="p-2.5 text-right">Stok Fisik</th>
                                    <th class="p-2.5 text-right">Selisih</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr
                                    v-for="(item, index) in opname.items"
                                    :key="item.id || index"
                                    :class="{
                                        'bg-red-50/40': Number(item.difference_qty) !== 0,
                                    }"
                                >
                                    <td class="p-2.5 text-center text-slate-400 font-bold">
                                        {{ index + 1 }}
                                    </td>
                                    <td class="p-2.5">
                                        <div class="font-semibold text-slate-800">
                                            {{ item.inventory_item?.name }}
                                        </div>
                                        <div class="text-[10px] text-slate-400">
                                            SKU: {{ item.inventory_item?.sku || '-' }} | Satuan:
                                            {{ item.inventory_item?.uom?.name || '-' }}
                                        </div>
                                    </td>
                                    <td class="p-2.5 text-right font-medium text-slate-600">
                                        {{ Number(item.system_qty ?? 0) }}
                                    </td>
                                    <td class="p-2.5 text-right font-semibold text-slate-800">
                                        {{ Number(item.actual_qty ?? 0) }}
                                    </td>
                                    <td
                                        class="p-2.5 text-right font-bold"
                                        :class="differenceColor(item.difference_qty)"
                                    >
                                        {{ formatDifference(item.difference_qty) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Ringkasan Footer -->
            <div
                class="grid grid-cols-2 sm:grid-cols-5 gap-2 p-3 bg-slate-50 border border-slate-200 rounded-lg text-xs"
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
                    <div class="font-bold text-danger text-sm mt-0.5">-{{ summary.shortage }}</div>
                </div>
            </div>

            <!-- Bagian Persetujuan / Penolakan jika PendingApproval -->
            <div
                v-if="opname.status === $enums.StockOpnameStatus.PendingApproval && canApprove"
                class="bg-amber-50 p-3 rounded-lg border border-amber-200 space-y-2"
            >
                <div>
                    <h4 class="font-bold text-xs text-amber-900">
                        Tindakan Verifikasi & Persetujuan
                    </h4>
                    <p class="text-xs text-amber-800 mt-0.5">
                        Menyetujui dokumen ini akan langsung menyesuaikan saldo fisik persediaan di
                        sistem.
                    </p>
                </div>

                <div v-if="showRejectInput" class="space-y-2">
                    <TextareaField
                        id="reject_notes"
                        v-model="rejectForm.notes"
                        label="Alasan Penolakan"
                        placeholder="Tulis alasan mengapa opname ini ditolak..."
                        :class="{ 'is-invalid': rejectForm.errors.notes }"
                        :error="rejectForm.errors.notes"
                        rows="2"
                        required
                    />
                </div>

                <div class="flex items-center gap-2">
                    <template v-if="!showRejectInput">
                        <button
                            type="button"
                            class="btn btn-main btn-sm h-[30px]"
                            :disabled="isProcessing"
                            @click="confirmApprove"
                        >
                            <FontAwesomeIcon :icon="faCheck" /> Setujui & Sesuaikan Stok
                        </button>
                        <button
                            type="button"
                            class="btn btn-danger btn-sm h-[30px]"
                            :disabled="isProcessing"
                            @click="showRejectInput = true"
                        >
                            <FontAwesomeIcon :icon="faTimes" /> Tolak
                        </button>
                    </template>
                    <template v-else>
                        <button
                            type="button"
                            class="btn btn-danger btn-sm h-[30px]"
                            :disabled="rejectForm.processing"
                            @click="executeReject"
                        >
                            Konfirmasi Tolak
                        </button>
                        <button
                            type="button"
                            class="btn btn-flat btn-sm h-[30px]"
                            :disabled="rejectForm.processing"
                            @click="showRejectInput = false"
                        >
                            Batal
                        </button>
                    </template>
                </div>
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
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCheck, faTimes, faFilePdf } from '@fortawesome/free-solid-svg-icons'
import TextareaField from '@/Components/Form/TextareaField.vue'
import { formatDateTimeSimple } from '@/Composable/date'
import { useEnum } from '@/Composable/useEnum'
import { usePopUpStore } from '@/store/popup'
import { useModalStore } from '@/store/notification'

const props = defineProps({
    opname: {
        type: Object,
        default: null,
    },
})

const page = usePage()
const emit = defineEmits(['close'])
const popUpStore = usePopUpStore()
const modalStore = useModalStore()
const { getLabel, getColor } = useEnum()

const isMounted = ref(false)
const isProcessing = ref(false)
const showRejectInput = ref(false)

onMounted(() => {
    isMounted.value = true
})

const rejectForm = useForm({
    notes: '',
})

const can = permission => {
    return (
        page.props.auth?.permissions?.includes(permission) ||
        page.props.auth?.permissions?.includes('inventory.*')
    )
}

const canApprove = computed(() => can('inventory.opname.approve'))

const summary = computed(() => {
    let totalItems = 0
    let matched = 0
    let diffCount = 0
    let surplus = 0
    let shortage = 0

    if (props.opname && props.opname.items) {
        totalItems = props.opname.items.length
        props.opname.items.forEach(item => {
            const diff = Number(item.difference_qty ?? 0)
            if (diff === 0) {
                matched++
            } else {
                diffCount++
                if (diff > 0) surplus += diff
                if (diff < 0) shortage += Math.abs(diff)
            }
        })
    }

    return {
        totalItems,
        matched,
        diff: diffCount,
        surplus: Number(surplus.toFixed(2)),
        shortage: Number(shortage.toFixed(2)),
    }
})

const differenceColor = diff => {
    const val = Number(diff ?? 0)
    if (val > 0) return 'text-emerald-600'
    if (val < 0) return 'text-danger'
    return 'text-slate-400'
}

const formatDifference = diff => {
    const val = Number(diff ?? 0)
    const formatted = Math.abs(val)
    if (val > 0) return '+' + formatted
    if (val < 0) return '-' + formatted
    return '0'
}

const exportPdf = id => {
    window.open(route('inventory.opnames.export.pdf', id), '_blank')
}

const close = () => {
    showRejectInput.value = false
    rejectForm.reset()
    popUpStore.close()
    emit('close')
}

const confirmApprove = () => {
    modalStore.open({
        title: 'Konfirmasi Persetujuan Stok Opname',
        message:
            'Saldo stok persediaan di sistem akan disesuaikan dengan hasil penghitungan fisik ini. Apakah kamu yakin ingin menyetujuinya?',
        confirmText: 'Ya, Setujui',
        confirmButtonClass: 'btn btn-main',
        onConfirm: () => executeApprove(),
    })
}

const executeApprove = () => {
    if (!props.opname) return
    isProcessing.value = true

    const payload = {
        notes: props.opname.notes || '',
        items: (props.opname.items || []).map(i => ({
            inventory_item_id: i.inventory_item_id,
            system_qty: Number(i.system_qty ?? 0),
            actual_qty: Number(i.actual_qty ?? i.system_qty ?? 0),
        })),
    }

    router.post(route('inventory.opnames.approve', props.opname.id), payload, {
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
    })
}

const executeReject = () => {
    if (!props.opname) return
    rejectForm.post(route('inventory.opnames.reject', props.opname.id), {
        preserveScroll: true,
        onSuccess: page => {
            const flash = page.props.app?.flash || {}
            if (!flash.failed) {
                close()
            }
        },
    })
}
</script>
