<template>
    <div v-if="shift" class="space-y-4 pb-20">
        <!-- Status & Header Info -->
        <div
            class="flex justify-between items-start bg-slate-50 p-4 rounded-lg border border-slate-200"
        >
            <div>
                <h2 class="font-bold text-lg text-slate-800">
                    {{ shift.shift_number || 'Shift Kasir' }}
                </h2>
                <div class="text-xs text-slate-500 mt-0.5">
                    Buka: {{ formatDateTimeSimple(shift.created_at) }}
                </div>
                <div v-if="shift.closed_at" class="text-xs text-slate-500">
                    Tutup: {{ formatDateTimeSimple(shift.closed_at) }}
                </div>
            </div>
            <div class="flex flex-col items-end gap-1">
                <span
                    class="badge"
                    :class="$enums.ShiftStatus._meta[shift.status]?.color || 'badge-gray'"
                >
                    {{ $enums.ShiftStatus._meta[shift.status]?.label || shift.status }}
                </span>
            </div>
        </div>

        <!-- Cashier & Outlet Info -->
        <div class="grid grid-cols-2 gap-4 text-sm bg-white p-4 rounded-lg border border-slate-200">
            <div>
                <span class="text-xs text-slate-500 block mb-0.5">Kasir</span>
                <span class="font-medium text-slate-800">{{ shift.user?.name || '-' }}</span>
                <span v-if="shift.user?.email" class="text-xs text-slate-500 block">
                    {{ shift.user.email }}
                </span>
            </div>
            <div>
                <span class="text-xs text-slate-500 block mb-0.5">Outlet</span>
                <span class="font-medium text-slate-800">{{ shift.outlet?.name || '-' }}</span>
            </div>
        </div>

        <!-- Ringkasan Kas (X/Z Report Summary) -->
        <div class="space-y-2 text-sm bg-slate-50 p-4 rounded-lg border border-slate-200">
            <h3 class="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">
                Ringkasan Kas
            </h3>

            <div class="flex justify-between">
                <span class="text-slate-500">Saldo Awal Buka Shift</span>
                <span class="font-medium">{{ formatCurrency(shift.opening_cash) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Total Penjualan</span>
                <span class="font-medium">{{ formatCurrency(shift.total_sales) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Total Kas Masuk (Cash In)</span>
                <span class="font-medium text-success">+{{ formatCurrency(totalCashIn) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Total Kas Keluar (Cash Out)</span>
                <span class="font-medium text-danger">-{{ formatCurrency(totalCashOut) }}</span>
            </div>

            <div class="pt-2 mt-2 border-t border-slate-200 flex justify-between font-semibold">
                <span class="text-slate-700">Kas Harapan (Expected)</span>
                <span class="text-slate-900">{{ formatCurrency(shift.expected_cash) }}</span>
            </div>

            <template v-if="shift.status === $enums.ShiftStatus.Closed">
                <div class="flex justify-between font-semibold pt-1">
                    <span class="text-slate-700">Kas Aktual (Closing)</span>
                    <span class="text-slate-900">{{ formatCurrency(shift.closing_cash) }}</span>
                </div>

                <div class="p-3 mt-3 rounded-lg border" :class="discrepancyClass">
                    <div class="flex justify-between font-bold text-sm">
                        <span>Selisih (Discrepancy)</span>
                        <span>{{ formatCurrency(discrepancyAmount) }}</span>
                    </div>
                    <div v-if="discrepancyAmount > 0" class="text-xs mt-1">
                        Uang fisik lebih besar dari sistem (Surplus).
                    </div>
                    <div v-else-if="discrepancyAmount < 0" class="text-xs mt-1">
                        Uang fisik lebih sedikit dari sistem (Minus / Defisit).
                    </div>
                    <div v-else class="text-xs mt-1">Saldo klop / seimbang.</div>
                </div>
            </template>
            <template v-else>
                <div
                    class="p-3 mt-3 bg-blue-50 text-blue-700 rounded-lg text-center text-xs border border-blue-200"
                >
                    Shift masih aktif. Saldo aktual kas fisik belum diinput oleh kasir.
                </div>
            </template>
        </div>

        <!-- Riwayat Pergerakan Kas (Cash Logs) -->
        <div class="space-y-3">
            <h3 class="text-xs font-semibold text-slate-700 uppercase tracking-wider">
                Riwayat Pergerakan Kas (Cash In / Out)
            </h3>
            <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="py-2.5 px-3 text-xs font-semibold text-slate-600">Waktu</th>
                            <th class="py-2.5 px-3 text-xs font-semibold text-slate-600">Tipe</th>
                            <th class="py-2.5 px-3 text-xs font-semibold text-slate-600">
                                Keterangan
                            </th>
                            <th class="py-2.5 px-3 text-xs font-semibold text-slate-600 text-right">
                                Nominal
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="log in shift.cash_logs || shift.cashLogs" :key="log.id">
                            <td class="py-2 px-3 text-xs text-slate-600">
                                {{ formatDateTimeSimple(log.created_at) }}
                            </td>
                            <td class="py-2 px-3">
                                <span
                                    class="badge"
                                    :class="
                                        $enums.ShiftCashLogType._meta[log.type]?.color ||
                                        'badge-gray'
                                    "
                                >
                                    {{ $enums.ShiftCashLogType._meta[log.type]?.label || log.type }}
                                </span>
                            </td>
                            <td class="py-2 px-3 text-xs text-slate-600">
                                {{ log.description || log.note || '-' }}
                            </td>
                            <td class="py-2 px-3 text-right font-medium text-xs">
                                <span
                                    v-if="log.type === $enums.ShiftCashLogType.CashOut"
                                    class="text-danger"
                                    >-</span
                                >
                                <span v-else class="text-success">+</span>
                                {{ formatCurrency(log.amount) }}
                            </td>
                        </tr>
                        <tr v-if="!(shift.cash_logs?.length || shift.cashLogs?.length)">
                            <td colspan="4" class="py-6 text-center text-xs text-slate-400">
                                Belum ada catatan pergerakan kas.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Teleport Actions to Drawer Footer -->
        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex items-center justify-end w-full">
                <button type="button" class="btn btn-flat btn-sm" @click="popUpStore.close()">
                    Tutup
                </button>
            </div>
        </Teleport>
    </div>

    <!-- Loading Skeleton -->
    <div v-else class="flex justify-center items-center h-64">
        <div class="animate-pulse flex flex-col items-center">
            <div class="h-8 w-8 bg-slate-200 rounded-full mb-4"></div>
            <div class="h-4 w-32 bg-slate-200 rounded"></div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { usePopUpStore } from '@/store/popup'
import { useModalStore } from '@/store/notification'
import { formatDateTimeSimple } from '@/Composable/date'
import { formatIDR as formatCurrency } from '@/Composable/currency-format'
import { useEnum } from '@/Composable/useEnum'

const props = defineProps({
    shiftId: {
        type: String,
        required: true,
    },
})

const popUpStore = usePopUpStore()
const modalStore = useModalStore()
const { enums } = useEnum()

const isMounted = ref(false)
const shift = ref(null)

const fetchDetail = async () => {
    try {
        const response = await axios.get(route('transactions.shifts.show', props.shiftId), {
            headers: { Accept: 'application/json' },
        })
        shift.value = response.data.data || response.data.shift || response.data
    } catch (_error) {
        modalStore.open({
            type: 'error',
            title: 'Gagal Memuat',
            message: 'Terjadi kesalahan saat memuat rincian shift.',
        })
        popUpStore.close()
    }
}

const totalCashIn = computed(() => {
    const logs = shift.value?.cash_logs || shift.value?.cashLogs || []
    return logs
        .filter(log => log.type === enums.ShiftCashLogType?.CashIn || log.type === 'cash_in')
        .reduce((sum, log) => sum + Number(log.amount || 0), 0)
})

const totalCashOut = computed(() => {
    const logs = shift.value?.cash_logs || shift.value?.cashLogs || []
    return logs
        .filter(log => log.type === enums.ShiftCashLogType?.CashOut || log.type === 'cash_out')
        .reduce((sum, log) => sum + Number(log.amount || 0), 0)
})

const discrepancyAmount = computed(() => {
    if (!shift.value) return 0
    return Number(shift.value.closing_cash || 0) - Number(shift.value.expected_cash || 0)
})

const discrepancyClass = computed(() => {
    const diff = discrepancyAmount.value
    if (diff === 0) return 'bg-emerald-50 text-emerald-800 border-emerald-200'
    if (diff > 0) return 'bg-amber-50 text-amber-800 border-amber-200'
    return 'bg-rose-50 text-rose-800 border-rose-200'
})

onMounted(() => {
    isMounted.value = true
    fetchDetail()
})
</script>
