<template>
    <MainPage>
        <template #header>
            <div class="flex flex-row justify-between gap-2">
                <div class="flex gap-2">
                    <TextField placeholder="Search merchant..." class="w-64" />
                    <DropdownField
                        placeholder="All Plans"
                        :options="[
                            { value: 'basic', label: 'Basic' },
                            { value: 'premium', label: 'Premium' },
                            { value: 'enterprise', label: 'Enterprise' },
                        ]"
                    />
                    <DropdownField
                        placeholder="Status"
                        :options="[
                            { value: 'active', label: 'Active' },
                            { value: 'expiring', label: 'Expiring Soon' },
                            { value: 'expired', label: 'Expired' },
                        ]"
                    />
                </div>
                <div class="flex gap-2">
                    <button class="btn btn-outline-main">
                        <FontAwesomeIcon :icon="faDownload" class="mr-2" />
                        Export Report
                    </button>
                </div>
            </div>
        </template>

        <Table
            :headers="tableHeaders"
            :data="subscriptions.data"
            :action="true"
        >
            <template #id="{ row }">
                <span class="text-neutral-500 font-medium">{{ row.id }}</span>
            </template>
            <template #merchant="{ row }">
                {{ row.merchant }}
            </template>
            <template #plan="{ row }">
                {{ row.plan }}
            </template>
            <template #status="{ row }">
                <span
                    v-if="row.status === 'Active'"
                    class="px-2 py-1 bg-success/10 text-success text-xs rounded-full font-medium"
                >
                    Active
                </span>
                <span
                    v-else-if="row.status === 'Expiring Soon'"
                    class="px-2 py-1 bg-warning/10 text-warning text-xs rounded-full font-medium"
                >
                    Expiring Soon
                </span>
                <span
                    v-else
                    class="px-2 py-1 bg-danger/10 text-danger text-xs rounded-full font-medium"
                >
                    Expired
                </span>
            </template>
            <template #expires_at="{ row }">
                {{ row.expires_at }}
            </template>
            <template #actions="{ row }">
                <Link
                    :href="
                        route().has('cockpit.subscriptions.show')
                            ? route('cockpit.subscriptions.show', 1)
                            : '#'
                    "
                    class="btn btn-neutral-100 text-neutral-600 btn-sm"
                    title="Manage"
                >
                    <FontAwesomeIcon :icon="faCog" /> Manage
                </Link>
            </template>
        </Table>

        <template #footer>
            <Pagination
                :links="subscriptions.links"
                :from="subscriptions.from"
                :to="subscriptions.to"
                :total="subscriptions.total"
                :per-page="subscriptions.per_page"
            />
        </template>
    </MainPage>
</template>

<script setup>
import MainPage from '@/Components/UI/MainPage.vue';
import Table from '@/Components/Tables/Table.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import TextField from '@/Components/Form/TextField.vue';
import DropdownField from '@/Components/Form/DropdownField.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faDownload, faCog } from '@fortawesome/free-solid-svg-icons';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const tableHeaders = [
    { field: 'id', label: 'Sub ID', slot: 'id', sortable: true },
    {
        field: 'merchant',
        label: 'Merchant Name',
        slot: 'merchant',
        sortable: true,
    },
    { field: 'plan', label: 'Plan', slot: 'plan' },
    { field: 'status', label: 'Status', slot: 'status' },
    {
        field: 'expires_at',
        label: 'Expires At',
        slot: 'expires_at',
        sortable: true,
    },
];

const subscriptions = ref({
    data: [
        {
            id: '#SUB-001',
            merchant: 'Toko Sejahtera',
            plan: 'Premium (Yearly)',
            status: 'Expiring Soon',
            expires_at: '12 Jan 2027',
        },
        {
            id: '#SUB-002',
            merchant: 'Kedai Kopi Senja',
            plan: 'Basic (Monthly)',
            status: 'Active',
            expires_at: '25 Feb 2027',
        },
    ],
    links: [
        { url: null, label: '&laquo; Previous', active: false },
        { url: '/cockpit/subscriptions?page=1', label: '1', active: true },
        { url: null, label: 'Next &raquo;', active: false },
    ],
    from: 1,
    to: 2,
    total: 2,
    per_page: 20,
});
</script>
