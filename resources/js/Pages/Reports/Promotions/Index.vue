<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Laporan Promo">
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
                            <th>Nama Promo</th>
                            <th>Tipe Promo</th>
                            <th class="text-right">Total Pemakaian</th>
                            <th class="text-right">Total Diskon Diberikan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(item, index) in promotions.data"
                            :key="index"
                        >
                            <td>{{ item.promo_name }}</td>
                            <td>
                                <span class="badge badge-primary">{{
                                    item.promo_type
                                }}</span>
                            </td>
                            <td class="text-right">
                                {{ formatNumberID(item.total_usage) }}x
                            </td>
                            <td class="text-right text-danger">
                                {{ formatIDR(item.total_discount_given) }}
                            </td>
                        </tr>
                        <tr v-if="promotions.data?.length === 0">
                            <td colspan="4" class="text-center text-muted py-4">
                                Tidak ada promo yang digunakan pada periode ini.
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
    promotions: Object,
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
    formFilters.get(route('reports.promotions.index'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const exportPdf = () => {
    router.post(route('reports.promotions.export.pdf'), formFilters.data(), {
        preserveScroll: true,
        preserveState: true,
    });
};

const exportCsv = () => {
    router.post(route('reports.promotions.export.csv'), formFilters.data(), {
        preserveScroll: true,
        preserveState: true,
    });
};
</script>
