<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Laporan Pelanggan">
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
                            <th>Nama Pelanggan</th>
                            <th>Kontak</th>
                            <th class="text-right">Total Kunjungan</th>
                            <th class="text-right">Total Belanja</th>
                            <th class="text-right">Kunjungan Terakhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(item, index) in customers.data"
                            :key="index"
                        >
                            <td class="font-bold">{{ item.name }}</td>
                            <td>
                                <div>{{ item.phone }}</div>
                                <div class="text-sm text-muted">
                                    {{ item.email || '-' }}
                                </div>
                            </td>
                            <td class="text-right">
                                {{ formatNumberID(item.total_visits) }}x
                            </td>
                            <td class="text-right font-bold text-success">
                                {{ formatIDR(item.total_spent) }}
                            </td>
                            <td class="text-right">
                                {{ formatDate(item.last_visit) }}
                            </td>
                        </tr>
                        <tr v-if="customers.data.length === 0">
                            <td colspan="5" class="text-center text-muted py-4">
                                Tidak ada data pelanggan yang bertransaksi pada
                                periode ini.
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
    customers: Object,
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
    formFilters.get(route('reports.customers.index'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

const exportPdf = () => {
    router.post(route('reports.customers.export.pdf'), formFilters.data(), {
        preserveScroll: true,
        preserveState: true,
    });
};

const exportCsv = () => {
    router.post(route('reports.customers.export.csv'), formFilters.data(), {
        preserveScroll: true,
        preserveState: true,
    });
};
</script>
