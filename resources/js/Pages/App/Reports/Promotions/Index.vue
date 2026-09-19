<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Laporan Promo" />
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
                            <th>Nama Promo</th>
                            <th>Tipe Promo</th>
                            <th class="text-right">Total Pemakaian</th>
                            <th class="text-right">Total Diskon Diberikan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in promotions.data" :key="index">
                            <td>{{ item.promo_name }}</td>
                            <td>
                                <span class="badge badge-primary">{{ item.promo_type }}</span>
                            </td>
                            <td class="text-right">{{ formatNumberID(item.total_usage) }}x</td>
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
            <Pagination
                class="mt-4"
                :links="promotions.links"
                :from="promotions.from"
                :to="promotions.to"
                :total="promotions.total"
                :per-page="promotions.per_page"
            />
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
    promotions: Object,
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
    formFilters.get(route('reports.promotions.index'), {
        preserveState: true,
        preserveScroll: true,
    })
}

const exportPdf = () => {
    router.post(route('reports.promotions.export.pdf'), formFilters.data(), {
        preserveScroll: true,
        preserveState: true,
    })
}

const exportCsv = () => {
    router.post(route('reports.promotions.export.csv'), formFilters.data(), {
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
