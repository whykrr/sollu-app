<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Pajak & Biaya Layanan">
                <SettingOutletSelector
                    v-if="outlets && outlets.length > 1"
                    :outlets="outlets"
                    :model-value="selectedOutlet?.id"
                    @update:model-value="changeOutlet"
                />
            </MainPageHeader>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 pb-12">
            <!-- Form Column -->
            <div class="lg:col-span-7 flex flex-col gap-6">
                <!-- Card Pajak -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-5">
                    <h3 class="text-base font-semibold text-slate-800 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2">
                        <FontAwesomeIcon :icon="faPercent" class="text-main" />
                        Pajak & Biaya Operasional
                    </h3>

                    <div class="flex flex-col gap-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <NumberField
                                id="financial_tax"
                                v-model="form.financial_tax"
                                label="Pajak Restoran / PB1 / PPN (%)"
                                placeholder="10"
                                :error="form.errors.financial_tax"
                                min="0"
                                max="100"
                                step="0.1"
                            />
                            <NumberField
                                id="financial_service_fee"
                                v-model="form.financial_service_fee"
                                label="Biaya Layanan / Service Charge (%)"
                                placeholder="5"
                                :error="form.errors.financial_service_fee"
                                min="0"
                                max="100"
                                step="0.1"
                            />
                        </div>

                        <div class="flex items-center justify-between p-3.5 border border-slate-200 rounded-lg">
                            <div>
                                <div class="font-medium text-sm text-slate-700">Harga Produk Sudah Termasuk Pajak</div>
                                <div class="text-xs text-slate-500">Pajak dihitung secara inklusif ke dalam harga barang</div>
                            </div>
                            <Switch id="tax_included_in_price" v-model="form.tax_included_in_price" size="md" />
                        </div>
                    </div>
                </div>

                <!-- Card Pembulatan -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-5">
                    <h3 class="text-base font-semibold text-slate-800 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2">
                        <FontAwesomeIcon :icon="faCoins" class="text-main" />
                        Pembulatan Nominal Transaksi
                    </h3>

                    <div class="flex flex-col gap-4">
                        <div class="flex items-center justify-between p-3.5 border border-slate-200 rounded-lg">
                            <div>
                                <div class="font-medium text-sm text-slate-700">Aktifkan Pembulatan Otomatis</div>
                                <div class="text-xs text-slate-500">Membulatkan total tagihan akhir ke kelipatan ratusan rupiah</div>
                            </div>
                            <Switch id="rounding_enabled" v-model="form.rounding_enabled" size="md" />
                        </div>

                        <div v-if="form.rounding_enabled" class="flex flex-col gap-2 pt-2">
                            <label class="text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                Aturan Pembulatan
                            </label>
                            <div class="grid grid-cols-3 gap-3">
                                <label
                                    class="flex items-center justify-center p-3 rounded-lg border text-center cursor-pointer text-xs font-medium transition-all"
                                    :class="form.rounding_mode === 'nearest' ? 'border-main bg-main/5 text-main font-bold' : 'border-slate-200 hover:bg-slate-50 text-slate-700'"
                                >
                                    <input v-model="form.rounding_mode" type="radio" value="nearest" class="sr-only" />
                                    <span>Terdekat (Rp 100)</span>
                                </label>
                                <label
                                    class="flex items-center justify-center p-3 rounded-lg border text-center cursor-pointer text-xs font-medium transition-all"
                                    :class="form.rounding_mode === 'up' ? 'border-main bg-main/5 text-main font-bold' : 'border-slate-200 hover:bg-slate-50 text-slate-700'"
                                >
                                    <input v-model="form.rounding_mode" type="radio" value="up" class="sr-only" />
                                    <span>Ke Atas (Ceil)</span>
                                </label>
                                <label
                                    class="flex items-center justify-center p-3 rounded-lg border text-center cursor-pointer text-xs font-medium transition-all"
                                    :class="form.rounding_mode === 'down' ? 'border-main bg-main/5 text-main font-bold' : 'border-slate-200 hover:bg-slate-50 text-slate-700'"
                                >
                                    <input v-model="form.rounding_mode" type="radio" value="down" class="sr-only" />
                                    <span>Ke Bawah (Floor)</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end sticky bottom-4 z-10 bg-white/90 backdrop-blur-xs p-4 rounded-xl border border-slate-200 shadow-sm">
                    <button
                        class="btn btn-main px-6 py-2.5 rounded-lg shadow-sm font-medium flex items-center gap-2"
                        :disabled="form.processing"
                        @click="submitForm"
                    >
                        <FontAwesomeIcon :icon="faSave" />
                        <span>Simpan Pengaturan Pajak</span>
                    </button>
                </div>
            </div>

            <!-- Simulation Calculation Column -->
            <div class="lg:col-span-5">
                <div class="sticky top-20 bg-white rounded-xl border border-slate-200 shadow-xs p-5">
                    <h3 class="text-sm font-semibold text-slate-800 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2">
                        <FontAwesomeIcon :icon="faCalculator" class="text-main" />
                        Simulasi Perhitungan Tagihan
                    </h3>

                    <div class="space-y-3 text-xs">
                        <div class="flex justify-between py-1.5 text-slate-600">
                            <span>Subtotal Pesanan Contoh:</span>
                            <span class="font-medium text-slate-900">Rp 100.000</span>
                        </div>

                        <div class="flex justify-between py-1.5 text-slate-600">
                            <span>Service Charge ({{ form.financial_service_fee || 0 }}%):</span>
                            <span class="font-medium text-slate-900">+ Rp {{ formatNumber(calculatedService) }}</span>
                        </div>

                        <div class="flex justify-between py-1.5 text-slate-600">
                            <span>Pajak Restoran ({{ form.financial_tax || 0 }}%):</span>
                            <span class="font-medium text-slate-900">+ Rp {{ formatNumber(calculatedTax) }}</span>
                        </div>

                        <div v-if="form.rounding_enabled" class="flex justify-between py-1.5 text-slate-600">
                            <span>Penyesuaian Pembulatan:</span>
                            <span class="font-medium text-slate-900">Rp {{ formatNumber(calculatedRounding) }}</span>
                        </div>

                        <div class="flex justify-between py-3 border-t border-slate-200 font-bold text-sm text-slate-900 bg-slate-50 -mx-5 px-5 mt-2">
                            <span>Total yang Dibayar Customer:</span>
                            <span class="text-main">Rp {{ formatNumber(calculatedTotal) }}</span>
                        </div>
                    </div>

                    <div class="mt-4 p-3 rounded-lg bg-blue-50/70 border border-blue-100 text-[11px] text-blue-800 leading-relaxed">
                        <strong>Keterangan:</strong> Pajak dan biaya layanan ini akan otomatis diterapkan ke setiap transaksi yang dilakukan pada outlet <strong>{{ selectedOutlet?.name }}</strong>.
                    </div>
                </div>
            </div>
        </div>
    </MainPage>
</template>

<script setup>
import { computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faCalculator,
    faCoins,
    faPercent,
    faSave,
} from '@fortawesome/free-solid-svg-icons';

import MainPage from '@/Components/UI/MainPage.vue';
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue';
import SettingOutletSelector from '../Components/SettingOutletSelector.vue';
import NumberField from '@/Components/Form/NumberField.vue';
import Switch from '@/Components/Form/Switch.vue';

const props = defineProps({
    outlets: Array,
    selectedOutlet: Object,
    taxSettings: Object,
});

const form = useForm({
    outlet_id: props.selectedOutlet?.id ?? '',
    financial_tax: props.taxSettings?.financial_tax ?? 0,
    financial_service_fee: props.taxSettings?.financial_service_fee ?? 0,
    tax_included_in_price: !!props.taxSettings?.tax_included_in_price,
    rounding_enabled: !!props.taxSettings?.rounding_enabled,
    rounding_mode: props.taxSettings?.rounding_mode ?? 'nearest',
});

const changeOutlet = (newOutletId) => {
    router.visit(route('settings.taxes.index', { outlet_id: newOutletId }), {
        preserveState: false,
        preserveScroll: true,
    });
};

const subtotalSample = 100000;

const calculatedService = computed(() => {
    const feeRate = parseFloat(form.financial_service_fee) || 0;
    return subtotalSample * (feeRate / 100);
});

const calculatedTax = computed(() => {
    const taxRate = parseFloat(form.financial_tax) || 0;
    if (form.tax_included_in_price) {
        return (subtotalSample * taxRate) / (100 + taxRate);
    }
    const taxableBase = subtotalSample + calculatedService.value;
    return taxableBase * (taxRate / 100);
});

const calculatedRawTotal = computed(() => {
    if (form.tax_included_in_price) {
        return subtotalSample + calculatedService.value;
    }
    return subtotalSample + calculatedService.value + calculatedTax.value;
});

const calculatedTotal = computed(() => {
    const raw = calculatedRawTotal.value;
    if (!form.rounding_enabled) return Math.round(raw);

    const step = 100;
    switch (form.rounding_mode) {
        case 'up':
            return Math.ceil(raw / step) * step;
        case 'down':
            return Math.floor(raw / step) * step;
        default:
            return Math.round(raw / step) * step;
    }
});

const calculatedRounding = computed(() => {
    return calculatedTotal.value - calculatedRawTotal.value;
});

const formatNumber = (val) => {
    return new Intl.NumberFormat('id-ID').format(Math.round(val));
};

const submitForm = () => {
    form.outlet_id = props.selectedOutlet?.id;
    form.put(route('settings.taxes.update'), {
        preserveScroll: true,
    });
};
</script>
