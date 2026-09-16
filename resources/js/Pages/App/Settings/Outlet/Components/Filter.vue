<template>
    <div class="flex items-center gap-2">
        <div>
            <FilterSearch v-model="filterForm.search" />
        </div>
        <div class="grow"></div>
    </div>
</template>

<script setup>
import { reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'

const props = defineProps({
    filters: Object,
})

const filterForm = reactive({
    search: props.filters?.search ?? '',
})

watch(
    filterForm,
    debounce(
        () =>
            router.get(
                route('settings.outlets.index'),
                { ...route().params, search: filterForm.search || undefined, page: 1 },
                {
                    preserveState: true,
                    preserveScroll: true,
                }
            ),
        500
    )
)
</script>
