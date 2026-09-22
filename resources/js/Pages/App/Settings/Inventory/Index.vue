<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Pengaturan Inventori"
                description="Kelola metode perhitungan nilai aset stok dan kebijakan pemisahan tugas (kontrol internal) di tokomu"
            />
        </template>

        <!-- Navigation Tabs -->
        <div class="flex items-center gap-1 border-b border-slate-200 mb-4">
            <button
                type="button"
                class="px-4 py-2.5 text-xs font-semibold border-b-2 transition-colors flex items-center gap-2"
                :class="[
                    activeTab === 'costing'
                        ? 'border-main text-main font-bold'
                        : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
                ]"
                @click="activeTab = 'costing'"
            >
                <FontAwesomeIcon :icon="faCalculator" />
                <span>Metode Perhitungan Aset</span>
            </button>
            <button
                type="button"
                class="px-4 py-2.5 text-xs font-semibold border-b-2 transition-colors flex items-center gap-2"
                :class="[
                    activeTab === 'sod'
                        ? 'border-main text-main font-bold'
                        : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
                ]"
                @click="activeTab = 'sod'"
            >
                <FontAwesomeIcon :icon="faUserShield" />
                <span>Pemisahan Tugas (SoD)</span>
                <span
                    class="badge text-[10px] px-1.5 py-0.2"
                    :class="formSod.enabled ? 'badge-success' : 'badge-gray'"
                >
                    {{ formSod.enabled ? 'Aktif' : 'Fleksibel' }}
                </span>
            </button>
        </div>

        <!-- TAB 1: Costing Method -->
        <div v-show="activeTab === 'costing'" class="grid grid-cols-1 lg:grid-cols-12 gap-4 pb-12">
            <!-- Left Column: Settings Form & Selection -->
            <div class="lg:col-span-8 flex flex-col gap-4">
                <!-- Card 1: Active Status Banner -->
                <div
                    class="bg-white rounded-xl border border-slate-200 p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
                >
                    <div class="flex items-start gap-3.5">
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-white font-bold"
                            :class="
                                formCosting.costing_method === 'fifo'
                                    ? 'bg-emerald-600'
                                    : 'bg-indigo-600'
                            "
                        >
                            <FontAwesomeIcon
                                :icon="
                                    formCosting.costing_method === 'fifo'
                                        ? faBoxesStacked
                                        : faCalculator
                                "
                                class="text-lg"
                            />
                        </div>
                        <div class="space-y-0.5">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium text-slate-500"
                                    >Metode Aktif Saat Ini:</span
                                >
                                <span
                                    class="badge text-xs font-semibold px-2 py-0.5"
                                    :class="
                                        formCosting.costing_method === 'fifo'
                                            ? 'badge-success'
                                            : 'badge-info'
                                    "
                                >
                                    {{
                                        formCosting.costing_method === 'fifo'
                                            ? 'FIFO (First-In, First-Out)'
                                            : 'Moving Average (Rata-Rata Bergerak)'
                                    }}
                                </span>
                            </div>
                            <h3 class="text-base font-bold text-slate-800">
                                {{
                                    formCosting.costing_method === 'fifo'
                                        ? 'Masuk Pertama, Keluar Pertama'
                                        : 'Rata-Rata Bergerak Otomatis'
                                }}
                            </h3>
                            <p class="text-xs text-slate-500">
                                {{
                                    formCosting.costing_method === 'fifo'
                                        ? 'Stok lama keluar lebih dulu, nilai aset di gudang mencerminkan harga pembelian paling aktual.'
                                        : 'Biaya dihitung dari rata-rata tertimbang setiap stok masuk baru, nilai HPP lebih stabil.'
                                }}
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
                                Klik salah satu opsi di bawah ini untuk mengubah metode perhitungan
                                aset bisnismu.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                        <!-- Option FIFO -->
                        <div
                            class="relative flex cursor-pointer flex-col justify-between rounded-xl border p-4 transition-all duration-200 hover:border-main/70 hover:bg-slate-50/50"
                            :class="[
                                formCosting.costing_method === 'fifo'
                                    ? 'border-main bg-main/5 ring-2 ring-main/20'
                                    : 'border-slate-200 bg-white',
                            ]"
                            @click="formCosting.costing_method = 'fifo'"
                        >
                            <div class="space-y-2.5">
                                <div class="flex items-center justify-between gap-2">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-medium text-emerald-700 border border-emerald-200/70"
                                    >
                                        <FontAwesomeIcon :icon="faUtensils" class="text-[10px]" />
                                        <span>Rekomendasi F&B</span>
                                    </span>
                                    <div
                                        class="flex h-5 w-5 items-center justify-center rounded-full border transition-all"
                                        :class="[
                                            formCosting.costing_method === 'fifo'
                                                ? 'border-main bg-main text-white'
                                                : 'border-slate-300 bg-white',
                                        ]"
                                    >
                                        <FontAwesomeIcon
                                            v-if="formCosting.costing_method === 'fifo'"
                                            :icon="faCheck"
                                            class="text-[10px]"
                                        />
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
                                    Stok yang masuk lebih dulu akan dikeluarkan lebih dulu saat ada
                                    penjualan atau pemakaian resep. Nilai akhir persediaan mengikuti
                                    harga beli terbaru.
                                </p>

                                <div
                                    class="rounded-lg border border-slate-100 bg-slate-50/80 p-2.5 text-[11px] text-slate-600 space-y-1"
                                >
                                    <div class="font-medium text-slate-700 flex items-center gap-1">
                                        <FontAwesomeIcon
                                            :icon="faLightbulb"
                                            class="text-amber-500"
                                        />
                                        <span>Contoh Skenario:</span>
                                    </div>
                                    <p class="leading-normal text-slate-500">
                                        Beli 10 kg telur @Rp25rb, lalu 10 kg @Rp28rb. Saat pakai 5
                                        kg di resep, HPP yang dipakai adalah
                                        <strong class="text-slate-700">Rp25rb/kg</strong>.
                                    </p>
                                </div>
                            </div>

                            <div
                                class="mt-3 pt-2.5 border-t border-slate-100 text-[11px] text-slate-500 flex items-center gap-1.5"
                            >
                                <FontAwesomeIcon
                                    :icon="faCheckCircle"
                                    class="text-emerald-500 shrink-0"
                                />
                                <span
                                    >Ideal untuk kafe, restoran, bakery, farmasi & produk
                                    berkadaluwarsa.</span
                                >
                            </div>
                        </div>

                        <!-- Option Moving Average -->
                        <div
                            class="relative flex cursor-pointer flex-col justify-between rounded-xl border p-4 transition-all duration-200 hover:border-main/70 hover:bg-slate-50/50"
                            :class="[
                                formCosting.costing_method === 'average'
                                    ? 'border-main bg-main/5 ring-2 ring-main/20'
                                    : 'border-slate-200 bg-white',
                            ]"
                            @click="formCosting.costing_method = 'average'"
                        >
                            <div class="space-y-2.5">
                                <div class="flex items-center justify-between gap-2">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-2.5 py-0.5 text-[11px] font-medium text-indigo-700 border border-indigo-200/70"
                                    >
                                        <FontAwesomeIcon
                                            :icon="faBagShopping"
                                            class="text-[10px]"
                                        />
                                        <span>Rekomendasi Retail</span>
                                    </span>
                                    <div
                                        class="flex h-5 w-5 items-center justify-center rounded-full border transition-all"
                                        :class="[
                                            formCosting.costing_method === 'average'
                                                ? 'border-main bg-main text-white'
                                                : 'border-slate-300 bg-white',
                                        ]"
                                    >
                                        <FontAwesomeIcon
                                            v-if="formCosting.costing_method === 'average'"
                                            :icon="faCheck"
                                            class="text-[10px]"
                                        />
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
                                    Harga modal dihitung dari rata-rata harga setiap kali ada barang
                                    masuk. Nilai HPP menjadi rata dan stabil tanpa perlu melacak
                                    antrean batch.
                                </p>

                                <div
                                    class="rounded-lg border border-slate-100 bg-slate-50/80 p-2.5 text-[11px] text-slate-600 space-y-1"
                                >
                                    <div class="font-medium text-slate-700 flex items-center gap-1">
                                        <FontAwesomeIcon
                                            :icon="faLightbulb"
                                            class="text-amber-500"
                                        />
                                        <span>Contoh Skenario:</span>
                                    </div>
                                    <p class="leading-normal text-slate-500">
                                        Stok 10 pcs @Rp50rb, beli lagi 10 pcs @Rp60rb. Biaya modal
                                        baru otomatis menjadi
                                        <strong class="text-slate-700">Rp55rb/pcs</strong>.
                                    </p>
                                </div>
                            </div>

                            <div
                                class="mt-3 pt-2.5 border-t border-slate-100 text-[11px] text-slate-500 flex items-center gap-1.5"
                            >
                                <FontAwesomeIcon
                                    :icon="faCheckCircle"
                                    class="text-indigo-500 shrink-0"
                                />
                                <span
                                    >Ideal untuk toko fashion, aksesoris, elektronik, ATK & bahan
                                    bangunan.</span
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Safety Guarantee Note -->
                    <div
                        class="rounded-lg border border-amber-200/80 bg-amber-50/60 p-3 text-xs text-amber-800 flex items-start gap-2.5"
                    >
                        <FontAwesomeIcon
                            :icon="faShieldHeart"
                            class="text-amber-600 mt-0.5 text-sm shrink-0"
                        />
                        <div class="space-y-0.5">
                            <span class="font-semibold">Perpindahan Metode Dijamin 100% Aman</span>
                            <p class="text-[11px] leading-relaxed text-amber-700">
                                Sollu App secara cerdas memelihara sinkronisasi data ganda
                                (dual-ledger sync). Mengganti metode perhitungan tidak akan merusak
                                atau mengubah angka penjualan masa lalu.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Sticky Bottom Action Bar Costing -->
                <div
                    class="flex items-center justify-between sticky bottom-4 z-10 bg-white/95 backdrop-blur-xs p-4 rounded-xl border border-slate-200"
                >
                    <div class="text-xs text-slate-500">
                        <span
                            v-if="hasCostingChanges"
                            class="text-amber-600 font-medium flex items-center gap-1"
                        >
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
                        :disabled="formCosting.processing || !hasCostingChanges"
                        @click="saveCostingMethod"
                    >
                        <FontAwesomeIcon
                            v-if="formCosting.processing"
                            :icon="faSpinner"
                            class="animate-spin"
                        />
                        <FontAwesomeIcon v-else :icon="faSave" />
                        <span>{{
                            formCosting.processing ? 'Menyimpan...' : 'Simpan Perubahan Metode'
                        }}</span>
                    </button>
                </div>
            </div>

            <!-- Right Column: Comparative Reference Table -->
            <div class="lg:col-span-4 flex flex-col gap-4">
                <!-- Summary Stats Card -->
                <div class="bg-white rounded-xl border border-slate-200 p-4 space-y-3">
                    <h4
                        class="text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-100 pb-2 flex items-center gap-2"
                    >
                        <FontAwesomeIcon :icon="faChartSimple" class="text-main" />
                        <span>Ringkasan Inventori Toko</span>
                    </h4>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500">Item Terlacak:</span>
                            <span class="font-semibold text-slate-800"
                                >{{ stats.tracked_items_count }} Item</span
                            >
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500">Total Nilai Persediaan:</span>
                            <span class="font-semibold text-slate-800"
                                >Rp {{ formatNumber(stats.total_stock_value) }}</span
                            >
                        </div>
                    </div>
                </div>

                <!-- Comparison Table Card -->
                <div class="bg-white rounded-xl border border-slate-200 p-4 space-y-3">
                    <h4
                        class="text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-100 pb-2 flex items-center gap-2"
                    >
                        <FontAwesomeIcon :icon="faScaleBalanced" class="text-main" />
                        <span>Tabel Perbandingan Metode</span>
                    </h4>

                    <div class="space-y-2.5 text-xs">
                        <div
                            class="rounded-lg border border-slate-100 bg-slate-50/60 p-2.5 space-y-1"
                        >
                            <div class="font-semibold text-slate-700">1. Prinsip Aliran Biaya</div>
                            <div class="text-[11px] text-slate-500">
                                • <strong class="text-slate-700">FIFO:</strong> Mengikuti kronologi
                                barang masuk fisik/batch.<br />
                                • <strong class="text-slate-700">Average:</strong> Meratakan harga
                                seluruh stok yang tersedia.
                            </div>
                        </div>

                        <div
                            class="rounded-lg border border-slate-100 bg-slate-50/60 p-2.5 space-y-1"
                        >
                            <div class="font-semibold text-slate-700">
                                2. Saat Terjadi Inflasi Harga
                            </div>
                            <div class="text-[11px] text-slate-500">
                                • <strong class="text-slate-700">FIFO:</strong> HPP lebih rendah di
                                awal, nilai sisa aset lebih tinggi.<br />
                                • <strong class="text-slate-700">Average:</strong> HPP dan nilai
                                aset bergerak seimbang di tengah.
                            </div>
                        </div>

                        <div
                            class="rounded-lg border border-slate-100 bg-slate-50/60 p-2.5 space-y-1"
                        >
                            <div class="font-semibold text-slate-700">
                                3. Kepatuhan Standar Akuntansi
                            </div>
                            <div class="text-[11px] text-slate-500">
                                Kedua metode 100% diakui dan legal sesuai SAK EMKM, PSAK 14, dan UU
                                PPh Pasal 10 Indonesia.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: Segregation of Duties (SoD) -->
        <div v-show="activeTab === 'sod'" class="grid grid-cols-1 lg:grid-cols-12 gap-4 pb-12">
            <!-- Left Column: SoD Configuration -->
            <div class="lg:col-span-8 flex flex-col gap-4">
                <!-- Banner: Mode SoD Aktif -->
                <div
                    class="bg-white rounded-xl border border-slate-200 p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
                >
                    <div class="flex items-start gap-3.5">
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-white font-bold"
                            :class="formSod.enabled ? 'bg-sky-600' : 'bg-slate-600'"
                        >
                            <FontAwesomeIcon
                                :icon="formSod.enabled ? faUserShield : faHandshake"
                                class="text-lg"
                            />
                        </div>
                        <div class="space-y-0.5">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium text-slate-500"
                                    >Mode Kontrol:</span
                                >
                                <span
                                    class="badge text-xs font-semibold px-2 py-0.5"
                                    :class="formSod.enabled ? 'badge-info' : 'badge-gray'"
                                >
                                    {{
                                        formSod.enabled
                                            ? 'Pemisahan Tugas Aktif'
                                            : 'Mode Fleksibel (UMKM)'
                                    }}
                                </span>
                            </div>
                            <h3 class="text-base font-bold text-slate-800">
                                {{
                                    formSod.enabled
                                        ? 'Pemeriksaan Silang & Otorisasi Berjenjang'
                                        : 'Fleksibilitas Penuh Tanpa Approval Silang'
                                }}
                            </h3>
                            <p class="text-xs text-slate-500">
                                {{
                                    formSod.enabled
                                        ? 'Pengguna yang membuat atau mencatat transaksi dilarang menyetujui transaksi mereka sendiri demi mencegah manipulasi stok.'
                                        : 'Cocok untuk tim kecil/usaha perorangan: staf atau manajer yang sama dapat membuat sekaligus menyetujui transaksi inventori.'
                                }}
                            </p>
                        </div>
                    </div>

                    <!-- Master Switch -->
                    <div class="flex items-center gap-2 self-end sm:self-center">
                        <Switch v-model="formSod.enabled" size="lg" />
                    </div>
                </div>

                <!-- Quick Presets Card -->
                <div class="bg-white rounded-xl border border-slate-200 p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <h4
                            class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-2"
                        >
                            <FontAwesomeIcon :icon="faWandMagicSparkles" class="text-main" />
                            <span>Pilih Preset Cepat</span>
                        </h4>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                        <button
                            type="button"
                            class="p-3 text-left rounded-lg border transition-all text-xs space-y-1"
                            :class="[
                                !formSod.enabled
                                    ? 'border-main bg-main/5 font-semibold text-main'
                                    : 'border-slate-200 hover:border-slate-300 text-slate-700',
                            ]"
                            @click="applyPreset('flexible')"
                        >
                            <div class="font-bold flex items-center gap-1.5">
                                <FontAwesomeIcon :icon="faStore" class="text-xs" />
                                <span>UMKM / Fleksibel</span>
                            </div>
                            <p class="text-[11px] text-slate-500 font-normal leading-tight">
                                SoD nonaktif. Cepat dan praktis untuk 1-3 staf per cabang.
                            </p>
                        </button>

                        <button
                            type="button"
                            class="p-3 text-left rounded-lg border transition-all text-xs space-y-1"
                            :class="[
                                formSod.enabled && !formSod.rules.purchase_order_receive
                                    ? 'border-main bg-main/5 font-semibold text-main'
                                    : 'border-slate-200 hover:border-slate-300 text-slate-700',
                            ]"
                            @click="applyPreset('standard')"
                        >
                            <div class="font-bold flex items-center gap-1.5">
                                <FontAwesomeIcon :icon="faUtensils" class="text-xs" />
                                <span>Standar Operasional</span>
                            </div>
                            <p class="text-[11px] text-slate-500 font-normal leading-tight">
                                SoD aktif pada Penyesuaian & Opname. Pembelian tetap fleksibel.
                            </p>
                        </button>

                        <button
                            type="button"
                            class="p-3 text-left rounded-lg border transition-all text-xs space-y-1"
                            :class="[
                                formSod.enabled &&
                                formSod.rules.purchase_order_receive &&
                                !formSod.allow_owner_bypass
                                    ? 'border-main bg-main/5 font-semibold text-main'
                                    : 'border-slate-200 hover:border-slate-300 text-slate-700',
                            ]"
                            @click="applyPreset('strict')"
                        >
                            <div class="font-bold flex items-center gap-1.5">
                                <FontAwesomeIcon :icon="faBuilding" class="text-xs" />
                                <span>Audit Ketat Enterprise</span>
                            </div>
                            <p class="text-[11px] text-slate-500 font-normal leading-tight">
                                Seluruh 5 kontrol aktif mutlak tanpa pengecualian pemilik.
                            </p>
                        </button>
                    </div>
                </div>

                <!-- Granular Rules Card -->
                <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
                    <div class="border-b border-slate-100 pb-3">
                        <h4 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <FontAwesomeIcon :icon="faSliders" class="text-main" />
                            <span>Aturan Rinci Pemisahan Tugas</span>
                        </h4>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Pilih alur transaksi spesifik yang mewajibkan persetujuan dari orang
                            yang berbeda.
                        </p>
                    </div>

                    <div class="divide-y divide-slate-100">
                        <!-- Rule 1: Stock Adjustment -->
                        <div class="py-3.5 flex items-start justify-between gap-4">
                            <div class="space-y-1">
                                <div
                                    class="text-xs font-bold text-slate-800 flex items-center gap-2"
                                >
                                    <span>Penyesuaian Stok (Stock Adjustment)</span>
                                </div>
                                <p class="text-[11px] text-slate-500 leading-relaxed">
                                    Pembuat draf penyesuaian (koreksi, barang rusak, kadaluwarsa)
                                    dilarang menyetujui drafnya sendiri. Wajib disetujui oleh
                                    staf/supervisor lain.
                                </p>
                            </div>
                            <Switch
                                v-model="formSod.rules.stock_adjustment"
                                size="md"
                                :disabled="!formSod.enabled"
                            />
                        </div>

                        <!-- Rule 2: Stock Opname -->
                        <div class="py-3.5 flex items-start justify-between gap-4">
                            <div class="space-y-1">
                                <div
                                    class="text-xs font-bold text-slate-800 flex items-center gap-2"
                                >
                                    <span>Stock Opname & Rekonsiliasi</span>
                                </div>
                                <p class="text-[11px] text-slate-500 leading-relaxed">
                                    Petugas yang menginput hasil hitungan fisik dilarang menyetujui
                                    atau memfinalisasi penyesuaian selisih opname.
                                </p>
                            </div>
                            <Switch
                                v-model="formSod.rules.stock_opname"
                                size="md"
                                :disabled="!formSod.enabled"
                            />
                        </div>

                        <!-- Rule 3: Stock Transfer Approval -->
                        <div class="py-3.5 flex items-start justify-between gap-4">
                            <div class="space-y-1">
                                <div
                                    class="text-xs font-bold text-slate-800 flex items-center gap-2"
                                >
                                    <span>Persetujuan Transfer Stok Cabang</span>
                                </div>
                                <p class="text-[11px] text-slate-500 leading-relaxed">
                                    Staf yang mengajukan permintaan transfer antar-outlet dilarang
                                    menyetujui permintaannya sendiri.
                                </p>
                            </div>
                            <Switch
                                v-model="formSod.rules.stock_transfer_approval"
                                size="md"
                                :disabled="!formSod.enabled"
                            />
                        </div>

                        <!-- Rule 4: Stock Transfer Receive -->
                        <div class="py-3.5 flex items-start justify-between gap-4">
                            <div class="space-y-1">
                                <div
                                    class="text-xs font-bold text-slate-800 flex items-center gap-2"
                                >
                                    <span>Pemisahan Pengirim & Penerima Transfer</span>
                                </div>
                                <p class="text-[11px] text-slate-500 leading-relaxed">
                                    Staf yang mengirim barang dari outlet asal dilarang merangkap
                                    mencatat penerimaan barang di outlet tujuan.
                                </p>
                            </div>
                            <Switch
                                v-model="formSod.rules.stock_transfer_receive"
                                size="md"
                                :disabled="!formSod.enabled"
                            />
                        </div>

                        <!-- Rule 5: Purchase Order vs Goods Receipt -->
                        <div class="py-3.5 flex items-start justify-between gap-4">
                            <div class="space-y-1">
                                <div
                                    class="text-xs font-bold text-slate-800 flex items-center gap-2"
                                >
                                    <span>Pemisahan Pembuat PO & Penerima Barang</span>
                                </div>
                                <p class="text-[11px] text-slate-500 leading-relaxed">
                                    Staf pembuat pesanan pembelian (PO) dilarang mencatat penerimaan
                                    fisik surat jalan (Goods Receipt) untuk PO tersebut.
                                </p>
                            </div>
                            <Switch
                                v-model="formSod.rules.purchase_order_receive"
                                size="md"
                                :disabled="!formSod.enabled"
                            />
                        </div>
                    </div>
                </div>

                <!-- Emergency Bypass Card -->
                <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
                    <div class="flex items-start justify-between gap-4">
                        <div class="space-y-1">
                            <div class="text-xs font-bold text-slate-800 flex items-center gap-2">
                                <FontAwesomeIcon :icon="faKey" class="text-amber-500" />
                                <span>Izin Bypass Pemilik Usaha (Owner Emergency Override)</span>
                            </div>
                            <p class="text-[11px] text-slate-500 leading-relaxed">
                                Izinkan pemilik usaha (Owner / Superadmin) menyetujui transaksi
                                mereka sendiri dalam kondisi mendesak/darurat, meskipun aturan SoD
                                sedang aktif.
                            </p>
                        </div>
                        <Switch
                            v-model="formSod.allow_owner_bypass"
                            size="md"
                            :disabled="!formSod.enabled"
                        />
                    </div>
                </div>

                <!-- Sticky Bottom Action Bar SoD -->
                <div
                    class="flex items-center justify-between sticky bottom-4 z-10 bg-white/95 backdrop-blur-xs p-4 rounded-xl border border-slate-200"
                >
                    <div class="text-xs text-slate-500">
                        <span
                            v-if="hasSodChanges"
                            class="text-amber-600 font-medium flex items-center gap-1"
                        >
                            <FontAwesomeIcon :icon="faInfoCircle" />
                            Ada perubahan pengaturan pemisahan tugas yang belum disimpan.
                        </span>
                        <span v-else class="text-slate-400">
                            Pengaturan sesuai dengan status tersimpan.
                        </span>
                    </div>

                    <button
                        type="button"
                        class="btn btn-main px-5 py-2 text-sm flex items-center gap-2"
                        :disabled="formSod.processing || !hasSodChanges"
                        @click="saveSodSettings"
                    >
                        <FontAwesomeIcon
                            v-if="formSod.processing"
                            :icon="faSpinner"
                            class="animate-spin"
                        />
                        <FontAwesomeIcon v-else :icon="faSave" />
                        <span>{{
                            formSod.processing ? 'Menyimpan...' : 'Simpan Pengaturan SoD'
                        }}</span>
                    </button>
                </div>
            </div>

            <!-- Right Column: Guide & Explanations -->
            <div class="lg:col-span-4 flex flex-col gap-4">
                <!-- Info Card: Prinsip 4-Eyes -->
                <div class="bg-white rounded-xl border border-slate-200 p-4 space-y-3">
                    <h4
                        class="text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-100 pb-2 flex items-center gap-2"
                    >
                        <FontAwesomeIcon :icon="faShieldHalved" class="text-main" />
                        <span>Mengapa Butuh SoD?</span>
                    </h4>

                    <div class="space-y-2.5 text-xs text-slate-600 leading-relaxed">
                        <div
                            class="rounded-lg border border-slate-100 bg-slate-50/60 p-2.5 space-y-1"
                        >
                            <div class="font-semibold text-slate-700">
                                Prinsip 4 Mata (Four-Eyes Principle)
                            </div>
                            <p class="text-[11px] text-slate-500">
                                Setiap transaksi yang mempengaruhi nilai saldo persediaan
                                diverifikasi oleh setidaknya dua individu berbeda.
                            </p>
                        </div>

                        <div
                            class="rounded-lg border border-slate-100 bg-slate-50/60 p-2.5 space-y-1"
                        >
                            <div class="font-semibold text-slate-700">
                                Mencegah Kecurangan & Selisih
                            </div>
                            <p class="text-[11px] text-slate-500">
                                Mengurangi risiko pencurian barang yang ditutupi dengan pembuatan
                                penyesuaian stok rusak fiktif secara sepihak.
                            </p>
                        </div>

                        <div
                            class="rounded-lg border border-slate-100 bg-slate-50/60 p-2.5 space-y-1"
                        >
                            <div class="font-semibold text-slate-700">Kepatuhan Standar Audit</div>
                            <p class="text-[11px] text-slate-500">
                                Memenuhi syarat audit internal dan laporan pertanggungjawaban
                                akuntansi bisnis modern.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainPage>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import Switch from '@/Components/Form/Switch.vue'
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
    faUserShield,
    faHandshake,
    faWandMagicSparkles,
    faStore,
    faBuilding,
    faKey,
    faShieldHalved,
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
    sodSettings: {
        type: Object,
        default: () => ({
            enabled: false,
            allow_owner_bypass: true,
            rules: {
                stock_adjustment: true,
                stock_opname: true,
                stock_transfer_approval: true,
                stock_transfer_receive: true,
                purchase_order_receive: false,
                direct_purchase_allowed: true,
            },
        }),
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
const activeTab = ref('costing')

// 1. Costing Method Form
const formCosting = useForm({
    costing_method: props.costingMethod || 'fifo',
})

const hasCostingChanges = computed(() => {
    return formCosting.costing_method !== props.costingMethod
})

const saveCostingMethod = () => {
    formCosting.put(route('settings.inventory.update'), {
        preserveScroll: true,
        onSuccess: () => {
            toastStore.success('Metode perhitungan aset inventaris berhasil diperbarui!')
        },
        onError: () => {
            toastStore.error('Gagal memperbarui metode perhitungan.')
        },
    })
}

// 2. SoD Settings Form
const formSod = useForm({
    enabled: props.sodSettings?.enabled ?? false,
    allow_owner_bypass: props.sodSettings?.allow_owner_bypass ?? true,
    rules: {
        stock_adjustment: props.sodSettings?.rules?.stock_adjustment ?? true,
        stock_opname: props.sodSettings?.rules?.stock_opname ?? true,
        stock_transfer_approval: props.sodSettings?.rules?.stock_transfer_approval ?? true,
        stock_transfer_receive: props.sodSettings?.rules?.stock_transfer_receive ?? true,
        purchase_order_receive: props.sodSettings?.rules?.purchase_order_receive ?? false,
        direct_purchase_allowed: props.sodSettings?.rules?.direct_purchase_allowed ?? true,
    },
})

const hasSodChanges = computed(() => {
    return (
        formSod.enabled !== (props.sodSettings?.enabled ?? false) ||
        formSod.allow_owner_bypass !== (props.sodSettings?.allow_owner_bypass ?? true) ||
        formSod.rules.stock_adjustment !== (props.sodSettings?.rules?.stock_adjustment ?? true) ||
        formSod.rules.stock_opname !== (props.sodSettings?.rules?.stock_opname ?? true) ||
        formSod.rules.stock_transfer_approval !==
            (props.sodSettings?.rules?.stock_transfer_approval ?? true) ||
        formSod.rules.stock_transfer_receive !==
            (props.sodSettings?.rules?.stock_transfer_receive ?? true) ||
        formSod.rules.purchase_order_receive !==
            (props.sodSettings?.rules?.purchase_order_receive ?? false)
    )
})

const applyPreset = preset => {
    if (preset === 'flexible') {
        formSod.enabled = false
        formSod.allow_owner_bypass = true
        formSod.rules.stock_adjustment = true
        formSod.rules.stock_opname = true
        formSod.rules.stock_transfer_approval = true
        formSod.rules.stock_transfer_receive = true
        formSod.rules.purchase_order_receive = false
    } else if (preset === 'standard') {
        formSod.enabled = true
        formSod.allow_owner_bypass = true
        formSod.rules.stock_adjustment = true
        formSod.rules.stock_opname = true
        formSod.rules.stock_transfer_approval = false
        formSod.rules.stock_transfer_receive = false
        formSod.rules.purchase_order_receive = false
    } else if (preset === 'strict') {
        formSod.enabled = true
        formSod.allow_owner_bypass = false
        formSod.rules.stock_adjustment = true
        formSod.rules.stock_opname = true
        formSod.rules.stock_transfer_approval = true
        formSod.rules.stock_transfer_receive = true
        formSod.rules.purchase_order_receive = true
    }
}

const saveSodSettings = () => {
    formSod.put(route('settings.inventory.sod.update'), {
        preserveScroll: true,
        onSuccess: () => {
            toastStore.success('Pengaturan pemisahan tugas (SoD) berhasil diperbarui!')
        },
        onError: () => {
            toastStore.error('Gagal memperbarui pengaturan pemisahan tugas.')
        },
    })
}

const formatNumber = num => {
    return Number(num || 0).toLocaleString('id-ID')
}
</script>
