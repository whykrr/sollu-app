<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Laporan Stock & Aset">
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
                            v-model="formFilters.start_date"
                            type="date"
                            class="form sm"
                            @change="applyFilters"
                        />
                        <span>-</span>
                        <input
                            v-model="formFilters.end_date"
                            type="date"
                            class="form sm"
                            @change="applyFilters"
                        />
                    </div>
                    <div class="flex items-center gap-2 ml-auto">
                        <button
                            class="btn btn-outline-primary btn-sm"
                            @click="exportPdf"
                        >
                            <FontAwesomeIcon :icon="faFilePdf" /> Ekspor PDF
                        </button>
                        <button
                            class="btn btn-outline-success btn-sm"
                            @click="exportCsv"
                        >
                            <FontAwesomeIcon :icon="faFileCsv" /> Ekspor CSV
                        </button>
                    </div>
                </div>
            </MainPageHeader>
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
        </div>
    </MainPage>
</template>

<script setup>
import { computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import {
    faFileCsv,
    faFilePdf,
    faStore,
} from '@fortawesome/free-solid-svg-icons';
import MainPage from '@/Components/UI/MainPage.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue';
import GroupDropdownIconField from '@/Components/Form/GroupDropdownIconField.vue';
import { useAuth } from '@/Composable/useAuth';
import { formatIDR } from '@/Composable/currency-format';
import { formatNumberID } from '@/Composable/useNumberFormat';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const props = defineProps({
    filters: Object,
    stocks: Object,
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
    formFilters.get(route('reports.stocks.index'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const exportPdf = () => {
    router.post(route('reports.stocks.export.pdf'), formFilters.data(), {
        preserveScroll: true,
        preserveState: true,
    });
};

const exportCsv = () => {
    router.post(route('reports.stocks.export.csv'), formFilters.data(), {
        preserveScroll: true,
        preserveState: true,
    });
};
</script>
