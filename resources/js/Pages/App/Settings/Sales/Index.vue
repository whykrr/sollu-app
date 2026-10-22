<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Pengaturan Penjualan"
                description="Atur fleksibilitas kasir POS, toleransi stok, saluran penjualan aktif, dan template faktur per outlet."
            >
                <SettingOutletSelector
                    v-if="outlets && outlets.length > 1"
                    :outlets="outlets"
                    :model-value="selectedOutlet?.id"
                    @update:model-value="changeOutlet"
                />
            </MainPageHeader>
            <div class="flex items-center gap-1 border-b border-slate-200 mb-4">
                <button
                    type="button"
                    class="px-4 py-2.5 text-xs font-semibold border-b-2 transition-colors flex items-center gap-2 cursor-pointer"
                    :class="[
                        activeTab === 'pos'
                            ? 'border-main text-main font-bold'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
                    ]"
                    @click="activeTab = 'pos'"
                >
                    <FontAwesomeIcon :icon="faCashRegister" />
                    <span>Pengaturan POS</span>
                </button>

                <button
                    v-feature="$enums.FeatureEnum.INVOICE_DEBT"
                    type="button"
                    class="px-4 py-2.5 text-xs font-semibold border-b-2 transition-colors flex items-center gap-2 cursor-pointer"
                    :class="[
                        activeTab === 'b2b'
                            ? 'border-main text-main font-bold'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
                    ]"
                    @click="activeTab = 'b2b'"
                >
                    <FontAwesomeIcon :icon="faFileInvoiceDollar" />
                    <span>Pengaturan Penjualan Faktur</span>
                </button>
            </div>
        </template>

        <!-- Navigation Tabs -->

        <!-- Tab 1: Pengaturan POS -->
        <div v-show="activeTab === 'pos'" class="pb-12">
            <PosSettingsTab
                :outlet="selectedOutlet"
                :outlets="outlets"
                :pos-settings="posSettings"
                :devices="devices"
                :otp-data="otpData"
            />
        </div>

        <!-- Tab 2: Pengaturan Penjualan Faktur (B2B) -->
        <div
            v-show="activeTab === 'b2b'"
            v-feature="$enums.FeatureEnum.INVOICE_DEBT"
            class="grid grid-cols-1 lg:grid-cols-12 gap-4 pb-12"
        >
            <!-- Left Column: Form Settings -->
            <div class="lg:col-span-7 flex flex-col gap-4">
                <!-- Card 1: Fleksibilitas Transaksi & Stok -->
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <h3
                        class="text-base font-semibold text-slate-800 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2"
                    >
                        <FontAwesomeIcon :icon="faBoxesStacked" class="text-main" />
                        Fleksibilitas Transaksi & Stok
                    </h3>

                    <div class="space-y-3">
                        <label
                            for="allow_negative_stock_b2b"
                            class="flex items-center justify-between p-3.5 border border-slate-200 rounded-lg cursor-pointer select-none hover:bg-slate-50/80 hover:border-slate-300 transition-colors"
                        >
                            <div>
                                <div class="font-medium text-sm text-slate-700">
                                    Toleransi Stok Negatif (Stok Minus)
                                </div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    Izinkan penerbitan faktur/invoice meskipun stok barang fisik di
                                    sistem tidak mencukupi (stok menjadi minus sementara).
                                </div>
                            </div>
                            <Switch
                                id="allow_negative_stock_b2b"
                                v-model="form.allow_negative_stock_b2b"
                                size="md"
                            />
                        </label>

                        <label
                            for="allow_custom_price_b2b"
                            class="flex items-center justify-between p-3.5 border border-slate-200 rounded-lg cursor-pointer select-none hover:bg-slate-50/80 hover:border-slate-300 transition-colors"
                        >
                            <div>
                                <div class="font-medium text-sm text-slate-700">
                                    Izinkan Override Harga Jual & Diskon Bebas
                                </div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    Staf berwenang dapat mengubah harga satuan produk dan memasukkan
                                    nominal diskon bebas pada saat input transaksi.
                                </div>
                            </div>
                            <Switch
                                id="allow_custom_price_b2b"
                                v-model="form.allow_custom_price_b2b"
                                size="md"
                            />
                        </label>
                    </div>
                </div>

                <!-- Card 2: Termin Pembayaran & Jatuh Tempo -->
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <h3
                        class="text-base font-semibold text-slate-800 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2"
                    >
                        <FontAwesomeIcon :icon="faCalendarAlt" class="text-main" />
                        Termin Pembayaran & Default Jatuh Tempo
                    </h3>

                    <div class="space-y-3">
                        <SelectionGroupField
                            id="default_due_days_b2b"
                            v-model="selectedDueTerm"
                            label="Default Durasi Jatuh Tempo (Hari)"
                            :options="dueTermOptions"
                            :feedback="form.errors.default_due_days_b2b"
                        />

                        <div v-if="selectedDueTerm === 'custom'" class="pt-1">
                            <NumberField
                                id="custom_due_days"
                                v-model="form.default_due_days_b2b"
                                label="Durasi Jatuh Tempo Kustom (Hari)"
                                placeholder="Misal: 45"
                                :min="0"
                                :max="365"
                                :feedback="form.errors.default_due_days_b2b"
                            />
                        </div>
                    </div>
                </div>

                <!-- Card 3: Format Faktur -->
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <h3
                        class="text-base font-semibold text-slate-800 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2"
                    >
                        <FontAwesomeIcon :icon="faFileInvoiceDollar" class="text-main" />
                        Format Nomor Faktur (Invoice)
                    </h3>

                    <div class="space-y-3">
                        <TextField
                            id="b2b_invoice_prefix"
                            v-model="form.b2b_invoice_prefix"
                            label="Awalan Nomor Faktur (Prefix, maks 5 karakter)"
                            placeholder="INV"
                            :feedback="form.errors.b2b_invoice_prefix"
                            maxlength="5"
                        />
                        <p class="text-xs text-slate-400 -mt-1">
                            Format faktur:
                            <span class="font-mono font-medium text-slate-600">{{
                                previewInvoiceNumber
                            }}</span>
                        </p>
                    </div>
                </div>

                <!-- Submit Button -->
                <div
                    class="flex items-center justify-between sticky bottom-4 z-10 bg-white/90 backdrop-blur-xs p-3.5 rounded-xl border border-slate-200"
                >
                    <button
                        type="button"
                        class="btn btn-flat text-xs text-slate-500 hover:text-slate-700"
                        :disabled="form.processing || !form.isDirty"
                        @click="resetForm"
                    >
                        <FontAwesomeIcon :icon="faUndo" class="mr-1.5" />
                        <span>Kembalikan</span>
                    </button>

                    <button
                        type="button"
                        class="btn btn-main flex items-center gap-2"
                        :disabled="form.processing || !form.isDirty"
                        @click="submitForm"
                    >
                        <FontAwesomeIcon :icon="faSave" />
                        <span>Simpan Pengaturan Penjualan</span>
                    </button>
                </div>
            </div>

            <!-- Right Column: Live Mockup Preview & Operational Notes -->
            <div class="lg:col-span-5 flex flex-col gap-4">
                <!-- Card Live Preview Faktur -->
                <div class="bg-white rounded-xl border border-slate-200 p-5 sticky top-0">
                    <h3
                        class="text-base font-semibold text-slate-800 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2"
                    >
                        <FontAwesomeIcon :icon="faEye" class="text-main" />
                        Preview Dokumen Faktur
                    </h3>

                    <div
                        class="bg-slate-50 border border-slate-200 rounded-lg p-4 font-sans text-xs space-y-3"
                    >
                        <div
                            class="flex justify-between items-start border-b border-slate-200 pb-2.5"
                        >
                            <div>
                                <div class="font-bold text-sm text-slate-800">
                                    {{ selectedOutlet?.name || 'Sollu Store' }}
                                </div>
                                <div class="text-slate-400 text-[11px]">Dokumen Faktur Resmi</div>
                            </div>
                            <div class="text-right">
                                <div class="font-mono font-bold text-main">
                                    {{ previewInvoiceNumber }}
                                </div>
                                <div class="text-slate-500 text-[11px]">
                                    Status: <span class="text-amber-600 font-semibold">UNPAID</span>
                                </div>
                            </div>
                        </div>

                        <div
                            class="grid grid-cols-2 gap-2 text-[11px] text-slate-600 border-b border-slate-200 pb-2.5"
                        >
                            <div>
                                <span class="text-slate-400">Tgl Transaksi:</span><br />
                                <span class="font-medium text-slate-700">{{
                                    currentDateFormatted
                                }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400">Jatuh Tempo:</span><br />
                                <span class="font-medium text-slate-700">
                                    {{ dueDateFormatted }} ({{
                                        form.default_due_days_b2b === 0
                                            ? 'Tunai'
                                            : `Net ${form.default_due_days_b2b} Hari`
                                    }})
                                </span>
                            </div>
                        </div>

                        <div>
                            <div class="text-slate-400 text-[11px] mb-1">Karakter Saluran:</div>
                            <span class="badge badge-sm badge-info font-normal">
                                Wholesale (Grosir / B2B)
                            </span>
                        </div>
                    </div>

                    <!-- Operational Guide / Tips -->
                    <div
                        class="mt-4 p-3.5 bg-blue-50/70 border border-blue-100 rounded-lg text-xs text-blue-900 space-y-2"
                    >
                        <div class="font-semibold flex items-center gap-1.5 text-blue-800">
                            <FontAwesomeIcon :icon="faInfoCircle" />
                            Petunjuk Operasional
                        </div>
                        <ul class="list-disc list-inside space-y-1 text-blue-700/90 text-[11px]">
                            <li>
                                <strong>Stok Negatif:</strong> Faktur dapat langsung diterbitkan
                                saat stok kosong. Stok minus akan otomatis terpulihkan saat barang
                                masuk melalui Penerimaan Barang (GR) atau Penyesuaian Stok.
                            </li>
                            <li>
                                <strong>Override Harga:</strong> Staf tetap harus memiliki hak akses
                                peran <em>'transaction.override_price'</em> untuk mengubah harga
                                master.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </MainPage>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { usePage } from '@inertiajs/vue3'
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import SettingOutletSelector from '../Components/SettingOutletSelector.vue'
import PosSettingsTab from './Partials/PosSettingsTab.vue'
import Switch from '@/Components/Form/Switch.vue'
import TextField from '@/Components/Form/TextField.vue'
import NumberField from '@/Components/Form/NumberField.vue'
import SelectionGroupField from '@/Components/Form/SelectionGroupField.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faBoxesStacked,
    faCashRegister,
    faCalendarAlt,
    faFileInvoiceDollar,
    faSave,
    faUndo,
    faEye,
    faInfoCircle,
} from '@fortawesome/free-solid-svg-icons'

const props = defineProps({
    outlets: {
        type: Array,
        default: () => [],
    },
    selectedOutlet: {
        type: Object,
        default: null,
    },
    salesSettings: {
        type: Object,
        default: () => ({}),
    },
    posSettings: {
        type: Object,
        default: () => ({
            enable_supervisor_pin: false,
            allow_negative_stock: false,
        }),
    },
    devices: {
        type: Array,
        default: () => [],
    },
    otpData: {
        type: Object,
        default: null,
    },
})

const activeTab = ref('pos')

const dueTermOptions = [
    { label: 'Tunai (0 Hari)', value: '0' },
    { label: '7 Hari', value: '7' },
    { label: '14 Hari', value: '14' },
    { label: '30 Hari (Net 30)', value: '30' },
    { label: '60 Hari', value: '60' },
    { label: 'Kustom', value: 'custom' },
]

const initialDueDays = props.salesSettings?.default_due_days_b2b ?? 14
const isPresetDue = ['0', '7', '14', '30', '60'].includes(String(initialDueDays))
const selectedDueTerm = ref(isPresetDue ? String(initialDueDays) : 'custom')

const form = useForm({
    outlet_id: props.selectedOutlet?.id || '',
    allow_negative_stock_b2b: props.salesSettings?.allow_negative_stock_b2b ?? false,
    allow_custom_price_b2b: props.salesSettings?.allow_custom_price_b2b ?? true,
    default_due_days_b2b: initialDueDays,
    b2b_invoice_prefix: props.salesSettings?.b2b_invoice_prefix || 'INV',
})

watch(selectedDueTerm, newVal => {
    if (newVal !== 'custom') {
        form.default_due_days_b2b = parseInt(newVal, 10)
    }
})

watch(
    () => props.salesSettings,
    newSettings => {
        form.outlet_id = props.selectedOutlet?.id || ''
        form.allow_negative_stock_b2b = newSettings?.allow_negative_stock_b2b ?? false
        form.allow_custom_price_b2b = newSettings?.allow_custom_price_b2b ?? true
        form.default_due_days_b2b = newSettings?.default_due_days_b2b ?? 14
        form.b2b_invoice_prefix = newSettings?.b2b_invoice_prefix || 'INV'

        const currentDue = form.default_due_days_b2b
        selectedDueTerm.value = ['0', '7', '14', '30', '60'].includes(String(currentDue))
            ? String(currentDue)
            : 'custom'
    },
    { deep: true }
)

const previewInvoiceNumber = computed(() => {
    const prefix = (form.b2b_invoice_prefix || 'INV').trim().toUpperCase()
    const now = new Date()
    const year = now.getFullYear()
    const month = String(now.getMonth() + 1).padStart(2, '0')
    const day = String(now.getDate()).padStart(2, '0')
    const outletName = props.selectedOutlet?.name || 'OUTLET'
    const words = outletName.trim().split(/\s+/)
    let outletInit = ''
    if (words.length >= 2) {
        outletInit = words
            .map(w => w[0])
            .join('')
            .toUpperCase()
    } else {
        outletInit = words[0].substring(0, 3).toUpperCase()
    }
    return `${prefix}/${year}.${month}.${day}/${outletInit}/00001`
})

const currentDateFormatted = computed(() => {
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(new Date())
})

const dueDateFormatted = computed(() => {
    const d = new Date()
    d.setDate(d.getDate() + (parseInt(form.default_due_days_b2b, 10) || 0))
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(d)
})

const changeOutlet = outletId => {
    router.visit(route('settings.sales.index', { outlet_id: outletId }), {
        preserveScroll: true,
        preserveState: false,
    })
}

const resetForm = () => {
    form.reset()
    const currentDue = form.default_due_days_b2b
    selectedDueTerm.value = ['0', '7', '14', '30', '60'].includes(String(currentDue))
        ? String(currentDue)
        : 'custom'
}

const submitForm = () => {
    form.put(route('settings.sales.update'), {
        preserveScroll: true,
    })
}
</script>
