<template>
    <ActionBar>
        <template #search>
            <FilterSearch
                v-model="filterForm.search"
                placeholder="Cari outlet..."
                @clear="updateQuery"
            />
        </template>

        <template #create>
            <button
                type="button"
                class="btn btn-main btn-sm h-[30px] inline-flex items-center gap-1.5 cursor-pointer"
                @click="$emit('create')"
            >
                <FontAwesomeIcon :icon="faPlus" />
                <span>Tambah Outlet Baru</span>
            </button>
        </template>
    </ActionBar>
</template>

<script setup>
import { reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faPlus } from '@fortawesome/free-solid-svg-icons'
import ActionBar from '@/Components/UI/ActionBar/ActionBar.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'

defineEmits(['create'])

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
})

const filterForm = reactive({
    search: props.filters?.search ?? '',
})

const updateQuery = () => {
    router.get(
        route('settings.outlets.index'),
        { ...route().params, search: filterForm.search || undefined, page: 1 },
        {
            preserveState: true,
            preserveScroll: true,
        }
    )
}

watch(
    () => filterForm.search,
    debounce(() => {
        updateQuery()
    }, 500)
)
</script>
