<template>
    <Container>
        <template #header>
            <div class="flex items-center gap-4">
                <button class="btn btn-flat" @click="goBack" title="Kembali">
                    <FontAwesomeIcon :icon="faArrowLeft" />
                </button>
                <ContainerHeader title="Rincian Shift Kasir" />
            </div>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 p-4">
            <!-- Left Column: Shift Info & Cash Log -->
            <div class="col-span-1 lg:col-span-2 space-y-6">
                <!-- Shift Info -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold mb-4">Informasi Shift</h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <div>
                            <span class="text-sm text-gray-500 block">Kasir</span>
                            <span class="font-medium">{{ shift.user?.name || '-' }}</span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500 block">Outlet</span>
                            <span class="font-medium">{{ shift.outlet?.name || '-' }}</span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500 block">Status</span>
                            <span class="font-medium uppercase" :class="shift.status === 'open' ? 'text-success' : 'text-gray-700'">{{ shift.status }}</span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500 block">Waktu Buka</span>
                            <span class="font-medium">{{ formatDateTimeSimple(shift.created_at) }}</span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500 block">Waktu Tutup</span>
                            <span class="font-medium">{{ shift.closed_at ? formatDateTimeSimple(shift.closed_at) : '-' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Cash Logs (Pergerakan Kas) -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold mb-4">Riwayat Pergerakan Kas (Cash In / Out)</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="p-3 text-sm font-semibold text-gray-600">Waktu</th>
                                    <th class="p-3 text-sm font-semibold text-gray-600">Tipe</th>
                                    <th class="p-3 text-sm font-semibold text-gray-600 text-right">Nominal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="log in shift.cashLogs" :key="log.id" class="border-b border-gray-100 last:border-0">
                                    <td class="p-3">{{ formatDateTimeSimple(log.created_at) }}</td>
                                    <td class="p-3">
                                        <span
                                            class="badge"
                                            :class="{
                                                'badge-success': log.type === 'cash_in',
                                                'badge-danger': log.type === 'cash_out',
                                            }"
                                        >
                                            {{ log.type === 'cash_in' ? 'Cash In' : 'Cash Out' }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-right font-medium">
                                        <span v-if="log.type === 'cash_out'" class="text-danger">-</span>
                                        {{ formatCurrency(log.amount) }}
                                    </td>
                                </tr>
                                <tr v-if="!shift.cashLogs || shift.cashLogs.length === 0">
                                    <td colspan="3" class="p-3 text-center text-gray-500">Belum ada catatan pergerakan kas.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Column: X/Z Report Summary -->
            <div class="col-span-1 space-y-6">
                <!-- Expected Cash vs Actual -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold mb-4">Ringkasan Kas</h3>
                    
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Saldo Awal Buka Shift</span>
                            <span>{{ formatCurrency(shift.opening_cash) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Total Penjualan</span>
                            <span>{{ formatCurrency(shift.total_sales) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Total Cash In</span>
                            <span class="text-success">+{{ formatCurrency(totalCashIn) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Total Cash Out</span>
                            <span class="text-danger">-{{ formatCurrency(totalCashOut) }}</span>
                        </div>
                        
                        <div class="pt-3 mt-3 border-t border-gray-200 flex justify-between font-semibold">
                            <span>Kas Harapan (Expected)</span>
                            <span>{{ formatCurrency(shift.expected_cash) }}</span>
                        </div>
                        
                        <template v-if="shift.status === 'closed'">
                            <div class="flex justify-between font-semibold mt-2">
                                <span>Kas Aktual (Closing)</span>
                                <span>{{ formatCurrency(shift.closing_cash) }}</span>
                            </div>
                            
                            <div class="p-3 mt-4 rounded-md" :class="discrepancyClass">
                                <div class="flex justify-between font-bold">
                                    <span>Selisih (Discrepancy)</span>
                                    <span>{{ formatCurrency(discrepancyAmount) }}</span>
                                </div>
                                <div class="text-xs mt-1" v-if="discrepancyAmount > 0">Uang fisik lebih besar dari sistem.</div>
                                <div class="text-xs mt-1" v-else-if="discrepancyAmount < 0">Uang fisik lebih sedikit dari sistem (Minus).</div>
                                <div class="text-xs mt-1" v-else>Saldo klop / seimbang.</div>
                            </div>
                        </template>
                        <template v-else>
                            <div class="p-3 mt-4 bg-blue-50 text-blue-700 rounded-md text-center text-xs">
                                Shift masih aktif. Saldo aktual belum diinput oleh kasir.
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </Container>
</template>

<script setup>
import { computed } from 'vue';
import { faArrowLeft } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { router } from '@inertiajs/vue3';
import Container from '@/Components/UI/Container.vue';
import ContainerHeader from '@/Components/UI/Container/ContainerHeader.vue';
import { formatDateTimeSimple } from '@/Composable/date.js';
import { formatIDR as formatCurrency } from '@/Composable/currency-format.js';

const props = defineProps({
    shift: {
        type: Object,
        required: true,
    },
});

const totalCashIn = computed(() => {
    if (!props.shift.cashLogs) return 0;
    return props.shift.cashLogs
        .filter(log => log.type === 'cash_in')
        .reduce((sum, log) => sum + Number(log.amount), 0);
});

const totalCashOut = computed(() => {
    if (!props.shift.cashLogs) return 0;
    return props.shift.cashLogs
        .filter(log => log.type === 'cash_out')
        .reduce((sum, log) => sum + Number(log.amount), 0);
});

const discrepancyAmount = computed(() => {
    return Number(props.shift.closing_cash) - Number(props.shift.expected_cash);
});

const discrepancyClass = computed(() => {
    const diff = discrepancyAmount.value;
    if (diff === 0) return 'bg-success/10 text-success';
    if (diff > 0) return 'bg-warning/10 text-warning-dark'; // Surplus
    return 'bg-danger/10 text-danger'; // Shortage
});

const goBack = () => {
    router.visit(route('transactions.shifts.index'));
};
</script>
