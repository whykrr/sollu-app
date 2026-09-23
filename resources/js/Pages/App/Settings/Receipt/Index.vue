<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Layout Struk & Nota"
                description="Kustomisasi tampilan struk kasir, mulai dari header, rincian transaksi, hingga footer."
            >
                <SettingOutletSelector
                    v-if="outlets && outlets.length > 1"
                    :outlets="outlets"
                    :model-value="selectedOutlet?.id"
                    @update:model-value="changeOutlet"
                />
            </MainPageHeader>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 pb-8">
            <!-- Left Column: Settings Form -->
            <div class="lg:col-span-7 flex flex-col gap-4">
                <!-- Card 1: Format Kertas & Perilaku Cetak -->
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <h3
                        class="text-base font-semibold text-slate-800 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2"
                    >
                        <FontAwesomeIcon :icon="faPrint" class="text-main" />
                        Format Kertas & Perilaku Cetak
                    </h3>
                    <div class="space-y-2">
                        <SelectionGroupField
                            id="paper_size"
                            v-model="form.paper_size"
                            label="Ukuran Kertas Thermal"
                            :options="paperSizeOptions"
                            :feedback="form.errors.paper_size"
                        />

                        <div class="pt-1">
                            <label
                                for="auto_print"
                                class="flex items-center justify-between p-3 border border-slate-200 rounded-lg cursor-pointer select-none hover:bg-slate-50/80 hover:border-slate-300 transition-colors"
                            >
                                <div>
                                    <div class="font-medium text-sm text-slate-700">
                                        Auto Print Struk
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        Cetak otomatis setelah bayar
                                    </div>
                                </div>
                                <Switch id="auto_print" v-model="form.auto_print" size="md" />
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Header Struk -->
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <h3
                        class="text-base font-semibold text-slate-800 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2"
                    >
                        <FontAwesomeIcon :icon="faHeading" class="text-main" />
                        Header Struk
                    </h3>
                    <div class="space-y-2">
                        <label
                            for="show_logo"
                            class="flex items-center justify-between p-3 border border-slate-200 rounded-lg cursor-pointer select-none hover:bg-slate-50/80 hover:border-slate-300 transition-colors"
                        >
                            <div>
                                <div class="font-medium text-sm text-slate-700">
                                    Tampilkan Logo Toko
                                </div>
                                <div class="text-xs text-slate-500">
                                    Cetak logo di bagian atas struk
                                </div>
                            </div>
                            <Switch id="show_logo" v-model="form.show_logo" size="md" />
                        </label>

                        <TextField
                            id="custom_header_title"
                            v-model="form.custom_header_title"
                            label="Judul Header Kustom (Opsional)"
                            placeholder="Kosongkan untuk menggunakan nama outlet"
                            :feedback="form.errors.custom_header_title"
                        />

                        <TextField
                            id="header_notes"
                            v-model="form.header_notes"
                            label="Pesan Pembuka / Slogan"
                            placeholder="Contoh: Nikmati Kopi Terbaik Anda Hari Ini!"
                            :feedback="form.errors.header_notes"
                        />

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <label
                                for="show_address"
                                class="flex items-center justify-between p-3 border border-slate-200 rounded-lg cursor-pointer select-none hover:bg-slate-50/80 hover:border-slate-300 transition-colors"
                            >
                                <div class="font-medium text-xs text-slate-700">
                                    Tampilkan Alamat
                                </div>
                                <Switch id="show_address" v-model="form.show_address" size="sm" />
                            </label>
                            <label
                                for="show_phone"
                                class="flex items-center justify-between p-3 border border-slate-200 rounded-lg cursor-pointer select-none hover:bg-slate-50/80 hover:border-slate-300 transition-colors"
                            >
                                <div class="font-medium text-xs text-slate-700">
                                    Tampilkan No. Telp
                                </div>
                                <Switch id="show_phone" v-model="form.show_phone" size="sm" />
                            </label>
                            <label
                                for="show_email"
                                class="flex items-center justify-between p-3 border border-slate-200 rounded-lg cursor-pointer select-none hover:bg-slate-50/80 hover:border-slate-300 transition-colors"
                            >
                                <div class="font-medium text-xs text-slate-700">
                                    Tampilkan Email
                                </div>
                                <Switch id="show_email" v-model="form.show_email" size="sm" />
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Rincian Transaksi (Body) -->
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <h3
                        class="text-base font-semibold text-slate-800 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2"
                    >
                        <FontAwesomeIcon :icon="faList" class="text-main" />
                        Rincian Transaksi
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <label
                            for="show_cashier_name"
                            class="flex items-center justify-between p-3 border border-slate-200 rounded-lg cursor-pointer select-none hover:bg-slate-50/80 hover:border-slate-300 transition-colors"
                        >
                            <div class="font-medium text-xs text-slate-700">
                                Nama Kasir Bertugas
                            </div>
                            <Switch
                                id="show_cashier_name"
                                v-model="form.show_cashier_name"
                                size="sm"
                            />
                        </label>
                        <label
                            for="show_customer_name"
                            class="flex items-center justify-between p-3 border border-slate-200 rounded-lg cursor-pointer select-none hover:bg-slate-50/80 hover:border-slate-300 transition-colors"
                        >
                            <div class="font-medium text-xs text-slate-700">Nama Pelanggan</div>
                            <Switch
                                id="show_customer_name"
                                v-model="form.show_customer_name"
                                size="sm"
                            />
                        </label>
                        <label
                            for="show_order_type"
                            class="flex items-center justify-between p-3 border border-slate-200 rounded-lg cursor-pointer select-none hover:bg-slate-50/80 hover:border-slate-300 transition-colors"
                        >
                            <div class="font-medium text-xs text-slate-700">
                                Tipe Pesanan (Dine-in/Takeaway)
                            </div>
                            <Switch id="show_order_type" v-model="form.show_order_type" size="sm" />
                        </label>
                        <label
                            for="show_modifiers"
                            class="flex items-center justify-between p-3 border border-slate-200 rounded-lg cursor-pointer select-none hover:bg-slate-50/80 hover:border-slate-300 transition-colors"
                        >
                            <div class="font-medium text-xs text-slate-700">
                                Detail Modifier / Topping
                            </div>
                            <Switch id="show_modifiers" v-model="form.show_modifiers" size="sm" />
                        </label>
                        <label
                            for="show_item_notes"
                            class="flex items-center justify-between p-3 border border-slate-200 rounded-lg cursor-pointer select-none hover:bg-slate-50/80 hover:border-slate-300 transition-colors"
                        >
                            <div class="font-medium text-xs text-slate-700">
                                Catatan Khusus Produk
                            </div>
                            <Switch id="show_item_notes" v-model="form.show_item_notes" size="sm" />
                        </label>
                        <label
                            for="show_tax_detail"
                            class="flex items-center justify-between p-3 border border-slate-200 rounded-lg cursor-pointer select-none hover:bg-slate-50/80 hover:border-slate-300 transition-colors"
                        >
                            <div class="font-medium text-xs text-slate-700">
                                Rincian Pajak (PPN/PB1)
                            </div>
                            <Switch id="show_tax_detail" v-model="form.show_tax_detail" size="sm" />
                        </label>
                        <label
                            for="show_service_charge"
                            class="flex items-center justify-between p-3 border border-slate-200 rounded-lg cursor-pointer select-none hover:bg-slate-50/80 hover:border-slate-300 transition-colors sm:col-span-2"
                        >
                            <div class="font-medium text-xs text-slate-700">
                                Rincian Biaya Layanan (Service Charge)
                            </div>
                            <Switch
                                id="show_service_charge"
                                v-model="form.show_service_charge"
                                size="sm"
                            />
                        </label>
                    </div>
                </div>

                <!-- Card 4: Footer Struk -->
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <h3
                        class="text-base font-semibold text-slate-800 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2"
                    >
                        <FontAwesomeIcon :icon="faParagraph" class="text-main" />
                        Footer Struk
                    </h3>
                    <div class="space-y-2">
                        <TextareaField
                            id="footer_notes"
                            v-model="form.footer_notes"
                            label="Pesan Penutup / Catatan Kaki"
                            placeholder="Contoh: Terima kasih atas kunjungan Anda! Barang yang dibeli tidak dapat ditukar."
                            rows="2"
                            :feedback="form.errors.footer_notes"
                        />

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <TextField
                                id="social_media_info"
                                v-model="form.social_media_info"
                                label="Instagram / Website"
                                placeholder="Contoh: @sollu.pos | sollu.id"
                                :feedback="form.errors.social_media_info"
                            />
                            <TextField
                                id="wifi_info"
                                v-model="form.wifi_info"
                                label="Informasi WiFi (Opsional)"
                                placeholder="Contoh: WiFi: Sollu / Pass: 12345678"
                                :feedback="form.errors.wifi_info"
                            />
                        </div>

                        <label
                            for="show_qr_code"
                            class="flex items-center justify-between p-3 border border-slate-200 rounded-lg cursor-pointer select-none hover:bg-slate-50/80 hover:border-slate-300 transition-colors"
                        >
                            <div>
                                <div class="font-medium text-sm text-slate-700">
                                    Tampilkan QR Code Struk
                                </div>
                                <div class="text-xs text-slate-500">
                                    QR untuk feedback atau cek transaksi digital
                                </div>
                            </div>
                            <Switch id="show_qr_code" v-model="form.show_qr_code" size="md" />
                        </label>
                    </div>
                </div>

                <!-- Submit Button -->
                <div
                    class="flex justify-end sticky bottom-4 z-10 bg-white/90 backdrop-blur-xs p-3 rounded-xl border border-slate-200"
                >
                    <button class="btn btn-main" :disabled="form.processing" @click="submitForm">
                        <FontAwesomeIcon :icon="faSave" />
                        <span>Simpan Pengaturan Struk</span>
                    </button>
                </div>
            </div>

            <!-- Right Column: Live Thermal Receipt Preview -->
            <div class="lg:col-span-5">
                <div class="sticky top-0 flex flex-col items-center">
                    <div
                        class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3 flex items-center gap-1.5"
                    >
                        <FontAwesomeIcon :icon="faEye" class="text-main" />
                        Live Preview Struk Thermal ({{ form.paper_size }})
                    </div>

                    <!-- Receipt Paper Container -->
                    <div
                        class="bg-[#fafaf8] text-slate-800 p-5 rounded-sm shadow-md border border-slate-300 font-mono text-xs leading-relaxed transition-all duration-300 w-full"
                        :class="form.paper_size === '58mm' ? 'max-w-[300px]' : 'max-w-[380px]'"
                    >
                        <!-- Top jagged edge indicator -->
                        <div
                            class="border-b-2 border-dashed border-slate-300 pb-3 mb-3 text-center"
                        >
                            <!-- Logo -->
                            <div v-if="form.show_logo" class="flex justify-center mb-2">
                                <img
                                    v-if="business?.logo_url || selectedOutlet?.logo_url"
                                    :src="business?.logo_url || selectedOutlet?.logo_url"
                                    class="h-16 object-contain"
                                    alt="Logo Usaha"
                                />
                                <div
                                    v-else
                                    class="w-16 h-16 rounded-full bg-slate-200 border border-slate-300 flex items-center justify-center text-slate-500 text-sm font-bold"
                                >
                                    LOGO
                                </div>
                            </div>

                            <!-- Header Title -->
                            <div class="font-bold text-base uppercase text-slate-900 tracking-wide">
                                {{
                                    form.custom_header_title ||
                                    selectedOutlet?.name ||
                                    'NAMA OUTLET KASIR'
                                }}
                            </div>

                            <!-- Address & Contact -->
                            <div
                                v-if="form.show_address && selectedOutlet?.address"
                                class="text-[11px] text-slate-600 mt-1"
                            >
                                {{ selectedOutlet.address }}
                            </div>
                            <div
                                v-if="form.show_phone && selectedOutlet?.phone"
                                class="text-[11px] text-slate-600"
                            >
                                Telp: {{ selectedOutlet.phone }}
                            </div>
                            <div
                                v-if="form.show_email && selectedOutlet?.email"
                                class="text-[11px] text-slate-600"
                            >
                                Email: {{ selectedOutlet.email }}
                            </div>

                            <!-- Header Note -->
                            <div
                                v-if="form.header_notes"
                                class="text-[11px] italic text-slate-600 mt-1.5"
                            >
                                {{ form.header_notes }}
                            </div>
                        </div>

                        <!-- Meta Info -->
                        <div
                            class="border-b border-dashed border-slate-300 pb-2 mb-2 text-[11px] text-slate-700"
                        >
                            <div class="flex justify-between">
                                <span>No: INV/20260814/0001</span>
                                <span>14/08/2026 14:00</span>
                            </div>
                            <div class="flex justify-between mt-0.5">
                                <span v-if="form.show_cashier_name">Kasir: Budi</span>
                                <span v-else></span>
                                <span v-if="form.show_order_type" class="font-bold">Dine In</span>
                            </div>
                            <div v-if="form.show_customer_name" class="mt-0.5">
                                Pelanggan: Ahmad
                            </div>
                        </div>

                        <!-- Item List -->
                        <div class="border-b border-dashed border-slate-300 pb-2 mb-2 text-[11px]">
                            <!-- Item 1 -->
                            <div class="font-medium">Kopi Susu Aren</div>
                            <div class="flex justify-between">
                                <span>2 x Rp 25.000</span>
                                <span>Rp 50.000</span>
                            </div>
                            <div v-if="form.show_modifiers" class="text-[10px] text-slate-500">
                                + Less Sugar, Extra Shot (+Rp 5.000)
                            </div>
                            <div
                                v-if="form.show_item_notes"
                                class="text-[10px] italic text-slate-500"
                            >
                                * Sedikit es
                            </div>
                            <div class="flex justify-between text-[10px] text-slate-500">
                                <span>Diskon Item</span>
                                <span>-Rp 0</span>
                            </div>

                            <!-- Item 2 -->
                            <div class="font-medium mt-1.5">Croissant Butter</div>
                            <div class="flex justify-between">
                                <span>1 x Rp 25.000</span>
                                <span>Rp 25.000</span>
                            </div>
                        </div>

                        <!-- Financial Calculation -->
                        <div class="border-b border-dashed border-slate-300 pb-2 mb-2 text-[11px]">
                            <div class="flex justify-between text-slate-600">
                                <span>Subtotal</span>
                                <span>Rp 75.000</span>
                            </div>
                            <div class="flex justify-between text-slate-600">
                                <span>Diskon</span>
                                <span>-Rp 5.000</span>
                            </div>
                            <div
                                v-if="form.show_tax_detail"
                                class="flex justify-between text-slate-600"
                            >
                                <span>Pajak (PB1/PPN)</span>
                                <span>Rp 7.000</span>
                            </div>
                            <div
                                v-if="form.show_service_charge"
                                class="flex justify-between text-slate-600"
                            >
                                <span>Service Charge</span>
                                <span>Rp 3.500</span>
                            </div>
                            <div
                                class="flex justify-between font-bold text-xs text-slate-900 pt-1 mt-1 border-t border-slate-200"
                            >
                                <span>TOTAL</span>
                                <span>Rp 80.500</span>
                            </div>
                            <div class="flex justify-between text-slate-600 mt-1">
                                <span>Tunai</span>
                                <span>Rp 100.000</span>
                            </div>
                            <div class="flex justify-between text-slate-600">
                                <span>Kembalian</span>
                                <span>Rp 19.500</span>
                            </div>
                        </div>

                        <!-- Footer Area -->
                        <div class="text-center pt-1 text-[11px] text-slate-600">
                            <div
                                v-if="form.footer_notes"
                                class="whitespace-pre-line mb-1.5 font-medium"
                            >
                                {{ form.footer_notes }}
                            </div>
                            <div v-if="form.social_media_info" class="text-[10px] text-slate-500">
                                {{ form.social_media_info }}
                            </div>
                            <div v-if="form.wifi_info" class="text-[11px] text-slate-600">
                                WiFi: {{ form.wifi_info }}
                            </div>
                            <!-- QR Code Preview -->
                            <div
                                v-if="form.show_qr_code"
                                class="flex flex-col items-center justify-center mt-2.5 pt-2 border-t border-slate-200"
                            >
                                <div
                                    class="w-16 h-16 bg-slate-900 text-white flex items-center justify-center rounded-sm text-[8px] font-bold"
                                >
                                    [QR CODE]
                                </div>
                                <span class="text-[9px] text-slate-400 mt-1"
                                    >Scan untuk detail transaksi</span
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainPage>
</template>

<script setup>
import { router, useForm } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faEye,
    faHeading,
    faList,
    faParagraph,
    faPrint,
    faSave,
} from '@fortawesome/free-solid-svg-icons'

import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import SettingOutletSelector from '../Components/SettingOutletSelector.vue'
import TextField from '@/Components/Form/TextField.vue'
import TextareaField from '@/Components/Form/TextareaField.vue'
import Switch from '@/Components/Form/Switch.vue'
import SelectionGroupField from '@/Components/Form/SelectionGroupField.vue'

const props = defineProps({
    outlets: Array,
    selectedOutlet: Object,
    receiptSetting: Object,
    business: Object,
})

const paperSizeOptions = [
    { label: '58 mm (Standar)', value: '58mm' },
    { label: '80 mm (Lebar)', value: '80mm' },
]

const defaultSettings = {
    outlet_id: props.selectedOutlet?.id ?? '',
    paper_size: '58mm',
    show_logo: true,
    custom_header_title: '',
    header_notes: 'Terima kasih atas kunjungan Anda!',
    show_address: true,
    show_phone: true,
    show_email: false,
    show_cashier_name: true,
    show_customer_name: true,
    show_order_type: true,
    show_modifiers: true,
    show_item_notes: true,
    show_tax_detail: true,
    show_service_charge: false,
    footer_notes: 'Barang yang sudah dibeli tidak dapat ditukar atau dikembalikan.',
    social_media_info: '',
    wifi_info: '',
    show_qr_code: false,
    qr_type: 'invoice',
    auto_print: true,
}

const form = useForm({
    ...defaultSettings,
    ...(props.receiptSetting || {}),
    outlet_id: props.selectedOutlet?.id ?? '',
})

const changeOutlet = newOutletId => {
    router.visit(route('settings.receipt.index', { outlet_id: newOutletId }), {
        preserveState: false,
        preserveScroll: true,
    })
}

const submitForm = () => {
    form.outlet_id = props.selectedOutlet?.id
    form.put(route('settings.receipt.update'), {
        preserveScroll: true,
    })
}
</script>
