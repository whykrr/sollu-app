<template>
    <ActionBar>
        <template #filters>
            <FilterDropdown
                v-if="outletOptions.length > 1 && !selectedOutlet"
                v-model="formFilters.outlet"
                label="Outlet"
                :options="outletOptions"
                :icon="faStore"
                all-option-label="Semua Outlet"
                :searchable="true"
                @change="applyFilters"
            />
            <FilterPresetDate
                v-model="formFilters.period"
                v-model:start-date="formFilters.start_date"
                v-model:end-date="formFilters.end_date"
                @change="handleDateChange"
            />
        </template>

        <template #search>
            <FilterSearch
                v-model="formFilters.search"
                placeholder="Cari nama / telepon / email..."
                @search="applyFilters"
            />
        </template>

        <template #tools>
            <ActionsDropdown label="Opsi Data" :items="actionItems" />
        </template>
    </ActionBar>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { faFileCsv, faFilePdf, faStore } from '@fortawesome/free-solid-svg-icons'
import ActionBar from '@/Components/UI/ActionBar/ActionBar.vue'
import FilterDropdown from '@/Components/UI/Filter/FilterDropdown.vue'
import FilterPresetDate from '@/Components/UI/Filter/FilterPresetDate.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'
import ActionsDropdown from '@/Components/UI/ActionsDropdown.vue'
import { useAuth } from '@/Composable/useAuth'

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
})

const { outlets: userOutlets, selectedOutlet } = useAuth()

const outletOptions = computed(() => {
    if (!userOutlets.value || !Array.isArray(userOutlets.value)) return []
    return userOutlets.value.map(store => ({
        value: store.id,
        label: store.name,
    }))
})

const formFilters = ref({
    outlet: props.filters?.outlet ?? '',
    period: props.filters?.period ?? 'this_month',
    start_date: props.filters?.start_date ?? '',
    end_date: props.filters?.end_date ?? '',
    search: props.filters?.search ?? '',
})

watch(
    () => props.filters,
    newFilters => {
        if (newFilters) {
            formFilters.value.outlet = newFilters.outlet ?? ''
            formFilters.value.period = newFilters.period ?? 'this_month'
            formFilters.value.start_date = newFilters.start_date ?? ''
            formFilters.value.end_date = newFilters.end_date ?? ''
            formFilters.value.search = newFilters.search ?? ''
        }
    },
    { deep: true }
)

const applyFilters = () => {
    const params = {}
    if (formFilters.value.outlet) params.outlet = formFilters.value.outlet
    if (formFilters.value.period) params.period = formFilters.value.period
    if (formFilters.value.start_date) params.start_date = formFilters.value.start_date
    if (formFilters.value.end_date) params.end_date = formFilters.value.end_date
    if (formFilters.value.search) params.search = formFilters.value.search

    router.get(route('reports.customers.index'), params, {
        preserveState: true,
        preserveScroll: true,
    })
}

const handleDateChange = range => {
    formFilters.value.period = range.preset
    formFilters.value.start_date = range.startDate
    formFilters.value.end_date = range.endDate
    applyFilters()
}

const exportPdf = () => {
    router.post(route('reports.customers.export.pdf'), formFilters.value, {
        preserveScroll: true,
        preserveState: true,
    })
}

const exportCsv = () => {
    router.post(route('reports.customers.export.csv'), formFilters.value, {
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
