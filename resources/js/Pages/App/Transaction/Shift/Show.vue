<template>
    <MainPage>
        <template #header>
            <div class="flex items-center gap-3">
                <button class="btn btn-flat btn-sm" title="Kembali ke Daftar Shift" @click="goBack">
                    <FontAwesomeIcon :icon="faArrowLeft" />
                </button>
                <MainPageHeader
                    title="Rincian Shift Kasir"
                    :description="
                        shift.shift_number
                            ? `Nomor Shift: ${shift.shift_number}`
                            : 'Informasi detail operasional shift kasir'
                    "
                />
            </div>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
            <!-- Left Column: Shift Info & Cash Log -->
            <div class="col-span-1 lg:col-span-2 space-y-3">
                <!-- Shift Info -->
                <div class="bg-white rounded-lg border border-slate-200 p-4">
                    <h3 class="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-3">
                        Informasi Shift
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
                        <div>
                            <span class="text-xs text-slate-500 block mb-0.5">Kasir</span>
                            <span class="font-medium text-slate-800">{{
                                shift.user?.name || '-'
                            }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-slate-500 block mb-0.5">Outlet</span>
                            <span class="font-medium text-slate-800">{{
                                shift.outlet?.name || '-'
                            }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-slate-500 block mb-0.5">Status</span>
                            <span
                                class="badge"
                                :class="
                                    $enums.ShiftStatus._meta[shift.status]?.color || 'badge-gray'
                                "
                            >
                                {{ $enums.ShiftStatus._meta[shift.status]?.label || shift.status }}
                            </span>
                        </div>
                        <div>
                            <span class="text-xs text-slate-500 block mb-0.5">Waktu Buka</span>
                            <span class="font-medium text-slate-800">
                                {{ formatDateTimeSimple(shift.created_at) }}
                            </span>
                        </div>
                        <div>
                            <span class="text-xs text-slate-500 block mb-0.5">Waktu Tutup</span>
                            <span class="font-medium text-slate-800">
                                {{ shift.closed_at ? formatDateTimeSimple(shift.closed_at) : '-' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Cash Logs (Pergerakan Kas) -->
                <div class="bg-white rounded-lg border border-slate-200 p-4">
                    <h3 class="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-3">
                        Riwayat Pergerakan Kas (Cash In / Out)
                    </h3>
                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200">
                                    <th class="py-2.5 px-3 text-xs font-semibold text-slate-600">
                                        Waktu
                                    </th>
                                    <th class="py-2.5 px-3 text-xs font-semibold text-slate-600">
                                        Tipe
                                    </th>
                                    <th class="py-2.5 px-3 text-xs font-semibold text-slate-600">
                                        Keterangan
                                    </th>
                                    <th
                                        class="py-2.5 px-3 text-xs font-semibold text-slate-600 text-right"
                                    >
                                        Nominal
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="log in shift.cashLogs || shift.cash_logs" :key="log.id">
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
                                            {{
                                                $enums.ShiftCashLogType._meta[log.type]?.label ||
                                                log.type
                                            }}
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
                                <tr v-if="!(shift.cashLogs?.length || shift.cash_logs?.length)">
                                    <td colspan="4" class="py-6 text-center text-xs text-slate-400">
                                        Belum ada catatan pergerakan kas.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Column: X/Z Report Summary -->
            <div class="col-span-1 space-y-3">
                <!-- Expected Cash vs Actual -->
                <div class="bg-white rounded-lg border border-slate-200 p-4">
                    <h3 class="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-3">
                        Ringkasan Kas
                    </h3>

                    <div
                        class="space-y-2 text-sm bg-slate-50 p-3 rounded-lg border border-slate-200"
                    >
                        <div class="flex justify-between">
                            <span class="text-slate-500">Saldo Awal Buka Shift</span>
                            <span class="font-medium">{{
                                formatCurrency(shift.opening_cash)
                            }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Total Penjualan</span>
                            <span class="font-medium">{{ formatCurrency(shift.total_sales) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Total Kas Masuk (Cash In)</span>
                            <span class="font-medium text-success"
                                >+{{ formatCurrency(totalCashIn) }}</span
                            >
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Total Kas Keluar (Cash Out)</span>
                            <span class="font-medium text-danger"
                                >-{{ formatCurrency(totalCashOut) }}</span
                            >
                        </div>

                        <div
                            class="pt-2 mt-2 border-t border-slate-200 flex justify-between font-semibold"
                        >
                            <span class="text-slate-700">Kas Harapan (Expected)</span>
                            <span class="text-slate-900">{{
                                formatCurrency(shift.expected_cash)
                            }}</span>
                        </div>

                        <template v-if="shift.status === $enums.ShiftStatus.Closed">
                            <div class="flex justify-between font-semibold pt-1">
                                <span class="text-slate-700">Kas Aktual (Closing)</span>
                                <span class="text-slate-900">{{
                                    formatCurrency(shift.closing_cash)
                                }}</span>
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
                                Shift masih aktif. Saldo aktual belum diinput oleh kasir.
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </MainPage>
</template>

<script setup>
import { computed } from 'vue'
import { faArrowLeft } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { router } from '@inertiajs/vue3'
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import { formatDateTimeSimple } from '@/Composable/date.js'
import { formatIDR as formatCurrency } from '@/Composable/currency-format.js'
import { useEnum } from '@/Composable/useEnum'

const props = defineProps({
    shift: {
        type: Object,
        required: true,
    },
})

const { enums } = useEnum()

const totalCashIn = computed(() => {
    const logs = props.shift.cashLogs || props.shift.cash_logs || []
    return logs
        .filter(log => log.type === enums.ShiftCashLogType?.CashIn || log.type === 'cash_in')
        .reduce((sum, log) => sum + Number(log.amount || 0), 0)
})

const totalCashOut = computed(() => {
    const logs = props.shift.cashLogs || props.shift.cash_logs || []
    return logs
        .filter(log => log.type === enums.ShiftCashLogType?.CashOut || log.type === 'cash_out')
        .reduce((sum, log) => sum + Number(log.amount || 0), 0)
})

const discrepancyAmount = computed(() => {
    return Number(props.shift.closing_cash || 0) - Number(props.shift.expected_cash || 0)
})

const discrepancyClass = computed(() => {
    const diff = discrepancyAmount.value
    if (diff === 0) return 'bg-emerald-50 text-emerald-800 border-emerald-200'
    if (diff > 0) return 'bg-amber-50 text-amber-800 border-amber-200'
    return 'bg-rose-50 text-rose-800 border-rose-200'
})

const goBack = () => {
    router.visit(route('transactions.shifts.index'))
}
</script>
