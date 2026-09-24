<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Pengaturan Penjualan"
                description="Atur fleksibilitas transaksi, batas stok, saluran penjualan aktif, dan template faktur per outlet."
            >
                <SettingOutletSelector
                    v-if="outlets && outlets.length > 1"
                    :outlets="outlets"
                    :model-value="selectedOutlet?.id"
                    @update:model-value="changeOutlet"
                />
            </MainPageHeader>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 pb-12">
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

                <!-- Card 2: Saluran Penjualan Aktif -->
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <h3
                        class="text-base font-semibold text-slate-800 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2"
                    >
                        <FontAwesomeIcon :icon="faShop" class="text-main" />
                        Saluran Penjualan Aktif (Sales Channels)
                    </h3>
                    <p class="text-xs text-slate-500 mb-3">
                        Pilih saluran penjualan yang dapat dipilih oleh staf pada formulir transaksi
                        outlet ini:
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div
                            v-for="channel in availableChannels"
                            :key="channel.value"
                            class="flex items-center justify-between p-3 border rounded-lg cursor-pointer select-none transition-colors"
                            :class="
                                form.sales_channels_b2b.includes(channel.value)
                                    ? 'border-main bg-main/5'
                                    : 'border-slate-200 hover:bg-slate-50/80'
                            "
                            @click="toggleChannel(channel.value)"
                        >
                            <div class="flex items-center gap-2.5">
                                <FontAwesomeIcon
                                    :icon="channel.icon"
                                    class="text-slate-500 text-sm"
                                />
                                <div>
                                    <div class="font-medium text-sm text-slate-700">
                                        {{ channel.label }}
                                    </div>
                                    <div class="text-xs text-slate-400">
                                        {{ channel.desc }}
                                    </div>
                                </div>
                            </div>
                            <input
                                type="checkbox"
                                :checked="form.sales_channels_b2b.includes(channel.value)"
                                class="checkbox checkbox-sm checkbox-primary"
                                @click.stop="toggleChannel(channel.value)"
                            />
                        </div>
                    </div>
                    <div v-if="form.errors.sales_channels_b2b" class="text-xs text-rose-500 mt-2">
                        {{ form.errors.sales_channels_b2b }}
                    </div>
                </div>

                <!-- Card 3: Termin Pembayaran & Jatuh Tempo -->
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

                <!-- Card 4: Format Faktur & Syarat Ketentuan -->
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <h3
                        class="text-base font-semibold text-slate-800 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2"
                    >
                        <FontAwesomeIcon :icon="faFileInvoiceDollar" class="text-main" />
                        Format Faktur & Syarat Ketentuan Standar
                    </h3>

                    <div class="space-y-3">
                        <TextField
                            id="b2b_invoice_prefix"
                            v-model="form.b2b_invoice_prefix"
                            label="Awalan Nomor Faktur (Prefix)"
                            placeholder="INV"
                            :feedback="form.errors.b2b_invoice_prefix"
                            maxlength="10"
                        />
                        <p class="text-xs text-slate-400 -mt-1">
                            Format faktur:
                            <span class="font-mono font-medium text-slate-600">{{
                                previewInvoiceNumber
                            }}</span>
                        </p>

                        <TextareaField
                            id="default_terms_and_conditions_b2b"
                            v-model="form.default_terms_and_conditions_b2b"
                            label="Syarat & Ketentuan Standar Faktur (T&C)"
                            placeholder="Masukkan syarat pembayaran, nomor rekening transfer bank, atau kebijakan retur..."
                            rows="4"
                            :feedback="form.errors.default_terms_and_conditions_b2b"
                        />
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
                            <div class="text-slate-400 text-[11px] mb-1">Kanal Aktif Terpilih:</div>
                            <div class="flex flex-wrap gap-1">
                                <span
                                    v-for="ch in form.sales_channels_b2b"
                                    :key="ch"
                                    class="badge badge-sm badge-info font-normal"
                                >
                                    {{ getChannelLabel(ch) }}
                                </span>
                                <span
                                    v-if="form.sales_channels_b2b.length === 0"
                                    class="text-rose-500 italic text-[11px]"
                                >
                                    Belum ada kanal dipilih
                                </span>
                            </div>
                        </div>

                        <div class="border-t border-slate-200 pt-2.5">
                            <div class="font-semibold text-slate-700 text-[11px] mb-1">
                                Syarat & Ketentuan:
                            </div>
                            <div
                                class="text-slate-500 whitespace-pre-line text-[11px] bg-white p-2 rounded border border-slate-200"
                            >
                                {{
                                    form.default_terms_and_conditions_b2b ||
                                    'Belum ada syarat & ketentuan yang dikonfigurasi.'
                                }}
                            </div>
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
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import SettingOutletSelector from '../Components/SettingOutletSelector.vue'
import Switch from '@/Components/Form/Switch.vue'
import TextField from '@/Components/Form/TextField.vue'
import TextareaField from '@/Components/Form/TextareaField.vue'
import NumberField from '@/Components/Form/NumberField.vue'
import SelectionGroupField from '@/Components/Form/SelectionGroupField.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faBoxesStacked,
    faShop,
    faCalendarAlt,
    faFileInvoiceDollar,
    faSave,
    faUndo,
    faEye,
    faInfoCircle,
    faStore,
    faWarehouse,
    faGlobe,
    faComments,
    faWrench,
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
})

const availableChannels = [
    {
        value: 'direct',
        label: 'Penjualan Langsung',
        desc: 'Transaksi langsung di kasir / toko fisik',
        icon: faStore,
    },
    {
        value: 'wholesale',
        label: 'Grosir / Wholesale',
        desc: 'Pesanan partai besar B2B',
        icon: faWarehouse,
    },
    {
        value: 'e_commerce',
        label: 'E-Commerce / Marketplace',
        desc: 'Shopee, Tokopedia, TikTok Shop, dll',
        icon: faGlobe,
    },
    {
        value: 'social_media',
        label: 'Media Sosial & WhatsApp',
        desc: 'Pesanan chat WhatsApp / Instagram',
        icon: faComments,
    },
    {
        value: 'custom',
        label: 'Pesanan Khusus',
        desc: 'Kustomisasi B2B / Proyek pesanan',
        icon: faWrench,
    },
]

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
    sales_channels_b2b: props.salesSettings?.sales_channels_b2b || [
        'direct',
        'wholesale',
        'e_commerce',
        'social_media',
    ],
    default_due_days_b2b: initialDueDays,
    default_terms_and_conditions_b2b: props.salesSettings?.default_terms_and_conditions_b2b || '',
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
        form.sales_channels_b2b = newSettings?.sales_channels_b2b || [
            'direct',
            'wholesale',
            'e_commerce',
            'social_media',
        ]
        form.default_due_days_b2b = newSettings?.default_due_days_b2b ?? 14
        form.default_terms_and_conditions_b2b = newSettings?.default_terms_and_conditions_b2b || ''
        form.b2b_invoice_prefix = newSettings?.b2b_invoice_prefix || 'INV'

        const currentDue = form.default_due_days_b2b
        selectedDueTerm.value = ['0', '7', '14', '30', '60'].includes(String(currentDue))
            ? String(currentDue)
            : 'custom'
    },
    { deep: true }
)

const toggleChannel = val => {
    const idx = form.sales_channels_b2b.indexOf(val)
    if (idx > -1) {
        if (form.sales_channels_b2b.length > 1) {
            form.sales_channels_b2b.splice(idx, 1)
        }
    } else {
        form.sales_channels_b2b.push(val)
    }
}

const getChannelLabel = val => {
    return availableChannels.find(c => c.value === val)?.label || val
}

const previewInvoiceNumber = computed(() => {
    const prefix = (form.b2b_invoice_prefix || 'INV').trim().toUpperCase()
    const now = new Date()
    const year = now.getFullYear()
    const month = String(now.getMonth() + 1).padStart(2, '0')
    return `${prefix}/${year}${month}/00001`
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
