<template>
    <div
        class="bg-white p-2.5 rounded-xl border border-neutral-200/70 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-2"
    >
        <div class="flex flex-wrap items-center gap-2">
            <FilterSearch
                v-model="filterForm.search"
                placeholder="Cari nama bisnis, email, pemilik..."
                class="w-full sm:w-64"
            />

            <div class="flex items-center gap-1 bg-neutral-100 p-1 rounded-lg text-xs font-medium">
                <button
                    type="button"
                    class="px-2.5 py-1 rounded-md transition-colors cursor-pointer"
                    :class="
                        filterForm.status === '' || filterForm.status === 'all'
                            ? 'bg-white text-neutral-800 font-bold'
                            : 'text-neutral-500 hover:text-neutral-800'
                    "
                    @click="setStatus('')"
                >
                    Semua
                </button>
                <button
                    type="button"
                    class="px-2.5 py-1 rounded-md transition-colors cursor-pointer"
                    :class="
                        filterForm.status === 'active'
                            ? 'bg-white text-success font-bold'
                            : 'text-neutral-500 hover:text-neutral-800'
                    "
                    @click="setStatus('active')"
                >
                    Aktif
                </button>
                <button
                    type="button"
                    class="px-2.5 py-1 rounded-md transition-colors cursor-pointer"
                    :class="
                        filterForm.status === 'suspended'
                            ? 'bg-white text-danger font-bold'
                            : 'text-neutral-500 hover:text-neutral-800'
                    "
                    @click="setStatus('suspended')"
                >
                    Ditangguhkan
                </button>
            </div>
        </div>

        <div v-if="businessTypeOptions.length > 1" class="flex items-center gap-2 justify-end">
            <div class="w-44">
                <DropdownField
                    v-model="filterForm.business_type_id"
                    :options="businessTypeOptions"
                    placeholder="Semua Jenis Bisnis"
                    class="sm"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, watch, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import debounce from 'lodash/debounce'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'
import DropdownField from '@/Components/Form/DropdownField.vue'

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
    businessTypes: {
        type: Array,
        default: () => [],
    },
})

const filterForm = reactive({
    search: typeof props.filters?.search === 'string' ? props.filters.search : '',
    status: typeof props.filters?.status === 'string' ? props.filters.status : '',
    business_type_id:
        typeof props.filters?.business_type_id === 'string' ? props.filters.business_type_id : '',
    sort:
        typeof props.filters?.sort === 'string' && props.filters.sort
            ? props.filters.sort
            : 'created_at',
})

const businessTypeOptions = computed(() => {
    const list = [{ value: '', label: 'Semua Jenis Bisnis' }]
    props.businessTypes.forEach(bt => {
        list.push({
            value: String(bt.id),
            label: bt.name,
        })
    })
    return list
})

const setStatus = val => {
    filterForm.status = val
    updateQuery()
}

const updateQuery = () => {
    const query = {
        ...route().params,
        search: filterForm.search || undefined,
        status: filterForm.status || undefined,
        business_type_id: filterForm.business_type_id || undefined,
        sort: filterForm.sort || undefined,
    }

    Object.keys(query).forEach(key => {
        if (query[key] === '' || query[key] === null || query[key] === undefined) {
            delete query[key]
        }
    })

    query.page = 1

    router.get(location.pathname, query, {
        preserveState: true,
        preserveScroll: true,
    })
}

watch(
    () => filterForm.search,
    debounce(() => {
        updateQuery()
    }, 500)
)

watch(
    () => filterForm.business_type_id,
    () => {
        updateQuery()
    }
)

watch(
    () => filterForm.sort,
    () => {
        updateQuery()
    }
)
</script>
