<template>
    <MainPage>
        <template #header>
            <div
                class="flex justify-between items-center bg-white p-4 rounded-xl shadow-sm border border-neutral-200/60 mb-4"
            >
                <div>
                    <h1 class="text-xl font-bold text-neutral-800">
                        Platform Configuration
                    </h1>
                    <div class="text-sm text-neutral-500">
                        Manage global settings, feature flags, and maintenance
                        modes
                    </div>
                </div>
                <div class="flex gap-2">
                    <button class="btn btn-main btn-sm">
                        <FontAwesomeIcon :icon="faSave" />Save Changes
                    </button>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <!-- System Settings -->
            <div
                class="bg-white rounded-xl shadow-sm border border-neutral-200/60 p-4"
            >
                <h3
                    class="font-bold text-neutral-800 mb-4 border-b border-neutral-100 pb-2"
                >
                    System Settings
                </h3>
                <div class="flex flex-col gap-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-medium text-neutral-800">
                                Maintenance Mode
                            </div>
                            <div class="text-xs text-neutral-500">
                                Enable this to block all merchant access during
                                updates
                            </div>
                        </div>
                        <Switch id="maintenance_mode" />
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-medium text-neutral-800">
                                Signups Allowed
                            </div>
                            <div class="text-xs text-neutral-500">
                                Allow new merchants to register on the platform
                            </div>
                        </div>
                        <Switch id="signups_allowed" :modelValue="1" />
                    </div>
                </div>
            </div>

            <!-- Feature Flags -->
            <div
                class="bg-white rounded-xl shadow-sm border border-neutral-200/60 p-4"
            >
                <h3
                    class="font-bold text-neutral-800 mb-4 border-b border-neutral-100 pb-2"
                >
                    Global Feature Flags
                </h3>
                <div class="flex flex-col gap-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-medium text-neutral-800">
                                AI Analytics Dashboard
                            </div>
                            <div class="text-xs text-neutral-500">
                                Enable new AI insights on merchant dashboard
                            </div>
                        </div>
                        <Switch id="ai_analytics" />
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-medium text-neutral-800">
                                Multi-Outlet Sync V2
                            </div>
                            <div class="text-xs text-neutral-500">
                                Enable the new optimized sync engine for
                                multi-outlets
                            </div>
                        </div>
                        <Switch id="multi_outlet" :modelValue="1" />
                    </div>
                </div>
            </div>

            <!-- Subscription Pricing -->
            <div
                class="bg-white rounded-xl shadow-sm border border-neutral-200/60 lg:col-span-2 overflow-hidden flex flex-col p-4"
            >
                <div
                    class="flex justify-between items-center mb-2 pb-2 border-b border-neutral-200"
                >
                    <h3 class="font-bold text-neutral-800">
                        Subscription Pricing Packages
                    </h3>
                    <button class="btn btn-outline-main btn-sm">
                        <FontAwesomeIcon :icon="faPlus" />Add Package
                    </button>
                </div>
                <Table
                    :headers="tableHeaders"
                    :data="packages.data"
                    :action="true"
                >
                    <template #name="{ row }">
                        <span class="font-medium text-neutral-800">{{
                            row.name
                        }}</span>
                    </template>
                    <template #monthly_price="{ row }">
                        {{ row.monthly_price }}
                    </template>
                    <template #yearly_price="{ row }">
                        {{ row.yearly_price }}
                    </template>
                    <template #max_outlets="{ row }">
                        {{ row.max_outlets }}
                    </template>
                    <template #actions="{ row }">
                        <button
                            class="btn btn-neutral-100 text-main btn-sm"
                            title="Edit"
                        >
                            <FontAwesomeIcon :icon="faPencil" />
                        </button>
                    </template>
                </Table>
            </div>
        </div>
    </MainPage>
</template>

<script setup>
import MainPage from '@/Components/UI/MainPage.vue';
import Table from '@/Components/Tables/Table.vue';
import Switch from '@/Components/Form/Switch.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faSave, faPlus, faPencil } from '@fortawesome/free-solid-svg-icons';
import { ref } from 'vue';

const tableHeaders = [
    { field: 'name', label: 'Package Name', slot: 'name', sortable: true },
    {
        field: 'monthly_price',
        label: 'Monthly Price',
        slot: 'monthly_price',
        sortable: true,
    },
    {
        field: 'yearly_price',
        label: 'Yearly Price',
        slot: 'yearly_price',
        sortable: true,
    },
    { field: 'max_outlets', label: 'Max Outlets', slot: 'max_outlets' },
];

const packages = ref({
    data: [
        {
            id: 1,
            name: 'Basic',
            monthly_price: 'Rp 150.000',
            yearly_price: 'Rp 1.500.000',
            max_outlets: '1',
        },
        {
            id: 2,
            name: 'Premium',
            monthly_price: 'Rp 450.000',
            yearly_price: 'Rp 4.500.000',
            max_outlets: '5',
        },
    ],
});
</script>
