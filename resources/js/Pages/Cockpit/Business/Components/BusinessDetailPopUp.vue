<template>
    <div v-if="loading" class="flex justify-center items-center h-48">
        <div class="animate-pulse flex flex-col items-center gap-2">
            <div
                class="w-8 h-8 border-4 border-main border-t-transparent rounded-full animate-spin"
            ></div>
            <span class="text-sm text-neutral-500">Memuat detail merchant...</span>
        </div>
    </div>

    <div v-else-if="business" class="space-y-3">
        <!-- Header Info Bisnis -->
        <div
            class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-neutral-50 p-3 rounded-xl border border-neutral-200/70 gap-3"
        >
            <div class="flex items-center gap-3">
                <div
                    class="w-14 h-14 bg-white rounded-xl flex items-center justify-center text-xl font-bold text-neutral-400 border border-neutral-200 overflow-hidden shrink-0 shadow-xs"
                >
                    <img
                        v-if="business.logo_url"
                        :src="business.logo_url"
                        alt="Logo"
                        class="w-full h-full object-cover"
                    />
                    <span v-else>{{ (business.name || 'MB').substring(0, 2).toUpperCase() }}</span>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-lg font-bold text-neutral-800">{{ business.name }}</h1>
                        <span
                            class="px-2 py-0.5 text-xs rounded-full font-medium"
                            :class="
                                business.status === $enums.BusinessStatus?.Active
                                    ? 'bg-success/10 text-success'
                                    : 'bg-danger/10 text-danger'
                            "
                        >
                            {{
                                $enums.BusinessStatus?._meta[business.status]?.label ||
                                (business.status === 'active' ? 'Aktif' : 'Ditangguhkan')
                            }}
                        </span>
                        <span
                            v-if="business.type"
                            class="px-2 py-0.5 text-xs rounded-full font-medium bg-neutral-200 text-neutral-700"
                        >
                            {{ business.type.name }}
                        </span>
                    </div>
                    <div class="text-xs text-neutral-500 mt-0.5 flex items-center gap-2 flex-wrap">
                        <span
                            >ID:
                            <code class="font-mono">{{ business.id?.substring(0, 8) }}</code></span
                        >
                        <span>•</span>
                        <span>Terdaftar: {{ formatDate(business.created_at) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Navigasi -->
        <div class="border-b border-neutral-200">
            <div class="flex gap-4 overflow-x-auto hide-scrollbar">
                <button
                    type="button"
                    class="px-2 py-2.5 text-xs font-bold border-b-2 transition-colors cursor-pointer"
                    :class="
                        activeTab === 'overview'
                            ? 'border-main text-main'
                            : 'border-transparent text-neutral-500 hover:text-neutral-800'
                    "
                    @click="activeTab = 'overview'"
                >
                    Ringkasan & Langganan
                </button>
                <button
                    type="button"
                    class="px-2 py-2.5 text-xs font-bold border-b-2 transition-colors cursor-pointer"
                    :class="
                        activeTab === 'outlets'
                            ? 'border-main text-main'
                            : 'border-transparent text-neutral-500 hover:text-neutral-800'
                    "
                    @click="activeTab = 'outlets'"
                >
                    Daftar Outlet ({{ business.outlets_count || 0 }})
                </button>
                <button
                    type="button"
                    class="px-2 py-2.5 text-xs font-bold border-b-2 transition-colors cursor-pointer"
                    :class="
                        activeTab === 'users'
                            ? 'border-main text-main'
                            : 'border-transparent text-neutral-500 hover:text-neutral-800'
                    "
                    @click="activeTab = 'users'"
                >
                    Pengguna & Tim ({{ business.users_count || 0 }})
                </button>
            </div>
        </div>

        <!-- TAB 1: Ringkasan & Langganan -->
        <div v-if="activeTab === 'overview'" class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <!-- Kolom Kiri: Profil Bisnis -->
            <div class="bg-white border border-neutral-200/70 rounded-xl p-3 space-y-2">
                <h3
                    class="font-bold text-sm text-neutral-800 pb-1 border-b border-neutral-100 flex items-center gap-1.5"
                >
                    <FontAwesomeIcon :icon="faBuilding" class="text-neutral-400 text-xs" />
                    Profil Bisnis Merchant
                </h3>
                <div class="space-y-2 text-xs">
                    <div>
                        <div class="text-neutral-400 font-medium">Nama Bisnis</div>
                        <div class="font-semibold text-neutral-800">{{ business.name }}</div>
                    </div>
                    <div>
                        <div class="text-neutral-400 font-medium">Nama Pemilik (Owner)</div>
                        <div class="font-semibold text-neutral-800">
                            {{ business.owner_name || '-' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-neutral-400 font-medium">Email Kontak</div>
                        <div class="font-semibold text-neutral-800">{{ business.email }}</div>
                    </div>
                    <div>
                        <div class="text-neutral-400 font-medium">Nomor Telepon</div>
                        <div class="font-semibold text-neutral-800">
                            {{ business.phone || '-' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-neutral-400 font-medium">Alamat Utama</div>
                        <div class="font-semibold text-neutral-800">
                            {{ business.address || '-' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-neutral-400 font-medium">Jenis Bisnis</div>
                        <div class="font-semibold text-neutral-800">
                            {{ business.type?.name || '-' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Detail Paket Langganan Asli -->
            <div class="space-y-3">
                <div
                    class="border rounded-xl p-3 space-y-2.5 transition-all"
                    :class="
                        business.active_plan?.type === 'paid'
                            ? 'bg-main/5 border-main/30'
                            : business.active_plan?.type === 'trial'
                              ? 'bg-amber-50/70 border-amber-200'
                              : 'bg-neutral-50 border-neutral-200'
                    "
                >
                    <div class="flex justify-between items-start gap-2">
                        <div>
                            <div
                                class="text-[11px] font-semibold uppercase tracking-wider text-neutral-500"
                            >
                                Paket Langganan Saat Ini
                            </div>
                            <div
                                class="text-base font-bold text-neutral-800 mt-0.5 flex items-center gap-1.5"
                            >
                                <span>{{
                                    business.active_plan?.plan_name || 'Tidak Ada Paket'
                                }}</span>
                                <span
                                    v-if="business.active_plan?.is_custom"
                                    class="px-1.5 py-0.2 bg-purple-100 text-purple-700 text-[9px] rounded font-bold uppercase tracking-wider"
                                >
                                    Kustom
                                </span>
                            </div>
                        </div>
                        <span
                            class="px-2 py-0.5 text-xs rounded-full font-bold"
                            :class="
                                business.active_plan?.type === 'paid'
                                    ? 'bg-success/10 text-success'
                                    : business.active_plan?.type === 'trial'
                                      ? 'bg-amber-100 text-amber-700'
                                      : 'bg-neutral-200 text-neutral-600'
                            "
                        >
                            {{ business.active_plan?.status_label || 'Nonaktif' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs pt-1 border-t border-neutral-200/50">
                        <div>
                            <div class="text-neutral-400">Siklus Penagihan</div>
                            <div class="font-semibold text-neutral-800 capitalize">
                                {{
                                    business.active_plan?.billing_cycle === 'yearly'
                                        ? 'Tahunan'
                                        : business.active_plan?.billing_cycle === 'monthly'
                                          ? 'Bulanan'
                                          : business.active_plan?.billing_cycle || '-'
                                }}
                            </div>
                        </div>
                        <div>
                            <div class="text-neutral-400">Harga per Outlet</div>
                            <div class="font-semibold text-neutral-800">
                                {{
                                    business.active_plan?.type === 'paid'
                                        ? `${formatIDR(business.active_plan.price_per_outlet)} / bln`
                                        : 'Rp 0'
                                }}
                            </div>
                        </div>
                        <div class="col-span-2">
                            <div class="text-neutral-400">Masa Berlaku / Berakhir</div>
                            <div class="font-semibold text-neutral-800 flex items-center gap-1.5">
                                <span>{{
                                    business.active_plan?.expired_at
                                        ? formatDate(business.active_plan.expired_at)
                                        : 'Tidak Terbatas / Belum Diatur'
                                }}</span>
                                <span
                                    v-if="
                                        business.active_plan?.type === 'trial' &&
                                        business.active_plan?.trial_days_left !== undefined
                                    "
                                    class="text-[10px] px-1.5 py-0.5 rounded font-bold bg-amber-200/70 text-amber-800"
                                >
                                    Sisa {{ business.active_plan.trial_days_left }} hari
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Fitur Sistem yang Aktif -->
                    <div
                        v-if="
                            business.active_plan?.system_features &&
                            business.active_plan.system_features.length > 0
                        "
                        class="pt-1 border-t border-neutral-200/50"
                    >
                        <div
                            class="text-[11px] font-semibold text-neutral-600 mb-1 flex items-center justify-between"
                        >
                            <span
                                >Fitur Sistem Terbuka ({{
                                    business.active_plan.system_features.length
                                }})</span
                            >
                            <button
                                type="button"
                                class="text-main hover:underline text-[10px]"
                                @click="showAllFeatures = !showAllFeatures"
                            >
                                {{ showAllFeatures ? 'Sembunyikan' : 'Lihat Semua' }}
                            </button>
                        </div>
                        <div class="flex flex-wrap gap-1">
                            <span
                                v-for="feat in showAllFeatures
                                    ? business.active_plan.system_features
                                    : business.active_plan.system_features.slice(0, 6)"
                                :key="feat.id || feat.code"
                                class="px-1.5 py-0.5 bg-white text-neutral-700 border border-neutral-200 rounded text-[10px] font-medium"
                            >
                                {{ feat.name }}
                            </span>
                            <span
                                v-if="
                                    !showAllFeatures &&
                                    business.active_plan.system_features.length > 6
                                "
                                class="px-1.5 py-0.5 bg-neutral-100 text-neutral-500 rounded text-[10px] font-medium cursor-pointer"
                                @click="showAllFeatures = true"
                            >
                                +{{ business.active_plan.system_features.length - 6 }} lainnya
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Kartu Ringkasan Metrik -->
                <div
                    class="bg-white border border-neutral-200/70 rounded-xl p-3 flex divide-x divide-neutral-200 shadow-2xs"
                >
                    <div class="flex-1 text-center pr-2">
                        <div class="text-xl font-bold text-neutral-800">
                            {{ business.outlets_count || 0 }}
                        </div>
                        <div class="text-[11px] text-neutral-500">Cabang Outlet</div>
                    </div>
                    <div class="flex-1 text-center px-2">
                        <div class="text-xl font-bold text-neutral-800">
                            {{ business.users_count || 0 }}
                        </div>
                        <div class="text-[11px] text-neutral-500">User Terdaftar</div>
                    </div>
                    <div class="flex-1 text-center pl-2">
                        <div class="text-xl font-bold text-neutral-800">
                            {{ business.invoices_count || 0 }}
                        </div>
                        <div class="text-[11px] text-neutral-500">Total Invoice</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: Daftar Outlet -->
        <div v-else-if="activeTab === 'outlets'" class="space-y-2">
            <Table :headers="outletHeaders" :data="business.outlets || []" :action="false">
                <template #name="{ row }">
                    <div>
                        <div class="font-bold text-neutral-800 text-xs flex items-center gap-1.5">
                            <span>{{ row.name }}</span>
                            <span
                                v-if="row.is_main_outlet"
                                class="px-1.5 py-0.2 bg-sky-100 text-sky-700 text-[9px] rounded font-bold uppercase tracking-wider"
                            >
                                Utama
                            </span>
                        </div>
                        <div class="text-[11px] text-neutral-400 mt-0.5">
                            {{ row.timezone || 'Asia/Jakarta' }} • {{ row.currency_code || 'IDR' }}
                        </div>
                    </div>
                </template>
                <template #contact="{ row }">
                    <div class="text-xs text-neutral-700">
                        <div>{{ row.phone || '-' }}</div>
                        <div class="text-neutral-400 text-[11px]">{{ row.email || '-' }}</div>
                    </div>
                </template>
                <template #address="{ row }">
                    <span
                        class="text-xs text-neutral-600 line-clamp-2 max-w-xs"
                        :title="row.address"
                    >
                        {{ row.address || '-' }}
                    </span>
                </template>
                <template #status="{ row }">
                    <div class="flex flex-col gap-1 items-start">
                        <span
                            class="px-2 py-0.5 text-[10px] rounded-full font-semibold"
                            :class="
                                row.is_active
                                    ? 'bg-success/10 text-success'
                                    : 'bg-neutral-200 text-neutral-600'
                            "
                        >
                            {{ row.is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                        <span
                            v-if="row.is_stock_frozen"
                            class="px-1.5 py-0.2 text-[9px] rounded font-bold bg-amber-100 text-amber-800"
                        >
                            Stok Beku
                        </span>
                    </div>
                </template>
                <template #created_at="{ row }">
                    <span class="text-xs text-neutral-500">
                        {{ formatDate(row.created_at) }}
                    </span>
                </template>
            </Table>
        </div>

        <!-- TAB 3: Pengguna & Tim -->
        <div v-else-if="activeTab === 'users'" class="space-y-2">
            <Table :headers="userHeaders" :data="business.users || []" :action="true">
                <template #user_info="{ row }">
                    <div>
                        <div class="font-bold text-neutral-800 text-xs flex items-center gap-1.5">
                            <span>{{ row.name }}</span>
                            <span
                                v-if="row.is_root_user"
                                class="px-1.5 py-0.2 bg-purple-100 text-purple-700 text-[9px] rounded font-bold uppercase tracking-wider"
                            >
                                Root Owner
                            </span>
                        </div>
                        <div class="text-[11px] text-neutral-400">{{ row.email }}</div>
                    </div>
                </template>
                <template #role="{ row }">
                    <div class="flex flex-wrap gap-1">
                        <span
                            v-for="r in row.roles"
                            :key="r.id"
                            class="px-2 py-0.5 bg-neutral-100 text-neutral-700 text-[10px] rounded-full font-medium"
                        >
                            {{ r.label || r.name }}
                        </span>
                        <span
                            v-if="!row.roles || row.roles.length === 0"
                            class="text-neutral-400 text-xs"
                            >-</span
                        >
                    </div>
                </template>
                <template #last_login="{ row }">
                    <span v-if="row.last_login_at" class="text-xs text-neutral-600">
                        {{ formatDate(row.last_login_at) }}
                    </span>
                    <span v-else class="text-xs text-neutral-400">-</span>
                </template>
                <template #actions="{ row }">
                    <a
                        :href="
                            route('cockpit.merchants.impersonate', {
                                id: business.id,
                                userId: row.id,
                            })
                        "
                        target="_blank"
                        class="btn btn-outline-main btn-xs text-[11px] inline-flex items-center gap-1.5"
                        title="Masuk ke aplikasi sebagai user ini"
                    >
                        <FontAwesomeIcon :icon="faRightToBracket" class="text-[10px]" />
                        <span>Login as User</span>
                    </a>
                </template>
            </Table>
        </div>

        <Teleport v-if="isMounted" to="#popUpFooter">
            <button type="button" class="btn btn-slate-400" @click="close">Tutup</button>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Table from '@/Components/Tables/Table.vue'
import { formatIDR } from '@/Composable/currency-format'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faBuilding, faRightToBracket } from '@fortawesome/free-solid-svg-icons'
import { usePopUpStore } from '@/store/popup'
import axios from 'axios'

const props = defineProps({
    businessId: {
        type: String,
        required: true,
    },
})

const popUpStore = usePopUpStore()
const business = ref(null)
const loading = ref(true)
const activeTab = ref('overview')
const showAllFeatures = ref(false)
const isMounted = ref(false)

const outletHeaders = [
    { field: 'name', label: 'Nama Outlet & Info', slot: 'name' },
    { field: 'contact', label: 'Kontak', slot: 'contact' },
    { field: 'address', label: 'Alamat', slot: 'address' },
    { field: 'status', label: 'Status', slot: 'status' },
    { field: 'created_at', label: 'Dibuat', slot: 'created_at' },
]

const userHeaders = [
    { field: 'name', label: 'Pengguna', slot: 'user_info' },
    { field: 'role', label: 'Peran / Jabatan', slot: 'role' },
    { field: 'last_login_at', label: 'Aktivitas Terakhir', slot: 'last_login' },
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

onMounted(async () => {
    isMounted.value = true
    try {
        const response = await axios.get(route('cockpit.merchants.show', props.businessId))
        business.value = response.data
    } catch (error) {
        console.error('Failed to load business details:', error)
    } finally {
        loading.value = false
    }
})

const close = () => {
    popUpStore.close()
}
</script>
