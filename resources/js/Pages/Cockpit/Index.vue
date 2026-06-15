<template>
    <div class="flex h-screen w-screen bg-white text-slate-900">
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-20 bg-black/20 backdrop-blur-sm lg:hidden"
            @click="sidebarOpen = false"
        />

        <aside
            class="fixed inset-y-0 left-0 z-30 flex w-full max-w-72 shrink-0 -translate-x-full flex-col border-r border-slate-200 bg-white/95 backdrop-blur-md transition-transform duration-300 lg:static lg:max-w-none lg:translate-x-0 xl:w-72"
            :class="{ 'translate-x-0': sidebarOpen }"
        >
                <div class="flex h-full flex-col">
                <div class="flex min-h-16 items-center justify-between gap-3 border-b border-slate-200 px-4">
                    <div class="flex items-center gap-3">
                        <img src="/img/icon-colored.png" alt="Sollu" class="h-9 w-9" />
                        <div>
                            <div class="text-base font-bold leading-tight">Sollu Cockpit</div>
                            <div class="text-xs text-main">Platform Control Center</div>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 hover:bg-main/10 hover:text-main lg:hidden"
                        @click="sidebarOpen = false"
                    >
                        <FontAwesomeIcon :icon="faXmark" />
                    </button>
                </div>

                <nav class="sidebar-navigation flex-1 p-2">
                    <div class="navigation-list">
                        <button
                            v-for="item in navigation"
                            :key="item.id"
                            type="button"
                            class="nav-item w-full"
                            :class="
                                activeSection === item.id
                                    ? 'bg-main text-white shadow-sm'
                                    : 'text-slate-600 hover:bg-main/5 hover:text-main'
                            "
                            @click="
                                activeSection = item.id;
                                sidebarOpen = false;
                            "
                        >
                            <FontAwesomeIcon :icon="item.icon" class="min-w-8 text-base" />
                            <span class="nav-item-label">{{ item.label }}</span>
                        </button>
                    </div>
                </nav>

                <div class="border-t border-slate-200 p-3">
                    <div class="rounded-lg border border-main/20 bg-main/5 p-3">
                        <div class="flex items-center gap-2 text-sm font-semibold text-main">
                            <FontAwesomeIcon :icon="faShieldHalved" />
                            Internal only
                        </div>
                        <p class="mt-1 text-xs leading-5 text-slate-600">
                            Cockpit menggunakan guard dan role terpisah dari dashboard merchant.
                        </p>
                    </div>
                </div>
            </div>
        </aside>

        <div class="grow flex flex-col h-screen overflow-hidden">
            <header
                class="flex min-h-16 items-center gap-2 bg-white/95 px-2.5 py-2 backdrop-blur-md"
            >
                <button
                    type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-main/10 hover:text-main lg:hidden"
                    @click="sidebarOpen = true"
                >
                    <FontAwesomeIcon :icon="faBars" />
                </button>
                <div class="min-w-0 grow">
                    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-500">
                        <span>Cockpit</span>
                        <span>/</span>
                        <span class="text-main">{{ activeNavigation.label }}</span>
                    </div>
                    <h1 class="truncate text-xl font-bold text-slate-950">
                        {{ activeNavigation.title }}
                    </h1>
                </div>

                <div class="hidden items-center gap-2 md:flex">
                    <label
                        class="flex min-h-10 items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-500"
                    >
                        <FontAwesomeIcon :icon="faMagnifyingGlass" />
                        <input
                            v-model="query"
                            type="search"
                            class="w-64 border-0 bg-transparent p-0 text-sm text-slate-900 placeholder:text-slate-400 focus:ring-0"
                            placeholder="Cari merchant, invoice, outlet"
                        />
                    </label>
                    <button
                        type="button"
                        class="btn border-slate-200 bg-white text-slate-700"
                        title="Export report"
                    >
                        <FontAwesomeIcon :icon="faDownload" />
                        Export
                    </button>
                    <button
                        type="button"
                        class="btn bg-main text-white"
                        title="Create internal note"
                    >
                        <FontAwesomeIcon :icon="faPlus" />
                        Note
                    </button>
                </div>
            </header>

            <main
                class="flex-1 relative overflow-hidden px-2.5 py-2.5 bg-slate-50 border border-slate-200 rounded-tl-lg"
            >
                <div class="flex h-full flex-col gap-3">
                    <div class="flex flex-col gap-3 rounded-lg border border-main/10 bg-white p-3 md:hidden">
                        <div>
                            <label
                                class="flex min-h-10 items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-500"
                            >
                                <FontAwesomeIcon :icon="faMagnifyingGlass" />
                                <input
                                    v-model="query"
                                    type="search"
                                    class="w-full border-0 bg-transparent p-0 text-sm text-slate-900 placeholder:text-slate-400 focus:ring-0"
                                    placeholder="Cari merchant, invoice, outlet"
                                />
                            </label>
                        </div>

                        <div class="flex gap-2 overflow-x-auto">
                            <button
                                v-for="item in navigation"
                                :key="item.id"
                                type="button"
                                class="inline-flex shrink-0 items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium"
                                :class="
                                    activeSection === item.id
                                        ? 'border-main bg-main text-white'
                                        : 'border-slate-200 bg-white text-slate-600'
                                "
                                @click="activeSection = item.id"
                            >
                                <FontAwesomeIcon :icon="item.icon" />
                                {{ item.label }}
                            </button>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto floating-scroll">
                    <section v-if="activeSection === 'dashboard'" class="space-y-4">
                        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                            <article
                                v-for="metric in platformMetrics"
                                :key="metric.label"
                                class="rounded-lg border border-slate-200 bg-white p-4"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-medium text-slate-500">{{ metric.label }}</p>
                                        <p class="mt-2 text-2xl font-bold">{{ metric.value }}</p>
                                    </div>
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-lg"
                                        :class="metric.tone"
                                    >
                                        <FontAwesomeIcon :icon="metric.icon" />
                                    </div>
                                </div>
                                <div class="mt-3 flex items-center gap-2 text-xs">
                                    <span class="font-semibold text-emerald-600">{{ metric.change }}</span>
                                    <span class="text-slate-500">{{ metric.caption }}</span>
                                </div>
                            </article>
                        </div>

                        <div class="grid gap-4 xl:grid-cols-[1.4fr_0.9fr]">
                            <section class="rounded-lg border border-slate-200 bg-white p-4">
                                <div class="mb-4 flex items-center justify-between gap-3">
                                    <div>
                                        <h2 class="text-base font-bold">Platform growth</h2>
                                        <p class="text-sm text-slate-500">Merchant, outlet, dan subscription aktif.</p>
                                    </div>
                                    <select
                                        v-model="period"
                                        class="rounded-lg border-slate-200 text-sm focus:border-main focus:ring-main/20"
                                    >
                                        <option>30 hari</option>
                                        <option>90 hari</option>
                                        <option>12 bulan</option>
                                    </select>
                                </div>
                                <div class="flex h-72 items-end gap-2 rounded-lg bg-slate-50 p-4">
                                    <div
                                        v-for="point in growthPoints"
                                        :key="point.month"
                                        class="flex min-w-0 flex-1 flex-col items-center gap-2"
                                    >
                                        <div class="flex h-52 w-full items-end justify-center gap-1">
                                            <div
                                                class="w-1/3 rounded-t bg-main"
                                                :style="{ height: `${point.merchants}%` }"
                                                title="Merchant"
                                            />
                                            <div
                                                class="w-1/3 rounded-t bg-cyan-500"
                                                :style="{ height: `${point.outlets}%` }"
                                                title="Outlet"
                                            />
                                            <div
                                                class="w-1/3 rounded-t bg-emerald-500"
                                                :style="{ height: `${point.subscriptions}%` }"
                                                title="Subscription"
                                            />
                                        </div>
                                        <span class="text-xs font-medium text-slate-500">{{ point.month }}</span>
                                    </div>
                                </div>
                            </section>

                            <section class="rounded-lg border border-slate-200 bg-white p-4">
                                <h2 class="text-base font-bold">Operation health</h2>
                                <div class="mt-4 space-y-4">
                                    <div v-for="item in healthSignals" :key="item.label">
                                        <div class="mb-2 flex items-center justify-between text-sm">
                                            <span class="font-medium text-slate-600">{{ item.label }}</span>
                                            <span class="font-semibold">{{ item.value }}%</span>
                                        </div>
                                        <div class="h-2 rounded-full bg-slate-100">
                                            <div
                                                class="h-2 rounded-full"
                                                :class="item.color"
                                                :style="{ width: `${item.value}%` }"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </section>

                    <section v-if="activeSection === 'merchants'" class="grid gap-4 xl:grid-cols-[1fr_24rem]">
                        <DataPanel
                            title="Merchant management"
                            subtitle="Monitoring seluruh merchant, status, outlet, dan kontribusi revenue."
                        >
                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[760px] text-left text-sm">
                                    <thead class="border-b border-slate-200 text-xs uppercase text-slate-500">
                                        <tr>
                                            <th class="py-3 pr-4">Merchant</th>
                                            <th class="py-3 pr-4">Plan</th>
                                            <th class="py-3 pr-4">Outlet</th>
                                            <th class="py-3 pr-4">MRR</th>
                                            <th class="py-3 pr-4">Status</th>
                                            <th class="py-3">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="merchant in filteredMerchants" :key="merchant.name">
                                            <td class="py-3 pr-4">
                                                <div class="font-semibold">{{ merchant.name }}</div>
                                                <div class="text-xs text-slate-500">{{ merchant.owner }}</div>
                                            </td>
                                            <td class="py-3 pr-4">{{ merchant.plan }}</td>
                                            <td class="py-3 pr-4">{{ merchant.outlets }}</td>
                                            <td class="py-3 pr-4">{{ merchant.revenue }}</td>
                                            <td class="py-3 pr-4">
                                                <StatusBadge :status="merchant.status" />
                                            </td>
                                            <td class="py-3">
                                                <button type="button" class="btn bg-slate-100 px-3 py-1.5 text-slate-700">
                                                    Detail
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </DataPanel>

                        <DataPanel title="Merchant detail" subtitle="Snapshot untuk support dan ops.">
                            <div class="space-y-4">
                                <div class="rounded-lg bg-slate-50 p-4">
                                    <div class="text-sm text-slate-500">Selected merchant</div>
                                    <div class="mt-1 text-lg font-bold">Kopi Pagi Nusantara</div>
                                    <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                                        <InfoItem label="Subscription" value="Growth" />
                                        <InfoItem label="Invoice" value="2 unpaid" />
                                        <InfoItem label="Revenue" value="Rp18,4 jt" />
                                        <InfoItem label="Activity" value="12 events" />
                                    </div>
                                </div>
                                <button type="button" class="btn w-full justify-center bg-warning text-white">
                                    Suspend merchant
                                </button>
                                <button type="button" class="btn w-full justify-center bg-emerald-600 text-white">
                                    Reactivate merchant
                                </button>
                            </div>
                        </DataPanel>
                    </section>

                    <section v-if="activeSection === 'billing'" class="grid gap-4 xl:grid-cols-[1fr_24rem]">
                        <DataPanel
                            title="Manual payment validation"
                            subtitle="Queue transfer bank yang perlu direview finance."
                        >
                            <div class="space-y-3">
                                <article
                                    v-for="payment in paymentQueue"
                                    :key="payment.invoice"
                                    class="rounded-lg border border-slate-200 p-4"
                                >
                                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h3 class="font-bold">{{ payment.invoice }}</h3>
                                                <StatusBadge :status="payment.status" />
                                            </div>
                                            <p class="mt-1 text-sm text-slate-500">
                                                {{ payment.merchant }} - {{ payment.bank }} - {{ payment.date }}
                                            </p>
                                        </div>
                                        <div class="text-left md:text-right">
                                            <div class="text-lg font-bold">{{ payment.amount }}</div>
                                            <div class="text-xs text-slate-500">uploaded proof</div>
                                        </div>
                                    </div>
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <button type="button" class="btn bg-emerald-600 text-white">
                                            <FontAwesomeIcon :icon="faCheck" />
                                            Approve
                                        </button>
                                        <button type="button" class="btn bg-danger text-white">
                                            <FontAwesomeIcon :icon="faXmark" />
                                            Reject
                                        </button>
                                        <button type="button" class="btn border-slate-200 bg-white text-slate-700">
                                            View proof
                                        </button>
                                    </div>
                                </article>
                            </div>
                        </DataPanel>

                        <DataPanel title="Validation flow" subtitle="Status pembayaran manual.">
                            <ol class="space-y-3">
                                <li
                                    v-for="(step, index) in validationFlow"
                                    :key="step"
                                    class="flex items-center gap-3"
                                >
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-main text-sm font-bold text-white">
                                        {{ index + 1 }}
                                    </div>
                                    <span class="text-sm font-medium text-slate-700">{{ step }}</span>
                                </li>
                            </ol>
                        </DataPanel>
                    </section>

                    <section v-if="activeSection === 'support'" class="grid gap-4 xl:grid-cols-[22rem_1fr]">
                        <DataPanel title="Support console" subtitle="Cari merchant dan cek konteks operasional.">
                            <div class="space-y-3">
                                <label class="block">
                                    <span class="text-sm font-medium text-slate-600">Merchant ID / email</span>
                                    <input
                                        v-model="supportSearch"
                                        type="text"
                                        class="form mt-1"
                                        placeholder="merchant@sollu.test"
                                    />
                                </label>
                                <button type="button" class="btn w-full justify-center bg-main text-white">
                                    <FontAwesomeIcon :icon="faMagnifyingGlass" />
                                    Search
                                </button>
                                <div class="rounded-lg bg-slate-50 p-3 text-sm text-slate-600">
                                    Hasil pencarian menampilkan subscription, audit log, dan error log terkait merchant.
                                </div>
                            </div>
                        </DataPanel>

                        <DataPanel title="Latest activity" subtitle="Audit dan operational events lintas platform.">
                            <div class="divide-y divide-slate-100">
                                <article v-for="activity in activities" :key="activity.title" class="py-3 first:pt-0">
                                    <div class="flex items-start gap-3">
                                        <div
                                            class="mt-1 flex h-9 w-9 items-center justify-center rounded-lg"
                                            :class="activity.tone"
                                        >
                                            <FontAwesomeIcon :icon="activity.icon" />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="font-semibold">{{ activity.title }}</div>
                                            <div class="text-sm text-slate-500">{{ activity.detail }}</div>
                                        </div>
                                        <div class="shrink-0 text-xs font-medium text-slate-400">{{ activity.time }}</div>
                                    </div>
                                </article>
                            </div>
                        </DataPanel>
                    </section>

                    <section v-if="activeSection === 'master'" class="grid gap-4 xl:grid-cols-2">
                        <DataPanel title="Global UOM" subtitle="Satuan global yang tersedia untuk merchant.">
                            <div class="grid gap-2 sm:grid-cols-2">
                                <div
                                    v-for="uom in uoms"
                                    :key="uom.code"
                                    class="flex items-center justify-between rounded-lg border border-slate-200 p-3"
                                >
                                    <div>
                                        <div class="font-bold">{{ uom.code }}</div>
                                        <div class="text-sm text-slate-500">{{ uom.name }}</div>
                                    </div>
                                    <button type="button" class="btn bg-slate-100 px-3 py-1.5 text-slate-700">
                                        Edit
                                    </button>
                                </div>
                            </div>
                        </DataPanel>

                        <DataPanel title="Business type" subtitle="Klasifikasi global untuk onboarding merchant.">
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="type in businessTypes"
                                    :key="type"
                                    class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700"
                                >
                                    {{ type }}
                                </span>
                            </div>
                        </DataPanel>
                    </section>

                    <section v-if="activeSection === 'configuration'" class="grid gap-4 xl:grid-cols-[1fr_24rem]">
                        <DataPanel title="Platform configuration" subtitle="Feature flag, parameter sistem, dan pricing.">
                            <div class="grid gap-3 md:grid-cols-2">
                                <label
                                    v-for="flag in featureFlags"
                                    :key="flag.label"
                                    class="flex items-center justify-between rounded-lg border border-slate-200 p-4"
                                >
                                    <div>
                                        <div class="font-semibold">{{ flag.label }}</div>
                                        <div class="text-sm text-slate-500">{{ flag.description }}</div>
                                    </div>
                                    <input
                                        v-model="flag.enabled"
                                        type="checkbox"
                                        class="rounded border-slate-300 text-main focus:ring-main/20"
                                    />
                                </label>
                            </div>
                        </DataPanel>

                        <DataPanel title="System parameters" subtitle="Default operasional platform.">
                            <div class="space-y-3">
                                <InfoItem label="Currency" value="IDR" />
                                <InfoItem label="Tax preset" value="PPN 11%" />
                                <InfoItem label="Maintenance" value="Off" />
                                <InfoItem label="Dashboard SLA" value="< 2 sec" />
                            </div>
                        </DataPanel>
                    </section>
                </div>
                </div>
            </main>
        </div>
    </div>
</template>

<script setup>
import BlankLayout from '@/Layout/BlankLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faBars,
    faBuilding,
    faChartLine,
    faCheck,
    faClipboardList,
    faCog,
    faCreditCard,
    faDownload,
    faFlag,
    faGaugeHigh,
    faHeadset,
    faLayerGroup,
    faMagnifyingGlass,
    faPlus,
    faReceipt,
    faShieldHalved,
    faShop,
    faStore,
    faTriangleExclamation,
    faXmark,
} from '@fortawesome/free-solid-svg-icons';
import { computed, h, ref } from 'vue';

defineOptions({
    layout: BlankLayout,
});

const activeSection = ref('dashboard');
const sidebarOpen = ref(false);
const query = ref('');
const period = ref('30 hari');
const supportSearch = ref('');

const navigation = [
    { id: 'dashboard', label: 'Dashboard', title: 'Platform dashboard', icon: faGaugeHigh },
    { id: 'merchants', label: 'Merchants', title: 'Business and merchant management', icon: faBuilding },
    { id: 'billing', label: 'Billing', title: 'Subscription and payment validation', icon: faCreditCard },
    { id: 'support', label: 'Support', title: 'Customer support console', icon: faHeadset },
    { id: 'master', label: 'Master Data', title: 'Global master data', icon: faLayerGroup },
    { id: 'configuration', label: 'Configuration', title: 'Platform configuration', icon: faCog },
];

const activeNavigation = computed(
    () => navigation.find((item) => item.id === activeSection.value) ?? navigation[0],
);

const platformMetrics = [
    {
        label: 'Total merchant',
        value: '12.840',
        change: '+18,2%',
        caption: 'vs last month',
        icon: faShop,
        tone: 'bg-blue-50 text-main',
    },
    {
        label: 'Active outlet',
        value: '38.116',
        change: '+11,7%',
        caption: 'operating',
        icon: faStore,
        tone: 'bg-cyan-50 text-cyan-600',
    },
    {
        label: 'MRR',
        value: 'Rp2,84 M',
        change: '+9,4%',
        caption: 'subscription',
        icon: faChartLine,
        tone: 'bg-emerald-50 text-emerald-600',
    },
    {
        label: 'Pending validation',
        value: '126',
        change: '24 urgent',
        caption: 'manual payment',
        icon: faReceipt,
        tone: 'bg-amber-50 text-amber-600',
    },
];

const growthPoints = [
    { month: 'Jan', merchants: 45, outlets: 54, subscriptions: 38 },
    { month: 'Feb', merchants: 52, outlets: 58, subscriptions: 44 },
    { month: 'Mar', merchants: 57, outlets: 64, subscriptions: 52 },
    { month: 'Apr', merchants: 61, outlets: 69, subscriptions: 59 },
    { month: 'Mei', merchants: 72, outlets: 76, subscriptions: 66 },
    { month: 'Jun', merchants: 84, outlets: 88, subscriptions: 78 },
    { month: 'Jul', merchants: 91, outlets: 94, subscriptions: 86 },
];

const healthSignals = [
    { label: 'Dashboard performance', value: 96, color: 'bg-emerald-500' },
    { label: 'Billing reliability', value: 91, color: 'bg-main' },
    { label: 'Support SLA', value: 84, color: 'bg-cyan-500' },
    { label: 'Audit completeness', value: 99, color: 'bg-emerald-600' },
];

const merchants = [
    {
        name: 'Kopi Pagi Nusantara',
        owner: 'Raka Pradipta',
        plan: 'Growth',
        outlets: 12,
        revenue: 'Rp18,4 jt',
        status: 'active',
    },
    {
        name: 'Apotek Sehat Sentosa',
        owner: 'Mira Andini',
        plan: 'Scale',
        outlets: 7,
        revenue: 'Rp11,2 jt',
        status: 'active',
    },
    {
        name: 'Laundry Cepat Bersih',
        owner: 'Damar Wicaksono',
        plan: 'Starter',
        outlets: 3,
        revenue: 'Rp3,6 jt',
        status: 'suspended',
    },
    {
        name: 'Warung Mandiri Jaya',
        owner: 'Tania Putri',
        plan: 'Growth',
        outlets: 5,
        revenue: 'Rp7,8 jt',
        status: 'inactive',
    },
];

const filteredMerchants = computed(() => {
    const keyword = query.value.trim().toLowerCase();
    if (!keyword) return merchants;

    return merchants.filter((merchant) =>
        [merchant.name, merchant.owner, merchant.plan, merchant.status].some((value) =>
            value.toLowerCase().includes(keyword),
        ),
    );
});

const paymentQueue = [
    {
        invoice: 'INV-2026-0614-0098',
        merchant: 'Kopi Pagi Nusantara',
        bank: 'BCA',
        date: '14 Jun 2026',
        amount: 'Rp2.499.000',
        status: 'waiting validation',
    },
    {
        invoice: 'INV-2026-0613-0042',
        merchant: 'Apotek Sehat Sentosa',
        bank: 'Mandiri',
        date: '13 Jun 2026',
        amount: 'Rp4.999.000',
        status: 'finance review',
    },
    {
        invoice: 'INV-2026-0612-0182',
        merchant: 'Warung Mandiri Jaya',
        bank: 'BRI',
        date: '12 Jun 2026',
        amount: 'Rp999.000',
        status: 'waiting validation',
    },
];

const validationFlow = [
    'Merchant upload payment proof',
    'Waiting validation',
    'Finance review',
    'Approve or reject',
    'Subscription updated',
];

const activities = [
    {
        title: 'Subscription extended',
        detail: 'Kopi Pagi Nusantara extended Growth plan until 14 Jul 2026.',
        time: '5m',
        icon: faCreditCard,
        tone: 'bg-emerald-50 text-emerald-600',
    },
    {
        title: 'Merchant suspended',
        detail: 'Laundry Cepat Bersih suspended after 3 failed billing attempts.',
        time: '22m',
        icon: faTriangleExclamation,
        tone: 'bg-amber-50 text-amber-600',
    },
    {
        title: 'Feature flag changed',
        detail: 'Product Admin enabled inventory conversion beta for retail segment.',
        time: '1h',
        icon: faFlag,
        tone: 'bg-blue-50 text-main',
    },
    {
        title: 'Support lookup',
        detail: 'Support Agent opened audit trail for Apotek Sehat Sentosa.',
        time: '2h',
        icon: faClipboardList,
        tone: 'bg-cyan-50 text-cyan-600',
    },
];

const uoms = [
    { code: 'PCS', name: 'Pieces' },
    { code: 'BOX', name: 'Box' },
    { code: 'KG', name: 'Kilogram' },
    { code: 'G', name: 'Gram' },
    { code: 'L', name: 'Liter' },
    { code: 'ML', name: 'Milliliter' },
];

const businessTypes = ['Retail', 'F&B', 'Service', 'Pharmacy', 'Laundry', 'Salon'];

const featureFlags = ref([
    {
        label: 'Manual transfer validation',
        description: 'Enable finance review queue.',
        enabled: true,
    },
    {
        label: 'Maintenance mode',
        description: 'Restrict merchant dashboard access.',
        enabled: false,
    },
    {
        label: 'Outlet usage analytics',
        description: 'Collect outlet activity telemetry.',
        enabled: true,
    },
    {
        label: 'Subscription pricing v2',
        description: 'Use revised package pricing.',
        enabled: false,
    },
]);

const DataPanel = {
    props: {
        title: { type: String, required: true },
        subtitle: { type: String, default: '' },
    },
    setup(props, { slots }) {
        return () =>
            h('section', { class: 'rounded-lg border border-slate-200 bg-white p-4' }, [
                h('div', { class: 'mb-4' }, [
                    h('h2', { class: 'text-base font-bold text-slate-950' }, props.title),
                    props.subtitle
                        ? h('p', { class: 'mt-1 text-sm text-slate-500' }, props.subtitle)
                        : null,
                ]),
                slots.default?.(),
            ]);
    },
};

const InfoItem = {
    props: {
        label: { type: String, required: true },
        value: { type: String, required: true },
    },
    setup(props) {
        return () =>
            h('div', { class: 'rounded-lg border border-slate-200 bg-white p-3' }, [
                h('div', { class: 'text-xs font-medium uppercase text-slate-400' }, props.label),
                h('div', { class: 'mt-1 text-sm font-bold text-slate-800' }, props.value),
            ]);
    },
};

const statusClasses = {
    active: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    inactive: 'bg-slate-100 text-slate-700 ring-slate-200',
    suspended: 'bg-amber-50 text-amber-700 ring-amber-200',
    'waiting validation': 'bg-amber-50 text-amber-700 ring-amber-200',
    'finance review': 'bg-blue-50 text-blue-700 ring-blue-200',
};

const StatusBadge = {
    props: {
        status: { type: String, required: true },
    },
    setup(props) {
        return () =>
            h(
                'span',
                {
                    class: [
                        'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize ring-1',
                        statusClasses[props.status] ?? 'bg-slate-100 text-slate-700 ring-slate-200',
                    ],
                },
                props.status,
            );
    },
};
</script>
