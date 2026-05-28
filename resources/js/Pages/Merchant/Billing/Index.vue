<template>
    <Container>
        <div class="flex flex-col gap-2">
            <div class="bg-white border rounded-md py-4">
                <div class="flex">
                    <span
                        class="bg-gradient-to-r from-main to-secondary-dark py-1.5 px-4 rounded-r-md text-white font-semibold text-sm"
                    >
                        Terdaftar Sejak
                        {{ formatDateID(auth.merchant.created_at) }}
                    </span>
                </div>
                <div class="mt-2 flex flex-col px-4">
                    <div class="font-bold text-xl">
                        {{ subscription.plan.name }}
                    </div>
                    <div class="flex gap-1 text-sm">
                        <div class="font-semibold">Tanggal Berakhir</div>
                        <div class="text-gray-600">
                            {{ formatDateID(auth.merchant.expired_at) }}
                        </div>
                        <div
                            v-if="
                                gapDaysFromNow(auth.merchant.expired_at) <= 10
                            "
                            class="text-danger"
                        >
                            (Sisa
                            {{ gapDaysFromNow(auth.merchant.expired_at) }} hari
                            lagi)
                        </div>
                    </div>
                    <div class="flex gap-1 text-sm">
                        <div class="font-semibold">Jumlah Outlet</div>
                        <div class="text-gray-600">
                            {{ auth.outlets.length }} Outlet
                        </div>
                    </div>
                    <div class="flex gap-1 text-sm">
                        <div class="font-semibold">Email Notifikasi</div>
                        <div class="text-gray-600">
                            {{ auth.merchant.email }}
                        </div>
                    </div>
                    <hr class="border-t border-neutral-300 my-2" />
                    <div class="space-x-2">
                        <Link
                            v-if="subscription.plan.is_trial"
                            :href="route('merchant.billing.plans')"
                            class="btn btn-outline-main btn-sm"
                        >
                            Langganan Sekarang
                        </Link>

                        <Link
                            v-if="subscription.plan.is_trial === false"
                            :href="route('merchant.billing.plans')"
                            class="btn btn-outline-info btn-sm"
                        >
                            Ubah Langganan
                        </Link>

                        <button
                            v-if="
                                gapDaysFromNow(auth.merchant.expired_at) <=
                                    10 && subscription.plan.is_trial === false
                            "
                            :href="route('merchant.invoices.index')"
                            class="btn btn-outline-main btn-sm"
                        >
                            Bayar Tagihan
                        </button>
                    </div>
                </div>
            </div>

            <div class="space-x-2">
                <div class="text font-semibold">Riwayat Langganan</div>
                <div>
                    <Table :headers="tableSetting" :data="subscriptions.data">
                        <template #plan="{ row }">
                            {{ row.plan.name }} ({{ row.plan.duration }} hari)
                        </template>
                        <template #created_at="{ row }">
                            {{ formatDateTimeID(row.created_at) }}
                        </template>
                        <template #start_date="{ row }">
                            {{ formatDateID(row.start_date) }}
                        </template>
                        <template #end_date="{ row }">
                            {{ formatDateID(row.end_date) }}
                        </template>
                        <template #status="{ row }">
                            <label
                                v-if="row.is_active"
                                class="badge pill text-sm badge-success"
                                >Aktif</label
                            >
                            <label
                                v-else
                                class="badge pill text-sm badge-warning"
                                >Tidak Aktif</label
                            >
                        </template>
                    </Table>
                </div>
            </div>
        </div>
        <template #footer>
            <Pagination
                :links="subscriptions.links"
                :from="subscriptions.from"
                :to="subscriptions.to"
                :total="subscriptions.total"
                :per-page="subscriptions.per_page ?? 20"
            />
        </template>
    </Container>
</template>

<script setup>
import Pagination from '@/Components/Tables/Pagination.vue';
import Table from '@/Components/Tables/Table.vue';
import Container from '@/Components/UI/Container.vue';
import {
    formatDateID,
    formatDateTimeID,
    gapDaysFromNow,
} from '@/Composable/date';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    subscription: Object,
    subscriptions: Object,
});

const tableSetting = [
    { field: 'plan', label: 'Langganan', slot: 'plan' },
    { field: 'created_at', label: 'Tanggal Dibuat', slot: 'created_at' },
    { field: 'start_date', label: 'Tanggal Mulai', slot: 'start_date' },
    { field: 'end_date', label: 'Tanggal Akhir', slot: 'end_date' },
    { field: 'is_active', label: 'Status', slot: 'status' },
];

const page = usePage();
const auth = computed(() => page.props.auth);
</script>
