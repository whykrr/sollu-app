<template>
  <div class="flex flex-row items-center gap-2">
    <div>
      <FilterSearch v-model="filterForm.search" />
    </div>
    <div v-if="outlets.length !== 1 && selectedOutlet === null">
      <GroupDropdownIconField
        id="outlets"
        v-model="filterForm.outlet"
        :icon="faMapMarkerAlt"
        placeholder="Semua Outlet"
        class="sm"
        :options="outlets"
      />
    </div>
    <div>
      <GroupDropdownIconField
        id="roles"
        v-model="filterForm.role"
        :icon="faUserShield"
        placeholder="Semua Peran"
        class="sm"
        :options="roles"
      />
    </div>
    <div>
      <FilterStatus v-model="filterForm.status" />
    </div>
  </div>
</template>

<script setup>
import GroupDropdownIconField from '@/Components/Dashboard/Form/GroupDropdownIconField.vue'
import FilterSearch from '@/Components/Dashboard/UI/Filter/FilterSearch.vue'
import FilterStatus from '@/Components/Dashboard/UI/Filter/FilterStatus.vue'
import {
    faMapMarkerAlt,
    faUserShield,
} from '@fortawesome/free-solid-svg-icons'
import { router, usePage } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import { computed, reactive, watch } from 'vue'

const outlets = usePage().props.auth.outlets.map((store) => ({
    value: store.id,
    label: store.name,
}))

const selectedOutlet = computed(() => usePage().props.selectedOutlet)

const props = defineProps({
    filters: Object,
    roles: Object,
})

const filterForm = reactive({
    search: props.filters?.search ?? '',
    outlet: props.filters?.outlet ?? '',
    role: props.filters?.role ?? '',
    status: props.filters?.status ?? '',
})

watch(
    filterForm,
    debounce(
        () =>
            router.get(
                route('dashboard.employees.index'),
                { ...route().params, ...filterForm, page: 1 },
                {
                    preserveState: true,
                    preserveScroll: true,
                },
            ),
        500,
    ),
)
</script>
