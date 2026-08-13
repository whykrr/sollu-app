<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Laporan Penjualan">
                <div class="flex flex-wrap items-center gap-2">
                    <div v-if="outletOptions.length > 0" class="w-48">
                        <GroupDropdownIconField
                            id="outlet-filter"
                            v-model="formFilters.outlet"
                            :icon="faStore"
                            class="sm"
                            :options="[
                                { value: '', label: 'Semua Outlet' },
                                ...outletOptions,
                            ]"
                            @change="applyFilters"
                        />
                    </div>
                    <div class="flex items-center gap-2">
                        <input
                            type="date"
                            v-model="formFilters.start_date"
                            class="form sm"
                            @change="applyFilters"
                        />
                        <span>-</span>
                        <input
                            type="date"
                            v-model="formFilters.end_date"
                            class="form sm"
                            @change="applyFilters"
                        />
                    </div>
                </div>
            </MainPageHeader>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
            <div class="lg:col-span-2 card card-body">
                <h5 class="font-semibold mb-4">Laporan Harian</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th class="text-right">Gross Omset</th>
                                <th class="text-right">Diskon</th>
                                <th class="text-right">Pajak</th>
                                <th class="text-right">Net Omset</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(item, index) in dailySales"
                                :key="index"
                            >
                                <td>{{ item.date }}</td>
                                <td class="text-right">
                                    {{ formatIDR(item.gross_sales) }}
                                </td>
                                <td class="text-right text-danger">
                                    {{ formatIDR(item.total_discount) }}
                                </td>
                                <td class="text-right">
                                    {{ formatIDR(item.total_tax) }}
                                </td>
                                <td class="text-right font-bold">
                                    {{ formatIDR(item.net_sales) }}
                                </td>
                            </tr>
                            <tr v-if="dailySales.length === 0">
                                <td
                                    colspan="5"
                                    class="text-center text-muted py-4"
                                >
                                    Tidak ada data penjualan pada periode ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="lg:col-span-1 card card-body">
                <h5 class="font-semibold mb-4">Metode Pembayaran</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Metode</th>
                                <th class="text-right">Transaksi</th>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(item, index) in paymentMethods"
                                :key="index"
                            >
                                <td>{{ item.payment_name }}</td>
                                <td class="text-right">
                                    {{
                                        formatNumberID(item.total_transactions)
                                    }}
                                </td>
                                <td class="text-right">
                                    {{ formatIDR(item.total_revenue) }}
                                </td>
                            </tr>
                            <tr v-if="paymentMethods.length === 0">
                                <td
                                    colspan="3"
                                    class="text-center text-muted py-4"
                                >
                                    Tidak ada data pembayaran.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </MainPage>
</template>

<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { faStore } from '@fortawesome/free-solid-svg-icons';
import MainPage from '@/Components/UI/MainPage.vue';
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue';
import GroupDropdownIconField from '@/Components/Form/GroupDropdownIconField.vue';
import { useAuth } from '@/Composable/useAuth';
import { formatIDR } from '@/Composable/currency-format';
import { formatNumberID } from '@/Composable/useNumberFormat';

const props = defineProps({
    filters: Object,
    dailySales: Array,
    paymentMethods: Array,
});

const { outlets: userOutlets } = useAuth();

const outletOptions = computed(() => {
    if (!userOutlets.value || !Array.isArray(userOutlets.value)) return [];
    return userOutlets.value.map((store) => ({
        value: store.id,
        label: store.name,
    }));
});

const formFilters = useForm({
    outlet: props.filters?.outlet ?? '',
    start_date: props.filters?.start_date ?? '',
    end_date: props.filters?.end_date ?? '',
});

const applyFilters = () => {
    formFilters.get(route('reports.sales.index'), {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>
