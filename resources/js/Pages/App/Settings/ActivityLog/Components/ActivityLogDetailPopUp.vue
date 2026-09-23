<template>
    <div class="space-y-3 flex-1 overflow-y-auto">
        <!-- Loading State -->
        <div
            v-if="loading"
            class="py-12 flex flex-col items-center justify-center gap-2 text-slate-400"
        >
            <FontAwesomeIcon :icon="faSpinner" class="animate-spin text-2xl text-main" />
            <span class="text-xs">Memuat detail log audit...</span>
        </div>

        <template v-else-if="detail">
            <!-- Header / Profil Pelaku Section -->
            <div
                class="bg-white p-3 rounded-xl border border-slate-200 flex flex-col sm:flex-row gap-3 items-start sm:items-center"
            >
                <div
                    class="w-10 h-10 bg-main/10 text-main rounded-full flex items-center justify-center text-base font-bold shrink-0"
                >
                    {{ getInitials(detail.causer?.name || 'Sistem') }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="text-sm font-bold text-slate-800 truncate">
                            {{ detail.causer?.name || 'Sistem Otomatis' }}
                        </h3>
                        <span v-if="detail.causer?.email" class="text-xs text-slate-500">
                            ({{ detail.causer.email }})
                        </span>
                        <span class="badge badge-info text-[10px]">
                            {{ formatModule(detail.module) }}
                        </span>
                    </div>
                    <div class="text-slate-500 mt-1 flex flex-wrap items-center gap-3 text-xs">
                        <span class="flex items-center gap-1">
                            <FontAwesomeIcon :icon="faStore" class="text-slate-400 text-xs" />
                            {{ detail.outlet?.name || 'Pusat / Semua Outlet' }}
                        </span>
                        <span class="flex items-center gap-1">
                            <FontAwesomeIcon :icon="faClock" class="text-slate-400 text-xs" />
                            {{ formatDate(detail.created_at) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Ringkasan Aktivitas -->
            <div class="bg-white p-3 rounded-xl border border-slate-200 space-y-1.5">
                <span
                    class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block"
                >
                    Keterangan Aktivitas
                </span>
                <p class="text-sm font-medium text-slate-800 leading-relaxed">
                    {{ detail.description }}
                </p>
                <div class="flex items-center gap-2 pt-1">
                    <span
                        class="text-xs text-slate-500 font-mono bg-slate-100 px-2 py-0.5 rounded border border-slate-200"
                    >
                        {{ detail.action }}
                    </span>
                    <span v-if="detail.subject_type" class="text-xs text-slate-400">
                        Subjek: {{ formatSubjectType(detail.subject_type) }} #{{
                            detail.subject_id
                        }}
                    </span>
                </div>
            </div>

            <!-- Rincian Perubahan Data (Diff Viewer) -->
            <div v-if="hasDiff" class="bg-white p-3 rounded-xl border border-slate-200 space-y-2">
                <span
                    class="text-[11px] font-semibold text-slate-700 uppercase tracking-wider block"
                >
                    Perubahan Data
                </span>
                <div class="border border-slate-200 rounded-lg overflow-hidden">
                    <table class="w-full text-xs text-left">
                        <thead
                            class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold"
                        >
                            <tr>
                                <th class="py-2 px-3">Field / Kolom</th>
                                <th class="py-2 px-3">Nilai Sebelum</th>
                                <th class="py-2 px-3">Nilai Sesudah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="item in diffItems"
                                :key="item.key"
                                class="hover:bg-slate-50/50"
                            >
                                <td class="py-2 px-3 font-mono font-medium text-slate-700">
                                    {{ item.key }}
                                </td>
                                <td class="py-2 px-3 text-danger bg-danger/5 font-mono">
                                    {{ formatValue(item.old) }}
                                </td>
                                <td class="py-2 px-3 text-success bg-success/5 font-mono">
                                    {{ formatValue(item.new) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Metadata Perangkat & Jaringan -->
            <div class="bg-white p-3 rounded-xl border border-slate-200 space-y-2">
                <span
                    class="text-[11px] font-semibold text-slate-700 uppercase tracking-wider block"
                >
                    Informasi Jaringan & Perangkat
                </span>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                    <div>
                        <span class="text-slate-400 text-[11px] block">Alamat IP</span>
                        <span class="font-mono text-slate-700 font-medium">
                            {{ detail.ip_address || '127.0.0.1' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-400 text-[11px] block">Browser / User Agent</span>
                        <span class="text-slate-600 truncate block" :title="detail.user_agent">
                            {{ detail.user_agent || '-' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Raw Payload Data (Collapsible) -->
            <div
                v-if="detail.properties"
                class="bg-white p-3 rounded-xl border border-slate-200 space-y-2"
            >
                <button
                    type="button"
                    class="flex items-center justify-between w-full text-[11px] font-semibold text-slate-500 hover:text-slate-800 transition cursor-pointer"
                    @click="showRawJson = !showRawJson"
                >
                    <span>PAYLOAD DATA LENGKAP (RAW JSON)</span>
                    <FontAwesomeIcon
                        :icon="showRawJson ? faChevronUp : faChevronDown"
                        class="text-xs"
                    />
                </button>
                <div v-if="showRawJson" class="mt-2">
                    <pre
                        class="bg-slate-900 text-slate-100 p-3 rounded-lg text-[11px] font-mono overflow-x-auto max-h-60 leading-relaxed"
                        >{{ JSON.stringify(detail.properties, null, 2) }}</pre>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faClock,
    faSpinner,
    faStore,
    faChevronDown,
    faChevronUp,
} from '@fortawesome/free-solid-svg-icons'
import { formatDateTimeID } from '@/Composable/date'

const props = defineProps({
    logId: {
        type: String,
        required: true,
    },
    initialData: {
        type: Object,
        default: null,
    },
})

const loading = ref(false)
const detail = ref(props.initialData)
const showRawJson = ref(false)

const loadDetail = async () => {
    if (detail.value && detail.value.properties !== undefined) {
        return
    }

    loading.value = true
    try {
        const response = await axios.get(route('settings.activity-logs.show', props.logId))
        detail.value = response.data.data || response.data
    } catch (error) {
        console.error('Gagal memuat detail log audit:', error)
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    loadDetail()
})

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
        return formatDateTimeID(dateString) + ' WIB'
    } catch {
        return dateString
    }
}

const formatModule = moduleKey => {
    const map = {
        pos_and_transactions: 'Penjualan & Kasir',
        products_and_menu: 'Produk & Menu',
        inventory_and_supply: 'Inventori & Rantai Pasok',
        promotions_and_crm: 'Promosi & Pelanggan',
        employees_and_roles: 'Pegawai & Hak Akses',
        settings_and_business: 'Pengaturan & Bisnis',
        auth_and_security: 'Keamanan & Autentikasi',
    }
    return map[moduleKey] || moduleKey
}

const formatSubjectType = type => {
    if (!type) return ''
    return type.split('\\').pop()
}

const formatValue = val => {
    if (val === null || val === undefined) return '-'
    if (typeof val === 'boolean') return val ? 'true' : 'false'
    if (typeof val === 'object') return JSON.stringify(val)
    return String(val)
}

const hasDiff = computed(() => {
    const propsData = detail.value?.properties
    return Boolean(propsData && (propsData.old || propsData.new))
})

const diffItems = computed(() => {
    const propsData = detail.value?.properties
    if (!propsData) return []

    const oldVals = propsData.old || {}
    const newVals = propsData.new || {}

    const allKeys = Array.from(new Set([...Object.keys(oldVals), ...Object.keys(newVals)]))

    return allKeys.map(key => ({
        key,
        old: oldVals[key],
        new: newVals[key],
    }))
})
</script>
