<template>
    <div
        class="bg-white p-2.5 rounded-xl border border-neutral-200/70 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-2"
    >
        <div class="flex flex-wrap items-center gap-2">
            <FilterSearch
                v-model="filterForm.search"
                placeholder="Cari nomor invoice, merchant..."
                class="w-full sm:w-72"
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
                        filterForm.status === 'pending' || filterForm.status === 'pending_review'
                            ? 'bg-white text-amber-600 font-bold'
                            : 'text-neutral-500 hover:text-neutral-800'
                    "
                    @click="setStatus('pending')"
                >
                    Menunggu Verifikasi
                </button>
                <button
                    type="button"
                    class="px-2.5 py-1 rounded-md transition-colors cursor-pointer"
                    :class="
                        filterForm.status === 'paid'
                            ? 'bg-white text-success font-bold'
                            : 'text-neutral-500 hover:text-neutral-800'
                    "
                    @click="setStatus('paid')"
                >
                    Lunas
                </button>
                <button
                    type="button"
                    class="px-2.5 py-1 rounded-md transition-colors cursor-pointer"
                    :class="
                        filterForm.status === 'rejected'
                            ? 'bg-white text-danger font-bold'
                            : 'text-neutral-500 hover:text-neutral-800'
                    "
                    @click="setStatus('rejected')"
                >
                    Ditolak
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import debounce from 'lodash/debounce'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
})

const filterForm = reactive({
    search: typeof props.filters?.search === 'string' ? props.filters.search : '',
    status: typeof props.filters?.status === 'string' ? props.filters.status : '',
    sort:
        typeof props.filters?.sort === 'string' && props.filters.sort
            ? props.filters.sort
            : 'created_at',
    direction:
        typeof props.filters?.direction === 'string' && props.filters.direction
            ? props.filters.direction
            : 'desc',
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
        sort: filterForm.sort || undefined,
        direction: filterForm.direction || undefined,
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
</script>
