<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Metode Perhitungan Aset Inventaris"
                description="Tentukan bagaimana sistem menghitung HPP barang keluar dan nilai rupiah aset persediaan tokomu"
            />
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 pb-12">
            <!-- Left Column: Settings Form & Selection -->
            <div class="lg:col-span-8 flex flex-col gap-4">
                <!-- Card 1: Active Status Banner -->
                <div class="bg-white rounded-xl border border-slate-200 p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-3.5">
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-white font-bold"
                            :class="form.costing_method === 'fifo' ? 'bg-emerald-600' : 'bg-indigo-600'"
                        >
                            <FontAwesomeIcon :icon="form.costing_method === 'fifo' ? faBoxesStacked : faCalculator" class="text-lg" />
                        </div>
                        <div class="space-y-0.5">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium text-slate-500">Metode Aktif Saat Ini:</span>
                                <span
                                    class="badge text-xs font-semibold px-2 py-0.5"
                                    :class="form.costing_method === 'fifo' ? 'badge-success' : 'badge-info'"
                                >
                                    {{ form.costing_method === 'fifo' ? 'FIFO (First-In, First-Out)' : 'Moving Average (Rata-Rata Bergerak)' }}
                                </span>
                            </div>
                            <h3 class="text-base font-bold text-slate-800">
                                {{ form.costing_method === 'fifo' ? 'Masuk Pertama, Keluar Pertama' : 'Rata-Rata Bergerak Otomatis' }}
                            </h3>
                            <p class="text-xs text-slate-500">
                                {{ form.costing_method === 'fifo'
                                    ? 'Stok lama keluar lebih dulu, nilai aset di gudang mencerminkan harga pembelian paling aktual.'
                                    : 'Biaya dihitung dari rata-rata tertimbang setiap stok masuk baru, nilai HPP lebih stabil.' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Interactive Method Switch Selection -->
                <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                <FontAwesomeIcon :icon="faSliders" class="text-main" />
                                <span>Pilih Metode Perhitungan</span>
                            </h4>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Klik salah satu opsi di bawah ini untuk mengubah metode perhitungan aset bisnismu.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                        <!-- Option FIFO -->
                        <div
                            class="relative flex cursor-pointer flex-col justify-between rounded-xl border p-4 transition-all duration-200 hover:border-main/70 hover:bg-slate-50/50"
                            :class="[
                                form.costing_method === 'fifo'
                                    ? 'border-main bg-main/5 ring-2 ring-main/20'
                                    : 'border-slate-200 bg-white'
                            ]"
                            @click="form.costing_method = 'fifo'"
                        >
                            <div class="space-y-2.5">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-medium text-emerald-700 border border-emerald-200/70">
                                        <FontAwesomeIcon :icon="faUtensils" class="text-[10px]" />
                                        <span>Rekomendasi F&B</span>
                                    </span>
                                    <div
                                        class="flex h-5 w-5 items-center justify-center rounded-full border transition-all"
                                        :class="[
                                            form.costing_method === 'fifo'
                                                ? 'border-main bg-main text-white'
                                                : 'border-slate-300 bg-white'
                                        ]"
                                    >
                                        <FontAwesomeIcon v-if="form.costing_method === 'fifo'" :icon="faCheck" class="text-[10px]" />
                                    </div>
                                </div>

                                <div>
                                    <div class="text-sm font-bold text-slate-800">
                                        FIFO (First-In, First-Out)
                                    </div>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        Masuk pertama, keluar pertama
                                    </p>
                                </div>

                                <p class="text-xs leading-relaxed text-slate-600">
                                    Stok yang masuk lebih dulu akan dikeluarkan lebih dulu saat ada penjualan atau pemakaian resep. Nilai akhir persediaan mengikuti harga beli terbaru.
                                </p>

                                <div class="rounded-lg border border-slate-100 bg-slate-50/80 p-2.5 text-[11px] text-slate-600 space-y-1">
                                    <div class="font-medium text-slate-700 flex items-center gap-1">
                                        <FontAwesomeIcon :icon="faLightbulb" class="text-amber-500" />
                                        <span>Contoh Skenario:</span>
                                    </div>
                                    <p class="leading-normal text-slate-500">
                                        Beli 10 kg telur @Rp25rb, lalu 10 kg @Rp28rb. Saat pakai 5 kg di resep, HPP yang dipakai adalah <strong class="text-slate-700">Rp25rb/kg</strong>.
                                    </p>
                                </div>
                            </div>

                            <div class="mt-3 pt-2.5 border-t border-slate-100 text-[11px] text-slate-500 flex items-center gap-1.5">
                                <FontAwesomeIcon :icon="faCheckCircle" class="text-emerald-500 shrink-0" />
                                <span>Ideal untuk kafe, restoran, bakery, farmasi & produk berkadaluwarsa.</span>
                            </div>
                        </div>

                        <!-- Option Moving Average -->
                        <div
                            class="relative flex cursor-pointer flex-col justify-between rounded-xl border p-4 transition-all duration-200 hover:border-main/70 hover:bg-slate-50/50"
                            :class="[
                                form.costing_method === 'average'
                                    ? 'border-main bg-main/5 ring-2 ring-main/20'
                                    : 'border-slate-200 bg-white'
                            ]"
                            @click="form.costing_method = 'average'"
                        >
                            <div class="space-y-2.5">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-2.5 py-0.5 text-[11px] font-medium text-indigo-700 border border-indigo-200/70">
                                        <FontAwesomeIcon :icon="faBagShopping" class="text-[10px]" />
                                        <span>Rekomendasi Retail</span>
                                    </span>
                                    <div
                                        class="flex h-5 w-5 items-center justify-center rounded-full border transition-all"
                                        :class="[
                                            form.costing_method === 'average'
                                                ? 'border-main bg-main text-white'
                                                : 'border-slate-300 bg-white'
                                        ]"
                                    >
                                        <FontAwesomeIcon v-if="form.costing_method === 'average'" :icon="faCheck" class="text-[10px]" />
                                    </div>
                                </div>

                                <div>
                                    <div class="text-sm font-bold text-slate-800">
                                        Moving Average
                                    </div>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        Rata-rata bergerak otomatis
                                    </p>
                                </div>

                                <p class="text-xs leading-relaxed text-slate-600">
                                    Harga modal dihitung dari rata-rata harga setiap kali ada barang masuk. Nilai HPP menjadi rata dan stabil tanpa perlu melacak antrean batch.
                                </p>

                                <div class="rounded-lg border border-slate-100 bg-slate-50/80 p-2.5 text-[11px] text-slate-600 space-y-1">
                                    <div class="font-medium text-slate-700 flex items-center gap-1">
                                        <FontAwesomeIcon :icon="faLightbulb" class="text-amber-500" />
                                        <span>Contoh Skenario:</span>
                                    </div>
                                    <p class="leading-normal text-slate-500">
                                        Stok 10 pcs @Rp50rb, beli lagi 10 pcs @Rp60rb. Biaya modal baru otomatis menjadi <strong class="text-slate-700">Rp55rb/pcs</strong>.
                                    </p>
                                </div>
                            </div>

                            <div class="mt-3 pt-2.5 border-t border-slate-100 text-[11px] text-slate-500 flex items-center gap-1.5">
                                <FontAwesomeIcon :icon="faCheckCircle" class="text-indigo-500 shrink-0" />
                                <span>Ideal untuk toko fashion, aksesoris, elektronik, ATK & bahan bangunan.</span>
                            </div>
                        </div>
                    </div>

                    <!-- Safety Guarantee Note -->
                    <div class="rounded-lg border border-amber-200/80 bg-amber-50/60 p-3 text-xs text-amber-800 flex items-start gap-2.5">
                        <FontAwesomeIcon :icon="faShieldHeart" class="text-amber-600 mt-0.5 text-sm shrink-0" />
                        <div class="space-y-0.5">
                            <span class="font-semibold">Perpindahan Metode Dijamin 100% Aman</span>
                            <p class="text-[11px] leading-relaxed text-amber-700">
                                Sollu App secara cerdas memelihara sinkronisasi data ganda (dual-ledger sync). Mengganti metode perhitungan tidak akan merusak atau mengubah angka penjualan masa lalu.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Sticky Bottom Action Bar -->
                <div class="flex items-center justify-between sticky bottom-4 z-10 bg-white/95 backdrop-blur-xs p-4 rounded-xl border border-slate-200">
                    <div class="text-xs text-slate-500">
                        <span v-if="hasChanges" class="text-amber-600 font-medium flex items-center gap-1">
                            <FontAwesomeIcon :icon="faInfoCircle" />
                            Ada perubahan metode yang belum disimpan.
                        </span>
                        <span v-else class="text-slate-400">
                            Pengaturan sesuai dengan status tersimpan.
                        </span>
                    </div>

                    <button
                        type="button"
                        class="btn btn-main px-5 py-2 text-sm flex items-center gap-2"
                        :disabled="form.processing || !hasChanges"
                        @click="saveCostingMethod"
                    >
                        <FontAwesomeIcon v-if="form.processing" :icon="faSpinner" class="animate-spin" />
                        <FontAwesomeIcon v-else :icon="faSave" />
                        <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan Metode' }}</span>
                    </button>
                </div>
            </div>

            <!-- Right Column: Comparative Reference Table -->
            <div class="lg:col-span-4 flex flex-col gap-4">
                <!-- Summary Stats Card -->
                <div class="bg-white rounded-xl border border-slate-200 p-4 space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-100 pb-2 flex items-center gap-2">
                        <FontAwesomeIcon :icon="faChartSimple" class="text-main" />
                        <span>Ringkasan Inventori Toko</span>
                    </h4>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500">Item Terlacak:</span>
                            <span class="font-semibold text-slate-800">{{ stats.tracked_items_count }} Item</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500">Total Nilai Persediaan:</span>
                            <span class="font-semibold text-slate-800">Rp {{ formatNumber(stats.total_stock_value) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Comparison Table Card -->
                <div class="bg-white rounded-xl border border-slate-200 p-4 space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-100 pb-2 flex items-center gap-2">
                        <FontAwesomeIcon :icon="faScaleBalanced" class="text-main" />
                        <span>Tabel Perbandingan Metode</span>
                    </h4>

                    <div class="space-y-2.5 text-xs">
                        <div class="rounded-lg border border-slate-100 bg-slate-50/60 p-2.5 space-y-1">
                            <div class="font-semibold text-slate-700">1. Prinsip Aliran Biaya</div>
                            <div class="text-[11px] text-slate-500">
                                • <strong class="text-slate-700">FIFO:</strong> Mengikuti kronologi barang masuk fisik/batch.<br>
                                • <strong class="text-slate-700">Average:</strong> Meratakan harga seluruh stok yang tersedia.
                            </div>
                        </div>

                        <div class="rounded-lg border border-slate-100 bg-slate-50/60 p-2.5 space-y-1">
                            <div class="font-semibold text-slate-700">2. Saat Terjadi Inflasi Harga</div>
                            <div class="text-[11px] text-slate-500">
                                • <strong class="text-slate-700">FIFO:</strong> HPP lebih rendah di awal, nilai sisa aset lebih tinggi.<br>
                                • <strong class="text-slate-700">Average:</strong> HPP dan nilai aset bergerak seimbang di tengah.
                            </div>
                        </div>

                        <div class="rounded-lg border border-slate-100 bg-slate-50/60 p-2.5 space-y-1">
                            <div class="font-semibold text-slate-700">3. Kepatuhan Standar Akuntansi</div>
                            <div class="text-[11px] text-slate-500">
                                Kedua metode 100% diakui dan legal sesuai SAK EMKM, PSAK 14, dan UU PPh Pasal 10 Indonesia.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainPage>
</template>

<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faBoxesStacked,
    faCalculator,
    faSliders,
    faUtensils,
    faBagShopping,
    faCheck,
    faLightbulb,
    faCheckCircle,
    faShieldHeart,
    faInfoCircle,
    faSave,
    faSpinner,
    faChartSimple,
    faScaleBalanced,
} from '@fortawesome/free-solid-svg-icons'
import { useToastStore } from '@/store/toast'

const props = defineProps({
    costingMethod: {
        type: String,
        default: 'fifo',
    },
    isConfigured: {
        type: Boolean,
        default: false,
    },
    options: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            tracked_items_count: 0,
            total_stock_value: 0,
        }),
    },
})

const toastStore = useToastStore()

const form = useForm({
    costing_method: props.costingMethod || 'fifo',
})

const hasChanges = computed(() => {
    return form.costing_method !== props.costingMethod
})

const formatNumber = num => {
    return Number(num || 0).toLocaleString('id-ID')
}

const saveCostingMethod = () => {
    form.put(route('settings.inventory.update'), {
        preserveScroll: true,
        onSuccess: () => {
            toastStore.success('Metode perhitungan aset inventaris berhasil diperbarui!')
        },
        onError: () => {
            toastStore.error('Gagal memperbarui metode perhitungan.')
        },
    })
}
</script>
