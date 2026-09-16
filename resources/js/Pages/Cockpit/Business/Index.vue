<template>
    <MainPage>
        <template #widgets>
            <BusinessWidgets :metrics="metrics" />
        </template>

        <template #header>
            <MainPageHeader
                title="Manajemen Merchant"
                description="Kelola data bisnis merchant, status akun, paket langganan aktif, dan seluruh cabang outlet"
            />

            <BusinessFilter :filters="filters" :business-types="businessTypes" />
        </template>

        <Table
            :headers="tableHeaders"
            :data="businesses.data"
            :action="true"
            :sort="typeof filters?.sort === 'string' ? filters.sort : 'created_at'"
            :sort-direction="typeof filters?.direction === 'string' ? filters.direction : 'desc'"
        >
            <template #name="{ row }">
                <div class="py-0.5">
                    <div
                        class="font-bold text-neutral-800 text-sm hover:text-main transition-colors flex items-center gap-1.5 flex-wrap"
                    >
                        <span>{{ row.name }}</span>
                        <span
                            v-if="row.type"
                            class="px-1.5 py-0.2 text-[9px] rounded font-semibold bg-neutral-100 text-neutral-600 border border-neutral-200"
                        >
                            {{ row.type.name }}
                        </span>
                    </div>
                    <div class="text-xs text-neutral-500 mt-0.5">{{ row.email }}</div>
                </div>
            </template>

            <template #owner_name="{ row }">
                <span class="text-xs font-medium text-neutral-700">
                    {{ row.owner_name || '-' }}
                </span>
            </template>

            <template #plan="{ row }">
                <div class="text-xs">
                    <div
                        v-if="
                            row.subscriptions &&
                            row.subscriptions.length > 0 &&
                            row.subscriptions[0].plan
                        "
                    >
                        <span class="font-bold text-neutral-800">
                            {{ row.subscriptions[0].plan.name }}
                        </span>
                    </div>
                    <div v-else-if="isTrialActive(row.trial_end_at)">
                        <span
                            class="px-2 py-0.5 text-[10px] rounded-full font-bold bg-amber-100 text-amber-800"
                        >
                            Masa Trial
                        </span>
                    </div>
                    <div v-else>
                        <span class="text-neutral-400 text-xs">-</span>
                    </div>
                </div>
            </template>

            <template #outlets="{ row }">
                <span class="text-xs font-medium text-neutral-700">
                    {{ row.outlets_count || 0 }} Cabang
                </span>
            </template>

            <template #status="{ row }">
                <span
                    class="px-2 py-0.5 text-[11px] rounded-full font-semibold inline-flex items-center gap-1"
                    :class="
                        row.status === $enums.BusinessStatus?.Active || row.status === 'active'
                            ? 'bg-success/10 text-success'
                            : 'bg-danger/10 text-danger'
                    "
                >
                    <span
                        class="w-1.5 h-1.5 rounded-full"
                        :class="
                            row.status === $enums.BusinessStatus?.Active || row.status === 'active'
                                ? 'bg-success'
                                : 'bg-danger'
                        "
                    ></span>
                    {{
                        $enums.BusinessStatus?._meta[row.status]?.label ||
                        (row.status === 'active' ? 'Aktif' : 'Ditangguhkan')
                    }}
                </span>
            </template>

            <template #created_at="{ row }">
                <span class="text-xs text-neutral-600">
                    {{ formatDate(row.created_at) }}
                </span>
            </template>

            <template #last_login_at="{ row }">
                <span v-if="row.users_max_last_login_at" class="text-xs text-neutral-600">
                    {{ formatDate(row.users_max_last_login_at) }}
                </span>
                <span v-else class="text-xs text-neutral-400">-</span>
            </template>

            <template #actions="{ row }">
                <div class="flex items-center gap-1 justify-end">
                    <button
                        type="button"
                        class="btn btn-outline-main btn-xs text-[11px] px-2 py-1"
                        title="Lihat Detail Merchant"
                        @click="openDetail(row.id)"
                    >
                        <FontAwesomeIcon :icon="faEye" class="mr-1 text-[10px]" />
                        Detail
                    </button>
                    <button
                        v-if="
                            row.status === $enums.BusinessStatus?.Active || row.status === 'active'
                        "
                        type="button"
                        class="btn btn-outline-danger btn-xs text-[11px] px-2 py-1"
                        title="Tangguhkan Akses Merchant"
                        @click="toggleStatus(row, 'suspended')"
                    >
                        <FontAwesomeIcon :icon="faBan" class="text-[10px]" />
                    </button>
                    <button
                        v-else
                        type="button"
                        class="btn btn-outline-success btn-xs text-[11px] px-2 py-1"
                        title="Aktifkan Kembali Merchant"
                        @click="toggleStatus(row, 'active')"
                    >
                        <FontAwesomeIcon :icon="faCheck" class="text-[10px]" />
                    </button>
                </div>
            </template>
        </Table>

        <template #footer>
            <Pagination :meta="businesses.meta || businesses" />
        </template>
    </MainPage>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import Table from '@/Components/Tables/Table.vue'
import Pagination from '@/Components/Tables/Pagination.vue'
import BusinessWidgets from './Components/BusinessWidgets.vue'
import BusinessFilter from './Components/BusinessFilter.vue'
import BusinessDetailPopUp from './Components/BusinessDetailPopUp.vue'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faEye, faBan, faCheck } from '@fortawesome/free-solid-svg-icons'
import { usePopUpStore } from '@/store/popup'
import { useModalStore } from '@/store/notification.js'

defineProps({
    businesses: {
        type: Object,
        default: () => ({ data: [] }),
    },
    metrics: {
        type: Object,
        default: () => ({}),
    },
    businessTypes: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
})

const popUpStore = usePopUpStore()
const modalStore = useModalStore()

const tableHeaders = [
    { field: 'name', label: 'Bisnis & Jenis', slot: 'name', sortable: true },
    { field: 'owner_name', label: 'Pemilik', slot: 'owner_name', sortable: true },
    { field: 'plan', label: 'Paket Langganan', slot: 'plan' },
    { field: 'outlets_count', label: 'Outlet', slot: 'outlets' },
    { field: 'status', label: 'Status Akun', slot: 'status', sortable: true },
    {
        field: 'created_at',
        label: 'Terdaftar Pada',
        slot: 'created_at',
        sortable: true,
    },
    { field: 'users_max_last_login_at', label: 'Aktivitas Terakhir', slot: 'last_login_at' },
]

const formatDate = val => {
    if (!val) return '-'
    const d = new Date(val)
    return isNaN(d.getTime())
        ? '-'
        : d.toLocaleDateString('id-ID', {
              day: 'numeric',
              month: 'short',
              year: 'numeric',
          })
}

const isTrialActive = trialEndAt => {
    if (!trialEndAt) return false
    return new Date(trialEndAt).getTime() > Date.now()
}

const openDetail = id => {
    popUpStore.open({
        title: 'Detail Bisnis Merchant',
        size: '2xl',
        component: BusinessDetailPopUp,
        props: { businessId: id },
    })
}

const toggleStatus = (business, newStatus) => {
    const isSuspending = newStatus === 'suspended'
    modalStore.confirm({
        title: isSuspending ? 'Tangguhkan Merchant' : 'Aktifkan Merchant',
        message: isSuspending
            ? `Apakah Anda yakin ingin menangguhkan merchant "${business.name}"? Merchant dan seluruh outletnya tidak akan dapat mengakses aplikasi.`
            : `Apakah Anda yakin ingin mengaktifkan kembali merchant "${business.name}"?`,
        type: isSuspending ? 'danger' : 'info',
        confirmText: isSuspending ? 'Ya, Tangguhkan' : 'Ya, Aktifkan',
        cancelText: 'Batal',
        confirmClass: isSuspending
            ? 'btn-danger bg-rose-600 hover:bg-rose-700 text-white'
            : 'btn-main',
        onConfirm: () => {
            router.post(
                route('cockpit.merchants.toggle-status', business.id),
                {
                    status: newStatus,
                },
                {
                    preserveScroll: true,
                }
            )
        },
    })
}
</script>
