<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Laporan Shift & Kas">
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
                            <th>Buka</th>
                            <th>Tutup</th>
                            <th>Kasir</th>
                            <th class="text-right">Kas Awal</th>
                            <th class="text-right">Sistem (Expected)</th>
                            <th class="text-right">Fisik (Actual)</th>
                            <th class="text-right">Selisih</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in shifts.data" :key="index">
                            <td>{{ formatDateTime(item.opened_at) }}</td>
                            <td>
                                {{
                                    item.closed_at
                                        ? formatDateTime(item.closed_at)
                                        : 'Belum Tutup'
                                }}
                            </td>
                            <td>{{ item.cashier_name }}</td>
                            <td class="text-right">
                                {{ formatIDR(item.starting_cash) }}
                            </td>
                            <td class="text-right">
                                {{ formatIDR(item.expected_ending_cash) }}
                            </td>
                            <td class="text-right">
                                {{ formatIDR(item.actual_ending_cash) }}
                            </td>
                            <td
                                class="text-right font-bold"
                                :class="
                                    item.difference < 0
                                        ? 'text-danger'
                                        : item.difference > 0
                                          ? 'text-success'
                                          : ''
                                "
                            >
                                {{ formatIDR(item.difference) }}
                            </td>
                        </tr>
                        <tr v-if="shifts.data.length === 0">
                            <td colspan="7" class="text-center text-muted py-4">
                                Tidak ada data shift pada periode ini.
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
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const props = defineProps({
    filters: Object,
    shifts: Object,
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
    formFilters.get(route('reports.cashiers.index'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const formatDateTime = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const exportPdf = () => {
    router.post(route('reports.cashiers.export.pdf'), formFilters.data(), {
        preserveScroll: true,
        preserveState: true,
    });
};

const exportCsv = () => {
    router.post(route('reports.cashiers.export.csv'), formFilters.data(), {
        preserveScroll: true,
        preserveState: true,
    });
};
</script>
