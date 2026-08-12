<template>
    <MainPage>
        <template #header>
            <div
                class="flex justify-between items-center bg-white p-4 rounded-xl shadow-sm border border-neutral-200/60 mb-4"
            >
                <div class="flex items-center gap-4">
                    <Link
                        :href="
                            route().has('cockpit.subscriptions.index')
                                ? route('cockpit.subscriptions.index')
                                : '#'
                        "
                        class="text-neutral-400 hover:text-neutral-600 transition-colors"
                    >
                        <FontAwesomeIcon :icon="faArrowLeft" class="text-xl" />
                    </Link>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl font-bold text-neutral-800">
                                Subscription #SUB-001
                            </h1>
                            <span
                                class="px-2 py-0.5 bg-warning/10 text-warning text-xs rounded-full font-medium"
                                >Expiring Soon</span
                            >
                        </div>
                        <div class="text-sm text-neutral-500 mt-1">
                            Merchant: Toko Sejahtera (#M-1001)
                        </div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button class="btn btn-outline-danger btn-sm">
                        <FontAwesomeIcon :icon="faBan" class="mr-2" />Suspend
                    </button>
                    <button class="btn btn-main btn-sm">
                        <FontAwesomeIcon :icon="faSync" class="mr-2" />Extend
                        Subscription
                    </button>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div
                class="col-span-1 md:col-span-2 bg-white rounded-xl shadow-sm border border-neutral-200/60 p-4"
            >
                <h3 class="font-bold text-neutral-800 mb-4">
                    Subscription Detail
                </h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div class="text-xs text-neutral-500">Plan Name</div>
                        <div class="text-sm font-medium text-neutral-800">
                            Premium Plan
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-neutral-500">
                            Billing Cycle
                        </div>
                        <div class="text-sm font-medium text-neutral-800">
                            Yearly
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-neutral-500">Start Date</div>
                        <div class="text-sm font-medium text-neutral-800">
                            12 Jan 2026
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-neutral-500">End Date</div>
                        <div class="text-sm font-medium text-warning">
                            12 Jan 2027 (12 days left)
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-neutral-500">
                            Outlets Included
                        </div>
                        <div class="text-sm font-medium text-neutral-800">
                            Up to 5 Outlets
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-neutral-500">Price</div>
                        <div class="text-sm font-medium text-neutral-800">
                            Rp 4.500.000 / Year
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="col-span-1 bg-white rounded-xl shadow-sm border border-neutral-200/60 p-4 flex flex-col"
            >
                <h3 class="font-bold text-neutral-800 mb-4">Invoice History</h3>
                <div class="flex-1 overflow-y-auto">
                    <div
                        class="flex justify-between items-center border-b border-neutral-100 py-2"
                    >
                        <div>
                            <div class="text-sm font-medium text-neutral-800">
                                INV-2026-001
                            </div>
                            <div class="text-xs text-neutral-500">
                                12 Jan 2026
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-medium text-neutral-800">
                                Rp 4.500.000
                            </div>
                            <div class="text-xs text-success">Paid</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainPage>
</template>

<script setup>
import MainPage from '@/Components/UI/MainPage.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faArrowLeft, faBan, faSync } from '@fortawesome/free-solid-svg-icons';
import { Link } from '@inertiajs/vue3';
</script>
