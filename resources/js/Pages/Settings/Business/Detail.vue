<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Informasi Usaha" />
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 pb-12">
            <!-- Left Column: Business Form -->
            <div class="lg:col-span-7 flex flex-col gap-6">
                <!-- Card 1: Identitas Usaha -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-5">
                    <h3 class="text-base font-semibold text-slate-800 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2">
                        <FontAwesomeIcon :icon="faBriefcase" class="text-main" />
                        <span>Identitas & Informasi Usaha</span>
                    </h3>

                    <div class="space-y-2">
                        <div>
                            <TextField
                                id="name"
                                v-model="business.name"
                                label="Nama Usaha"
                                placeholder="Contoh: Sollu Coffee"
                                :feedback="business.errors.name"
                            />
                            <p class="text-xs text-slate-400 mt-0.5">
                                Nama usaha akan tampil pada struk belanja, invoice, dan laporan.
                            </p>
                        </div>

                        <div>
                            <EmailField
                                id="email"
                                v-model="business.email"
                                label="Email Usaha"
                                placeholder="business@email.com"
                                :feedback="business.errors.email"
                            />
                            <p class="text-xs text-slate-400 mt-0.5">
                                Digunakan untuk notifikasi sistem, invoice, dan informasi tagihan.
                            </p>
                        </div>

                        <TextField
                            id="owner_name"
                            v-model="business.owner_name"
                            label="Nama Pemilik"
                            placeholder="Nama pemilik usaha"
                            :feedback="business.errors.owner_name"
                        />

                        <NumberField
                            id="phone"
                            v-model="business.phone"
                            label="Nomor Telepon Usaha"
                            placeholder="Contoh: 081234567890"
                            :feedback="business.errors.phone"
                        />

                        <div>
                            <TextareaField
                                id="address"
                                v-model="business.address"
                                label="Alamat Lengkap Usaha"
                                placeholder="Masukkan alamat lengkap usaha"
                                rows="3"
                                :feedback="business.errors.address"
                            />
                            <p class="text-xs text-slate-400 mt-0.5">
                                Alamat dapat digunakan untuk kebutuhan struk belanja dan profil usaha.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Sticky Bottom Action Bar -->
                <div class="flex justify-end sticky bottom-4 z-10 bg-white/90 backdrop-blur-xs p-4 rounded-xl border border-slate-200 shadow-sm">
                    <button
                        class="btn btn-main px-6 py-2.5 rounded-lg shadow-sm font-medium flex items-center gap-2"
                        :disabled="business.processing"
                        @click="saveDetail"
                    >
                        <FontAwesomeIcon :icon="faSave" />
                        <span>{{ business.processing ? 'Menyimpan...' : 'Simpan Informasi Usaha' }}</span>
                    </button>
                </div>
            </div>

            <!-- Right Column: Logo Card -->
            <div class="lg:col-span-5">
                <div class="sticky top-20 bg-white rounded-xl border border-slate-200 shadow-xs p-5 flex flex-col gap-4">
                    <h3 class="text-base font-semibold text-slate-800 border-b border-slate-100 pb-3 mb-2 flex items-center gap-2">
                        <FontAwesomeIcon :icon="faImage" class="text-main" />
                        <span>Logo Usaha</span>
                    </h3>

                    <div class="flex justify-center p-0 relative">
                        <LogoCropper
                            :url="auth.business?.logo_url"
                            @action="saveLogo"
                        />
                    </div>

                    <div class="mt-2 p-3 rounded-lg bg-blue-50/70 border border-blue-100 text-[11px] text-blue-800 leading-relaxed">
                        <strong>Petunjuk:</strong> Logo usaha akan otomatis diterapkan pada kop struk transaksi kasir thermal, faktur tagihan pelanggan, dan laporan cetak.
                    </div>
                </div>
            </div>
        </div>
    </MainPage>
</template>

<script setup>
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faBriefcase,
    faImage,
    faSave,
} from '@fortawesome/free-solid-svg-icons';

import MainPage from '@/Components/UI/MainPage.vue';
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue';
import TextField from '@/Components/Form/TextField.vue';
import EmailField from '@/Components/Form/EmailField.vue';
import NumberField from '@/Components/Form/NumberField.vue';
import TextareaField from '@/Components/Form/TextareaField.vue';
import LogoCropper from './Components/LogoCropper.vue';

const props = defineProps({
    business: Object,
});

const auth = computed(() => usePage().props.auth);

const business = useForm({
    id: props.business.id,
    name: props.business.name,
    email: props.business.email,
    owner_name: props.business.owner_name,
    phone: props.business.phone,
    address: props.business.address,
});

const formLogo = useForm({
    logo: null,
});

const saveDetail = () => {
    business.put(route('settings.business.detail.save'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const saveLogo = (logo) => {
    formLogo.logo = logo;
    formLogo.post(route('settings.business.detail.save.logo'), {
        preserveState: true,
        preserveScroll: true,
        forceFormData: true,
    });
};
</script>
