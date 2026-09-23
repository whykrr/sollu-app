<template>
    <ActionBar>
        <template #search>
            <FilterSearch
                v-model="filterForm.search"
                placeholder="Cari peran..."
                @clear="updateQuery"
            />
        </template>

        <template #create>
            <div class="flex items-center gap-1.5">
                <button
                    v-if="canCreate"
                    type="button"
                    class="btn btn-outline-main btn-sm h-[30px] inline-flex items-center gap-1.5 cursor-pointer text-xs"
                    @click="$emit('open-template')"
                >
                    <FontAwesomeIcon :icon="faBolt" />
                    <span>Template Peran</span>
                </button>

                <button
                    v-if="canCreate"
                    type="button"
                    class="btn btn-main btn-sm h-[30px] inline-flex items-center gap-1.5 cursor-pointer text-xs"
                    @click="$emit('create')"
                >
                    <FontAwesomeIcon :icon="faPlus" />
                    <span>Peran Baru</span>
                </button>
            </div>
        </template>
    </ActionBar>
</template>

<script setup>
import { reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faPlus, faBolt } from '@fortawesome/free-solid-svg-icons'
import ActionBar from '@/Components/UI/ActionBar/ActionBar.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'

defineEmits(['create', 'open-template'])

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
    canCreate: {
        type: Boolean,
        default: true,
    },
})

const filterForm = reactive({
    search: props.filters?.search ?? '',
})

const updateQuery = () => {
    router.get(
        route('settings.roles.index'),
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
