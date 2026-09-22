<template>
    <div class="space-y-3">
        <!-- Transfer Header Summary -->
        <div
            v-if="transferData"
            class="grid grid-cols-1 sm:grid-cols-3 gap-2 bg-slate-50 p-3 rounded-lg border border-slate-200 text-xs"
        >
            <div>
                <span class="text-slate-500 font-medium">No. Dokumen Transfer</span>
                <p class="font-bold text-slate-800 mt-0.5">{{ transferData.transfer_number }}</p>
            </div>
            <div>
                <span class="text-slate-500 font-medium">Dari Outlet (Pengirim)</span>
                <p class="font-semibold text-slate-800 mt-0.5">
                    {{ transferData.from_outlet?.name || transferData.fromOutlet?.name || '-' }}
                </p>
            </div>
            <div>
                <span class="text-slate-500 font-medium">Ke Outlet (Tujuan Anda)</span>
                <p class="font-semibold text-slate-800 mt-0.5">
                    {{ transferData.to_outlet?.name || transferData.toOutlet?.name || '-' }}
                </p>
            </div>
        </div>

        <!-- Summary KPI Widgets -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
            <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50 text-center">
                <span class="text-[11px] text-slate-500 font-medium block">Total Jenis Item</span>
                <span class="text-base font-bold text-slate-800">{{ form.items.length }}</span>
            </div>
            <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50 text-center">
                <span class="text-[11px] text-slate-500 font-medium block">Total Qty Dikirim</span>
                <span class="text-base font-bold text-slate-800">{{ totalQtySentFormatted }}</span>
            </div>
            <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50 text-center">
                <span class="text-[11px] text-slate-500 font-medium block">Total Qty Diterima</span>
                <span class="text-base font-bold text-emerald-600">{{
                    totalQtyReceivedFormatted
                }}</span>
            </div>
            <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50 text-center">
                <span class="text-[11px] text-slate-500 font-medium block">Total Selisih</span>
                <span
                    class="text-base font-bold"
                    :class="totalDiscrepancy > 0 ? 'text-rose-600' : 'text-slate-400'"
                >
                    {{ totalDiscrepancyFormatted }}
                </span>
            </div>
        </div>

        <!-- Discrepancy Warning Banner -->
        <div
            v-if="totalDiscrepancy > 0"
            class="p-2.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-center justify-between"
        >
            <span>
                ⚠️ <strong>Perhatian:</strong> Terdapat selisih {{ totalDiscrepancyFormatted }} item
                antara barang yang dikirim dengan yang Anda terima fisik.
            </span>
            <button
                type="button"
                class="text-xs text-amber-700 underline font-semibold hover:text-amber-900"
                @click="receiveAll"
            >
                Terima Semua Penuh
            </button>
        </div>

        <!-- Receive Form Items Table -->
        <div class="space-y-1.5">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                    Verifikasi Fisik Barang Diterima
                </h3>
                <span class="text-[11px] text-slate-500">
                    Sesuaikan jumlah fisik barang yang benar-benar diterima di outlet.
                </span>
            </div>

            <div
                v-if="form.items.length === 0"
                class="text-center py-8 text-slate-400 border border-slate-200 rounded-lg text-xs"
            >
                Tidak ada item transfer untuk diverifikasi.
            </div>

            <div v-else class="border border-slate-200 rounded-lg overflow-hidden">
                <table class="w-full text-xs text-left">
                    <thead
                        class="bg-slate-100 text-slate-600 font-medium border-b border-slate-200"
                    >
                        <tr>
                            <th class="py-2 px-3 w-10 text-center">No</th>
                            <th class="py-2 px-3">Item / Bahan</th>
                            <th class="py-2 px-3 w-20 text-center">Satuan</th>
                            <th class="py-2 px-3 w-24 text-right">Qty Dikirim</th>
                            <th class="py-2 px-3 w-36 text-center">Qty Diterima Fisik</th>
                            <th class="py-2 px-3 w-24 text-right">Selisih</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <tr
                            v-for="(item, idx) in form.items"
                            :key="item.id"
                            class="hover:bg-slate-50 transition-colors"
                        >
                            <td class="py-2 px-3 text-center text-slate-400">{{ idx + 1 }}</td>
                            <td class="py-2 px-3">
                                <div class="font-semibold text-slate-800">{{ item.name }}</div>
                                <div v-if="item.sku" class="text-[11px] text-slate-400">
                                    SKU: {{ item.sku }}
                                </div>
                            </td>
                            <td class="py-2 px-3 text-center text-slate-600">
                                {{ item.uom_name || '-' }}
                            </td>
                            <td class="py-2 px-3 text-right font-medium text-slate-700">
                                {{ item.qty_sent_formatted }}
                            </td>
                            <td class="py-1.5 px-3">
                                <NumberField
                                    v-model="item.qty_received"
                                    :min="0"
                                    :max="item.qty_sent"
                                    step="0.01"
                                    size="sm"
                                    :error="form.errors[`items.${idx}.qty_received`]"
                                />
                            </td>
                            <td class="py-2 px-3 text-right">
                                <span
                                    class="font-semibold"
                                    :class="
                                        item.qty_sent - item.qty_received > 0
                                            ? 'text-rose-600'
                                            : 'text-slate-500'
                                    "
                                >
                                    {{ formatNumber(item.qty_sent - item.qty_received) }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Teleport Drawer Footer -->
        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex justify-between w-full">
                <button
                    type="button"
                    class="btn btn-flat btn-sm"
                    :disabled="form.processing"
                    @click="handleCancel"
                >
                    Batal
                </button>
                <button
                    type="button"
                    class="btn btn-main btn-sm"
                    :disabled="form.processing || form.items.length === 0"
                    @click="confirmSubmit"
                >
                    Konfirmasi Penerimaan Stok
                </button>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useFormDirtyGuard } from '@/Composable/useFormDirtyGuard'
import { useModalStore } from '@/store/notification'
import NumberField from '@/Components/Form/NumberField.vue'

const props = defineProps({
    transferData: Object,
})

const emit = defineEmits(['refresh'])
const isMounted = ref(false)
const modalStore = useModalStore()

const form = useForm({
    items: [],
})

const { handleCancel, forceClose } = useFormDirtyGuard({ form })

onMounted(() => {
    isMounted.value = true
    if (props.transferData?.items) {
        form.items = props.transferData.items.map(i => ({
            id: i.id,
            name: i.inventory_item?.name || i.inventoryItem?.name || 'Unknown',
            sku: i.inventory_item?.sku || i.inventoryItem?.sku || '',
            uom_name: i.inventory_item?.uom?.name || i.inventoryItem?.uom?.name || '',
            qty_sent: Number(i.qty || 0),
            qty_sent_formatted: i.qty_formatted,
            qty_received: Number(i.qty || 0), // initialize with actual numeric sent qty
        }))
    } else {
        form.items = []
    }
})

const totalQtySent = computed(() => {
    return form.items.reduce((acc, item) => acc + Number(item.qty_sent || 0), 0)
})

const totalQtyReceived = computed(() => {
    return form.items.reduce((acc, item) => acc + Number(item.qty_received || 0), 0)
})

const totalDiscrepancy = computed(() => {
    return form.items.reduce((acc, item) => {
        const diff = Number(item.qty_sent || 0) - Number(item.qty_received || 0)
        return acc + Math.max(0, diff)
    }, 0)
})

const totalQtySentFormatted = computed(() => formatNumber(totalQtySent.value))
const totalQtyReceivedFormatted = computed(() => formatNumber(totalQtyReceived.value))
const totalDiscrepancyFormatted = computed(() => formatNumber(totalDiscrepancy.value))

const receiveAll = () => {
    form.items.forEach(item => {
        item.qty_received = item.qty_sent
    })
}

const confirmSubmit = () => {
    if (!props.transferData?.id) return

    const hasDiscrepancy = totalDiscrepancy.value > 0
    modalStore.open({
        title: 'Konfirmasi Penerimaan Stok',
        message: hasDiscrepancy
            ? `Terdapat selisih sebanyak ${totalDiscrepancyFormatted.value} item dengan yang dikirimkan. Stok di outlet Anda hanya akan bertambah sesuai jumlah yang diterima. Lanjutkan?`
            : 'Pastikan seluruh barang fisik yang diterima di outlet sudah sesuai dengan rincian formulir.',
        confirmText: 'Ya, Konfirmasi Penerimaan',
        confirmButtonClass: 'btn btn-main',
        onConfirm: () => {
            submit()
        },
    })
}

const submit = () => {
    if (props.transferData?.id) {
        form.post(route('inventory.transfers.receive', props.transferData.id), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                forceClose()
                emit('refresh')
            },
        })
    }
}

const formatNumber = num => {
    return Number(num || 0).toLocaleString('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })
}
</script>
