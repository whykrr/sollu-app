<template>
    
        <Container>
            <template #header>
                <div class="flex flex-row justify-between gap-2">
                    <div class="flex gap-2">
                        <TextField placeholder="Search actor, action..." class="w-64" />
                        <DropdownField 
                            placeholder="All Categories"
                            :options="[
                                {value: 'merchant', label: 'Merchant Activity'},
                                {value: 'billing', label: 'Billing Activity'},
                                {value: 'config', label: 'Configuration'},
                                {value: 'user', label: 'User Activity'}
                            ]" 
                        />
                        <TextField type="date" class="w-40" />
                    </div>
                </div>
            </template>
            
            <Table
                :headers="tableHeaders"
                :data="audits.data"
                :action="true"
            >
                <template #timestamp="{ row }">
                    <span class="text-neutral-600 whitespace-nowrap">{{ row.timestamp }}</span>
                </template>
                <template #category="{ row }">
                    <span 
                        class="px-2 py-1 text-xs rounded-full font-medium"
                        :class="{
                            'bg-main/10 text-main': row.category === 'Billing',
                            'bg-warning/10 text-warning': row.category === 'Merchant',
                            'bg-info/10 text-info': row.category === 'Config',
                        }"
                    >
                        {{ row.category }}
                    </span>
                </template>
                <template #actor="{ row }">
                    <span class="font-medium text-neutral-800">{{ row.actor }}</span>
                </template>
                <template #action="{ row }">
                    <span class="text-neutral-600">{{ row.action }}</span>
                </template>
                <template #actions="{ row }">
                    <button class="btn btn-neutral-100 text-main btn-sm" title="View Metadata">
                        <FontAwesomeIcon :icon="faEye" />
                    </button>
                </template>
            </Table>

            <template #footer>
                <Pagination
                    :links="audits.links"
                    :from="audits.from"
                    :to="audits.to"
                    :total="audits.total"
                    :per-page="audits.per_page"
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
import { faEye } from '@fortawesome/free-solid-svg-icons';
import { ref } from 'vue';

const tableHeaders = [
    { field: 'timestamp', label: 'Timestamp', slot: 'timestamp', sortable: true },
    { field: 'category', label: 'Category', slot: 'category', sortable: true },
    { field: 'actor', label: 'Actor', slot: 'actor', sortable: true },
    { field: 'action', label: 'Action', slot: 'action' },
];

const audits = ref({
    data: [
        { id: 1, timestamp: '14 Jun 2026 15:20:10', category: 'Billing', actor: 'Admin Finance (Budi)', action: 'Approved Payment Validation for INV-2026-892' },
        { id: 2, timestamp: '14 Jun 2026 14:15:00', category: 'Merchant', actor: 'System (Auto)', action: 'Suspended Merchant #M-1002 due to expired subscription' },
        { id: 3, timestamp: '13 Jun 2026 10:05:22', category: 'Config', actor: 'Super Admin (Andi)', action: 'Updated Global Setting: Enabled Multi-Outlet Sync V2' },
    ],
    links: [
        { url: null, label: '&laquo; Previous', active: false },
        { url: '/cockpit/audit?page=1', label: '1', active: true },
        { url: null, label: 'Next &raquo;', active: false },
    ],
    from: 1,
    to: 3,
    total: 42105,
    per_page: 20
});
</script>
