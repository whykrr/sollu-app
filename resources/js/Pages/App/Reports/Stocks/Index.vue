<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Laporan Stock & Aset">
                <button
                    type="button"
                    class="btn btn-flat btn-sm flex items-center gap-1.5 text-xs text-slate-700"
                    title="Klik untuk melihat atau mengubah metode perhitungan aset persediaan"
                    @click="openCostingModal"
                >
                    <FontAwesomeIcon
                        :icon="activeCostingMethod === 'fifo' ? faBoxesStacked : faCalculator"
                        class="text-main"
                    />
                    <span>
                        Metode Aset:
                        <strong class="text-slate-900">{{
                            activeCostingMethod === 'fifo' ? 'FIFO' : 'Moving Average'
                        }}</strong>
                    </span>
                </button>
            </MainPageHeader>
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

        <div class="card card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Nama Item</th>
                            <th class="text-right">Stok Awal</th>
                            <th class="text-right text-success">Masuk</th>
                            <th class="text-right text-danger">Keluar</th>
                            <th class="text-right">Stok Akhir</th>
                            <!-- <th class="text-right">Nilai Aset</th> -->
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in stocks.data" :key="index">
                            <td>{{ item.item_name }}</td>
                            <td class="text-right">
                                {{ formatNumberID(item.starting_stock) }}
                            </td>
                            <td class="text-right text-success">
                                {{ formatNumberID(item.stock_in) }}
                            </td>
                            <td class="text-right text-danger">
                                {{ formatNumberID(item.stock_out) }}
                            </td>
                            <td class="text-right font-bold">
                                {{ formatNumberID(item.closing_stock) }}
                            </td>
                            <!-- <td class="text-right">{{ formatIDR(item.asset_value) }}</td> -->
                        </tr>
                        <tr v-if="stocks.data?.length === 0">
                            <td colspan="5" class="text-center text-muted py-4">
                                Tidak ada pergerakan stok pada periode ini.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination
                class="mt-4"
                :links="stocks.links"
                :from="stocks.from"
                :to="stocks.to"
                :total="stocks.total"
                :per-page="stocks.per_page"
            />
        </div>
    </MainPage>
</template>

<script setup>
import { computed } from 'vue'
import { useForm, router, usePage } from '@inertiajs/vue3'
import {
    faBoxesStacked,
    faCalculator,
    faFileExcel,
    faFilePdf,
    faStore,
} from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import ActionBar from '@/Components/UI/ActionBar/ActionBar.vue'
import ActionsDropdown from '@/Components/UI/ActionsDropdown.vue'
import Pagination from '@/Components/Tables/Pagination.vue'
import GroupDropdownIconField from '@/Components/Form/GroupDropdownIconField.vue'
import InventoryCostingModal from '@/Pages/App/Inventory/Stock/Components/InventoryCostingModal.vue'
import { useAuth } from '@/Composable/useAuth'
import { useModalStore } from '@/store/notification'
import { formatNumberID } from '@/Composable/useNumberFormat'

const props = defineProps({
    filters: Object,
    stocks: Object,
})

const page = usePage()
const modalStore = useModalStore()
const { outlets: userOutlets, selectedOutlet } = useAuth()

const activeCostingMethod = computed(() => {
    return page.props.auth?.business?.inventory_costing_method || 'fifo'
})

const openCostingModal = () => {
    modalStore.open({
        type: 'info',
        title: 'Pengaturan Metode Perhitungan Aset Inventaris',
        component: InventoryCostingModal,
        props: {
            currentMethod: activeCostingMethod.value,
            isSetupMode: false,
        },
        size: 'max-w-2xl',
        showFooter: false,
    })
}

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
    formFilters.get(route('reports.stocks.index'), {
        preserveState: true,
        preserveScroll: true,
    })
}

const exportPdf = () => {
    router.post(route('reports.stocks.export.pdf'), formFilters.data(), {
        preserveScroll: true,
        preserveState: true,
    })
}

const exportCsv = () => {
    router.post(route('reports.stocks.export.csv'), formFilters.data(), {
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
        label: 'Ekspor Excel / CSV',
        icon: faFileExcel,
        handler: exportCsv,
    },
])
</script>
