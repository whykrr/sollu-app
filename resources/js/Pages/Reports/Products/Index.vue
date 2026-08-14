<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Laporan Produk">
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
                    <div class="flex items-center gap-2 ml-auto">
                        <a :href="route('reports.products.export.pdf', formFilters)" target="_blank" class="btn btn-outline-primary sm">
                            <FontAwesomeIcon :icon="faFilePdf" /> Ekspor PDF
                        </a>
                        <button @click="exportCsv" class="btn btn-outline-success sm">
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
                            <th>Nama Produk</th>
                            <th>Kategori</th>
                            <th class="text-right">Qty Terjual</th>
                            <th class="text-right">Total Penjualan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in products" :key="index">
                            <td>{{ item.product_name }}</td>
                            <td>{{ item.category_name || '-' }}</td>
                            <td class="text-right">
                                {{ formatNumberID(item.total_qty) }}
                            </td>
                            <td class="text-right">
                                {{ formatIDR(item.total_sales) }}
                            </td>
                        </tr>
                        <tr v-if="products.length === 0">
                            <td colspan="4" class="text-center text-muted py-4">
                                Tidak ada data penjualan produk pada periode
                                ini.
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
import { faStore } from '@fortawesome/free-solid-svg-icons';
import MainPage from '@/Components/UI/MainPage.vue';
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue';
import GroupDropdownIconField from '@/Components/Form/GroupDropdownIconField.vue';
import { useAuth } from '@/Composable/useAuth';
import { formatIDR } from '@/Composable/currency-format';
import { formatNumberID } from '@/Composable/useNumberFormat';

const props = defineProps({
    filters: Object,
    products: Array,
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
    formFilters.get(route('reports.products.index'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const exportCsv = () => {
    router.post(route('reports.products.export.csv'), formFilters.data(), {
        preserveScroll: true,
        preserveState: true,
    });
};

</script>
