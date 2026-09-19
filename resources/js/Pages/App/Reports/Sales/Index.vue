<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Laporan Penjualan" />
        </template>

        <template #filter>
            <ActionBar>
                <template #filters>
                    <div v-if="outletOptions.length > 1 && !selectedOutlet" class="w-48">
                        <GroupDropdownIconField
                            id="outlet-filter"
                            v-model="formFilters.outlet"
                            :icon="faStore"
                            size="sm"
                            :options="[{ value: '', label: 'Semua Outlet' }, ...outletOptions]"
                            @change="applyFilters"
                        />
                    </div>
                    <div class="flex items-center gap-1.5">
                        <input
                            v-model="formFilters.start_date"
                            type="date"
                            class="form sm h-[30px]"
                            @change="applyFilters"
                        />
                        <span class="text-slate-400 text-xs">-</span>
                        <input
                            v-model="formFilters.end_date"
                            type="date"
                            class="form sm h-[30px]"
                            @change="applyFilters"
                        />
                    </div>
                </template>

                <template #tools>
                    <ActionsDropdown label="Opsi Data" :items="actionItems" />
                </template>
            </ActionBar>
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
                            <tr v-for="(item, index) in dailySales.data" :key="index">
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
                            <tr v-if="dailySales.data?.length === 0">
                                <td colspan="5" class="text-center text-muted py-4">
                                    Tidak ada data penjualan pada periode ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Pagination
                    class="mt-4"
                    :links="dailySales.links"
                    :from="dailySales.from"
                    :to="dailySales.to"
                    :total="dailySales.total"
                    :per-page="dailySales.per_page"
                />
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
                            <tr v-for="(item, index) in paymentMethods" :key="index">
                                <td>{{ item.payment_name }}</td>
                                <td class="text-right">
                                    {{ formatNumberID(item.total_transactions) }}
                                </td>
                                <td class="text-right">
                                    {{ formatIDR(item.total_revenue) }}
                                </td>
                            </tr>
                            <tr v-if="paymentMethods.length === 0">
                                <td colspan="3" class="text-center text-muted py-4">
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
import { computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { faFileCsv, faFilePdf, faStore } from '@fortawesome/free-solid-svg-icons'
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import ActionBar from '@/Components/UI/ActionBar/ActionBar.vue'
import ActionsDropdown from '@/Components/UI/ActionsDropdown.vue'
import Pagination from '@/Components/Tables/Pagination.vue'
import GroupDropdownIconField from '@/Components/Form/GroupDropdownIconField.vue'
import { useAuth } from '@/Composable/useAuth'
import { formatIDR } from '@/Composable/currency-format'
import { formatNumberID } from '@/Composable/useNumberFormat'

const props = defineProps({
    filters: Object,
    dailySales: Object,
    paymentMethods: Array,
})

const { outlets: userOutlets, selectedOutlet } = useAuth()

const outletOptions = computed(() => {
    if (!userOutlets.value || !Array.isArray(userOutlets.value)) return []
    return userOutlets.value.map(store => ({
        value: store.id,
        label: store.name,
    }))
})

const formFilters = useForm({
    outlet: props.filters?.outlet ?? '',
    start_date: props.filters?.start_date ?? '',
    end_date: props.filters?.end_date ?? '',
})

const applyFilters = () => {
    formFilters.get(route('reports.sales.index'), {
        preserveState: true,
        preserveScroll: true,
    })
}

const exportPdf = () => {
    router.post(route('reports.sales.export.pdf'), formFilters.data(), {
        preserveScroll: true,
        preserveState: true,
    })
}

const exportCsv = () => {
    router.post(route('reports.sales.export.csv'), formFilters.data(), {
        preserveScroll: true,
        preserveState: true,
    })
}

const actionItems = computed(() => [
    {
        label: 'Ekspor PDF',
        icon: faFilePdf,
        handler: exportPdf,
    },
    {
        label: 'Ekspor CSV',
        icon: faFileCsv,
        handler: exportCsv,
    },
])
</script>
