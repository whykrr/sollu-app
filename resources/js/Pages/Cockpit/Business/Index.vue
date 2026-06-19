<template>
    <Container>
        <template #header>
            <div class="flex flex-row justify-between gap-2">
                <div class="flex gap-2">
                    <TextField 
                        v-model="params.search" 
                        placeholder="Search name, email, ID..." 
                        @keyup.enter="applyFilters" 
                        class="w-64"
                    />
                    <DropdownField 
                        v-model="params.status" 
                        placeholder="All Status"
                        :options="[{value: 'active', label: 'Active'}, {value: 'suspended', label: 'Suspended'}]" 
                        @change="applyFilters" 
                    />
                </div>
            </div>
        </template>
        
        <Table
            :headers="tableHeaders"
            :data="businesses.data"
            :action="true"
            :sort="filters.sort"
            :sort-direction="filters.direction"
        >
            <template #id="{ row }">
                <span class="text-neutral-500 font-medium text-xs">{{ row.id.substring(0, 8) }}</span>
            </template>
            <template #name="{ row }">
                <div>
                    <div class="font-medium text-neutral-800">{{ row.name }}</div>
                    <div class="text-xs text-neutral-500">{{ row.email }}</div>
                </div>
            </template>
            <template #outlets="{ row }">
                {{ row.outlets_count }} Outlets
            </template>
            <template #status="{ row }">
                <span 
                    v-if="row.status === 'active'" 
                    class="px-2 py-1 bg-success/10 text-success text-xs rounded-full font-medium"
                >
                    Active
                </span>
                <span 
                    v-else 
                    class="px-2 py-1 bg-danger/10 text-danger text-xs rounded-full font-medium"
                >
                    Suspended
                </span>
            </template>
            <template #created_at="{ row }">
                {{ new Date(row.created_at).toLocaleDateString() }}
            </template>
            <template #actions="{ row }">
                <Link :href="route('cockpit.merchants.show', row.id)" class="btn btn-neutral-100 text-neutral-600 btn-sm" title="View Detail">
                    <FontAwesomeIcon :icon="faEye" />
                </Link>
                <button 
                    v-if="row.status === 'active'" 
                    class="btn btn-danger/10 text-danger btn-sm" 
                    title="Suspend"
                    @click="toggleStatus(row.id, 'suspended')"
                >
                    <FontAwesomeIcon :icon="faBan" />
                </button>
                <button 
                    v-else 
                    class="btn btn-success/10 text-success btn-sm" 
                    title="Activate"
                    @click="toggleStatus(row.id, 'active')"
                >
                    <FontAwesomeIcon :icon="faCheck" />
                </button>
            </template>
        </Table>

        <template #footer>
            <Pagination
                :links="businesses.links"
                :from="businesses.from"
                :to="businesses.to"
                :total="businesses.total"
                :per-page="businesses.per_page"
            />
        </template>
    </Container>
</template>

<script setup>
import Container from '@/Components/UI/Container.vue';
import Table from '@/Components/Tables/Table.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import TextField from '@/Components/Form/TextField.vue';
import DropdownField from '@/Components/Form/DropdownField.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faEye, faBan, faCheck } from '@fortawesome/free-solid-svg-icons';
import { Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    businesses: Object,
    filters: Object,
});

const tableHeaders = [
    { field: 'id', label: 'ID', slot: 'id' },
    { field: 'name', label: 'Merchant', slot: 'name', sortable: true },
    { field: 'outlets', label: 'Outlets', slot: 'outlets' },
    { field: 'status', label: 'Status', slot: 'status', sortable: true },
    { field: 'created_at', label: 'Joined At', slot: 'created_at', sortable: true },
];

const params = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
});

const applyFilters = () => {
    router.get(route('cockpit.merchants.index'), params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const toggleStatus = (id, newStatus) => {
    if (confirm(`Are you sure you want to change this merchant's status to ${newStatus}?`)) {
        router.post(route('cockpit.merchants.toggle-status', id), {
            status: newStatus
        }, {
            preserveScroll: true
        });
    }
};
</script>
