<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Log Aktivitas & Jejak Audit"
                description="Pantau riwayat aktivitas operasional penting, perubahan data, dan akses pengguna di tokomu."
            />
        </template>

        <template #widgets>
            <ActivityLogWidgets :stats="stats" />
        </template>

        <template #filter>
            <ActivityLogFilter :filters="filters" :modules="modules" :outlets="outlets" />
        </template>

        <FeatureLock
            :feature="$enums.FeatureEnum.AUDIT_LOGS"
            class="h-full flex-1 min-h-0 flex flex-col"
            content-class="h-full flex-1 min-h-0 flex flex-col"
        >
            <div class="h-full flex-1 min-h-0 flex flex-col">
                <Table
                    :headers="headers"
                    :data="logs.data"
                    :sort="filters?.sort || 'created_at'"
                    :sort-direction="filters?.direction || 'desc'"
                    :action="false"
                    @row-click="openDetail"
                >
                    <!-- Kolom Waktu -->
                    <template #created_at="{ row }">
                        <div class="flex flex-col">
                            <span class="font-medium text-neutral-800 text-xs">
                                {{ formatDate(row.created_at) }}
                            </span>
                            <span class="text-[11px] text-neutral-400">
                                {{ formatTime(row.created_at) }} WIB
                            </span>
                        </div>
                    </template>

                    <!-- Kolom Pelaku -->
                    <template #causer="{ row }">
                        <div class="flex items-center gap-2">
                            <div
                                class="w-7 h-7 bg-main/10 text-main rounded-full flex items-center justify-center text-[11px] font-bold shrink-0"
                            >
                                {{ getInitials(row.causer?.name || 'S') }}
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span class="font-medium text-neutral-800 truncate text-xs">
                                    {{ row.causer?.name || 'Sistem' }}
                                </span>
                                <span class="text-[11px] text-neutral-400 truncate">
                                    {{ row.causer?.email || '-' }}
                                </span>
                            </div>
                        </div>
                    </template>

                    <!-- Kolom Outlet -->
                    <template #outlet="{ row }">
                        <span class="text-xs text-neutral-600">
                            {{ row.outlet?.name || 'Pusat / Semua' }}
                        </span>
                    </template>

                    <!-- Kolom Modul -->
                    <template #module="{ row }">
                        <span class="badge badge-neutral-200 text-[10px] font-medium">
                            {{ formatModule(row.module) }}
                        </span>
                    </template>

                    <!-- Kolom Aksi & Keterangan -->
                    <template #action_description="{ row }">
                        <div class="flex flex-col gap-0.5">
                            <span class="text-xs font-medium text-neutral-900 leading-snug">
                                {{ row.description }}
                            </span>
                            <span class="text-[11px] font-mono text-neutral-400">
                                {{ row.action }}
                            </span>
                        </div>
                    </template>

                    <!-- Kolom IP / Jaringan -->
                    <template #ip_address="{ row }">
                        <span class="text-[11px] font-mono text-neutral-500">
                            {{ row.ip_address || '-' }}
                        </span>
                    </template>
                </Table>
            </div>
        </FeatureLock>

        <template #footer>
            <Pagination :links="logs.links" :meta="logs" />
        </template>
    </MainPage>
</template>

<script setup>
import { formatDateID } from '@/Composable/date'
import { usePopUpStore } from '@/store/popup'
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import Table from '@/Components/Tables/Table.vue'
import Pagination from '@/Components/Tables/Pagination.vue'
import FeatureLock from '@/Components/UI/FeatureLock.vue'
import ActivityLogFilter from './Components/ActivityLogFilter.vue'
import ActivityLogWidgets from './Components/ActivityLogWidgets.vue'
import ActivityLogDetailPopUp from './Components/ActivityLogDetailPopUp.vue'

defineProps({
    logs: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    outlets: {
        type: Array,
        default: () => [],
    },
    modules: {
        type: Object,
        default: () => ({}),
    },
    stats: {
        type: Object,
        default: () => ({
            total_activities: 0,
            sensitive_actions_count: 0,
        }),
    },
})

const headers = [
    { field: 'created_at', label: 'Waktu', slot: 'created_at', sortable: true },
    { field: 'causer', label: 'Pelaku', slot: 'causer' },
    { field: 'outlet', label: 'Outlet', slot: 'outlet' },
    { field: 'module', label: 'Modul', slot: 'module', sortable: true },
    { field: 'action_description', label: 'Aksi & Keterangan', slot: 'action_description' },
    { field: 'ip_address', label: 'IP Address', slot: 'ip_address', show: 'lg' },
]

const popUpStore = usePopUpStore()

const openDetail = row => {
    popUpStore.open({
        title: 'Detail Jejak Audit',
        size: 'xl',
        component: ActivityLogDetailPopUp,
        props: {
            logId: row.id,
            initialData: row,
        },
    })
}

const getInitials = name => {
    if (!name) return 'S'
    return name
        .split(' ')
        .map(n => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase()
}

const formatDate = dateString => {
    if (!dateString) return '-'
    try {
        return formatDateID(dateString)
    } catch {
        return dateString
    }
}

const formatTime = dateString => {
    if (!dateString) return ''
    try {
        const d = new Date(dateString)
        const jam = String(d.getHours()).padStart(2, '0')
        const menit = String(d.getMinutes()).padStart(2, '0')
        const detik = String(d.getSeconds()).padStart(2, '0')
        return `${jam}:${menit}:${detik}`
    } catch {
        return ''
    }
}

const formatModule = moduleKey => {
    const map = {
        pos_and_transactions: 'Penjualan & Kasir',
        products_and_menu: 'Produk & Menu',
        inventory_and_supply: 'Inventori & Pasok',
        promotions_and_crm: 'Promosi & Pelanggan',
        employees_and_roles: 'Pegawai & Hak Akses',
        settings_and_business: 'Pengaturan & Bisnis',
        auth_and_security: 'Keamanan & Auth',
    }
    return map[moduleKey] || moduleKey
}
</script>
