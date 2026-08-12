<template>
    <MainPage>
        <template #header>
            <div
                class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white p-4 rounded-xl shadow-sm border border-neutral-200/60 gap-4 mb-4"
            >
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('cockpit.merchants.index')"
                        class="text-neutral-400 hover:text-neutral-600 transition-colors mr-2"
                    >
                        <FontAwesomeIcon :icon="faArrowLeft" class="text-xl" />
                    </Link>
                    <div
                        class="w-16 h-16 bg-neutral-100 rounded-xl flex items-center justify-center text-2xl font-bold text-neutral-400 border border-neutral-200 overflow-hidden"
                    >
                        <img
                            v-if="business.logo_url"
                            :src="business.logo_url"
                            alt="Logo"
                            class="w-full h-full object-cover"
                        />
                        <span v-else>{{
                            business.name.substring(0, 2).toUpperCase()
                        }}</span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl font-bold text-neutral-800">
                                {{ business.name }}
                            </h1>
                            <span
                                v-if="business.status === 'active'"
                                class="px-2 py-0.5 bg-success/10 text-success text-xs rounded-full font-medium"
                                >Active</span
                            >
                            <span
                                v-else
                                class="px-2 py-0.5 bg-danger/10 text-danger text-xs rounded-full font-medium"
                                >Suspended</span
                            >
                        </div>
                        <div class="text-sm text-neutral-500 mt-1">
                            ID:
                            <span class="font-mono">{{
                                business.id.substring(0, 8)
                            }}</span>
                            • Joined:
                            {{
                                new Date(
                                    business.created_at,
                                ).toLocaleDateString()
                            }}
                        </div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button
                        v-if="business.status === 'active'"
                        class="btn btn-outline-danger btn-sm"
                        @click="toggleStatus('suspended')"
                    >
                        <FontAwesomeIcon :icon="faBan" class="mr-2" />Suspend
                        Merchant
                    </button>
                    <button
                        v-else
                        class="btn btn-outline-success btn-sm"
                        @click="toggleStatus('active')"
                    >
                        <FontAwesomeIcon :icon="faCheck" class="mr-2" />Activate
                        Merchant
                    </button>
                </div>
            </div>

            <div
                class="bg-white rounded-xl shadow-sm border border-neutral-200/60"
            >
                <div class="border-b border-neutral-200 px-4">
                    <div class="flex gap-6 overflow-x-auto hide-scrollbar">
                        <button
                            class="px-1 py-3 text-sm font-medium border-b-2 border-main text-main"
                        >
                            Overview
                        </button>
                        <button
                            class="px-1 py-3 text-sm font-medium border-b-2 border-transparent text-neutral-500 hover:text-neutral-800 cursor-not-allowed opacity-50"
                        >
                            Subscription
                        </button>
                        <button
                            class="px-1 py-3 text-sm font-medium border-b-2 border-transparent text-neutral-500 hover:text-neutral-800 cursor-not-allowed opacity-50"
                        >
                            Outlets ({{ business.outlets_count }})
                        </button>
                        <button
                            class="px-1 py-3 text-sm font-medium border-b-2 border-transparent text-neutral-500 hover:text-neutral-800 cursor-not-allowed opacity-50"
                        >
                            Users ({{ business.users_count }})
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div
                class="bg-white border border-neutral-200/60 shadow-sm rounded-lg p-4"
            >
                <h3 class="font-bold text-neutral-800 mb-4">
                    Merchant Profile
                </h3>
                <div class="flex flex-col gap-3">
                    <div>
                        <div class="text-xs text-neutral-500">
                            Business Name
                        </div>
                        <div class="text-sm font-medium text-neutral-800">
                            {{ business.name }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-neutral-500">Owner Name</div>
                        <div class="text-sm font-medium text-neutral-800">
                            {{ business.owner_name || '-' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-neutral-500">Email</div>
                        <div class="text-sm font-medium text-neutral-800">
                            {{ business.email }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-neutral-500">Phone</div>
                        <div class="text-sm font-medium text-neutral-800">
                            {{ business.phone || '-' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-neutral-500">Address</div>
                        <div class="text-sm font-medium text-neutral-800">
                            {{ business.address || '-' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-neutral-500">
                            Business Type
                        </div>
                        <div class="text-sm font-medium text-neutral-800">
                            {{ business.type?.name || '-' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-4">
                <div class="bg-main/5 border border-main/20 rounded-lg p-4">
                    <h3 class="font-bold text-neutral-800 mb-2">
                        Current Subscription
                    </h3>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xl font-bold text-main"
                            >Trial / Free Plan</span
                        >
                        <span
                            class="text-xs font-medium bg-white px-2 py-1 rounded text-neutral-600 border border-neutral-200"
                            >Default</span
                        >
                    </div>
                    <div class="text-sm text-neutral-600 mb-4">
                        Trial ends:
                        {{
                            business.trial_end_at
                                ? new Date(
                                      business.trial_end_at,
                                  ).toLocaleDateString()
                                : 'Lifetime'
                        }}
                    </div>
                </div>

                <div
                    class="bg-white shadow-sm border border-neutral-200/60 rounded-lg p-4 flex gap-4"
                >
                    <div class="flex-1 text-center border-r border-neutral-200">
                        <div class="text-2xl font-bold text-neutral-800">
                            {{ business.outlets_count }}
                        </div>
                        <div class="text-xs text-neutral-500">
                            Active Outlets
                        </div>
                    </div>
                    <div class="flex-1 text-center border-r border-neutral-200">
                        <div class="text-2xl font-bold text-neutral-800">
                            {{ business.users_count }}
                        </div>
                        <div class="text-xs text-neutral-500">
                            Registered Users
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
import { faBan, faCheck, faArrowLeft } from '@fortawesome/free-solid-svg-icons';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    business: Object,
});

const toggleStatus = (newStatus) => {
    if (
        confirm(
            `Are you sure you want to change this merchant's status to ${newStatus}?`,
        )
    ) {
        router.post(
            route('cockpit.merchants.toggle-status', props.business.id),
            {
                status: newStatus,
            },
            {
                preserveScroll: true,
            },
        );
    }
};
</script>
