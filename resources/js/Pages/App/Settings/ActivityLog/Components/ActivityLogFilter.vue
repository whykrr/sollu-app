<template>
    <ActionBar>
        <template #filters>
            <!-- Preset Date Filter -->
            <FilterPresetDate
                v-model:preset="filterForm.preset"
                v-model:date-from="filterForm.date_from"
                v-model:date-to="filterForm.date_to"
                @change="updateQuery"
            />

            <!-- Module Filter -->
            <FilterDropdown
                v-model="filterForm.module"
                label="Modul"
                :options="moduleOptions"
                all-option-label="Semua Modul"
                @change="updateQuery"
            />

            <!-- Outlet Filter (Jika multi outlet) -->
            <FilterDropdown
                v-if="outlets && outlets.length > 1"
                v-model="filterForm.outlet_id"
                label="Outlet"
                :options="outletOptions"
                all-option-label="Semua Outlet"
                @change="updateQuery"
            />
        </template>

        <template #search>
            <FilterSearch
                v-model="filterForm.search"
                placeholder="Cari aktivitas atau pelaku..."
                @clear="updateQuery"
            />
        </template>
    </ActionBar>
</template>

<script setup>
import { computed, reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import ActionBar from '@/Components/UI/ActionBar/ActionBar.vue'
import FilterPresetDate from '@/Components/UI/Filter/FilterPresetDate.vue'
import FilterDropdown from '@/Components/UI/Filter/FilterDropdown.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
    modules: {
        type: Object,
        default: () => ({}),
    },
    outlets: {
        type: Array,
        default: () => [],
    },
})

const filterForm = reactive({
    preset: props.filters?.preset || 'this_month',
    date_from: props.filters?.date_from || null,
    date_to: props.filters?.date_to || null,
    module: props.filters?.module || '',
    outlet_id: props.filters?.outlet_id || '',
    search: props.filters?.search || '',
})

const moduleOptions = computed(() => {
    return Object.entries(props.modules || {}).map(([value, label]) => ({
        value,
        label,
    }))
})

const outletOptions = computed(() => {
    return (props.outlets || []).map(outlet => ({
        value: outlet.id,
        label: outlet.name,
    }))
})

watch(
    () => filterForm.search,
    debounce(() => {
        updateQuery()
    }, 500)
)

const updateQuery = () => {
    const query = {
        ...route().params,
        preset: filterForm.preset || undefined,
        date_from: filterForm.date_from || undefined,
        date_to: filterForm.date_to || undefined,
        module: filterForm.module || undefined,
        outlet_id: filterForm.outlet_id || undefined,
        search: filterForm.search || undefined,
        page: 1,
    }

    router.get(location.pathname, query, {
        preserveState: true,
        preserveScroll: true,
    })
}
</script>
