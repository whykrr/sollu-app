<template>
    <MainPage>
        <div
            class="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-6xl mx-auto items-start p-1"
        >
            <!-- Left Column: Form -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Informasi Usaha Card -->
                <div
                    class="bg-white rounded-xl border border-slate-200/80 shadow-xs p-6"
                >
                    <!-- Header -->
                    <div
                        class="flex items-center gap-3 border-b border-slate-100 pb-4 mb-6"
                    >
                        <div
                            class="flex size-10 items-center justify-center rounded-lg bg-main/10 text-main"
                        >
                            <FontAwesomeIcon
                                :icon="faBriefcase"
                                class="text-lg"
                            />
                        </div>
                        <div>
                            <h2
                                class="text-lg font-semibold text-slate-800 leading-tight"
                            >
                                Informasi Usaha
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Kelola identitas dan informasi utama usaha Anda
                            </p>
                        </div>
                    </div>

                    <!-- Form -->
                    <div class="space-y-4">
                        <!-- Business Name -->
                        <div>
                            <TextField
                                v-model="business.name"
                                label="Nama Usaha"
                                placeholder="Contoh: Sollu Coffee"
                                :class="{
                                    'is-invalid': business.errors.name,
                                }"
                                :error="business.errors.name"
                            />
                            <p
                                class="text-xs leading-relaxed text-slate-400 mt-1.5"
                            >
                                Nama usaha akan tampil pada struk, invoice,
                                laporan, dan halaman pelanggan.
                            </p>
                        </div>

                        <!-- Email -->
                        <div>
                            <EmailField
                                v-model="business.email"
                                label="Email Usaha"
                                placeholder="business@email.com"
                                :class="{
                                    'is-invalid': business.errors.email,
                                }"
                                :error="business.errors.email"
                            />
                            <p
                                class="text-xs leading-relaxed text-slate-400 mt-1.5"
                            >
                                Digunakan untuk notifikasi sistem, invoice, dan
                                informasi tagihan.
                            </p>
                        </div>

                        <!-- Owner -->
                        <div>
                            <TextField
                                v-model="business.owner_name"
                                label="Nama Pemilik"
                                placeholder="Nama pemilik usaha"
                                :class="{
                                    'is-invalid': business.errors.owner_name,
                                }"
                                :error="business.errors.owner_name"
                            />
                        </div>

                        <!-- Phone -->
                        <div>
                            <NumberField
                                v-model="business.phone"
                                label="Nomor Telepon"
                                placeholder="Contoh: 081234567890"
                                :class="{
                                    'is-invalid': business.errors.phone,
                                }"
                                :error="business.errors.phone"
                            />
                        </div>

                        <!-- Address -->
                        <div>
                            <TextareaField
                                id="address"
                                v-model="business.address"
                                label="Alamat Usaha"
                                placeholder="Masukkan alamat usaha lengkap"
                                rows="4"
                                :class="{
                                    'is-invalid': business.errors.address,
                                }"
                                :error="business.errors.address"
                            />
                            <p
                                class="text-xs leading-relaxed text-slate-400 mt-1.5"
                            >
                                Alamat dapat digunakan untuk kebutuhan invoice,
                                pengiriman, dan profil usaha.
                            </p>
                        </div>
                    </div>

                    <!-- Footer Action -->
                    <div
                        class="border-t border-slate-100 pt-5 mt-6 flex justify-end"
                    >
                        <button
                            class="btn btn-success px-6 justify-center rounded-lg w-full sm:w-auto"
                            :disabled="business.processing"
                            @click="saveDetail"
                        >
                            <FontAwesomeIcon
                                v-if="business.processing"
                                :icon="faSpinner"
                                class="animate-spin"
                            />
                            <span>{{
                                business.processing
                                    ? 'Menyimpan...'
                                    : 'Simpan Perubahan'
                            }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Column: Logo -->
            <div class="lg:col-span-1">
                <div
                    class="bg-white rounded-xl border border-slate-200/80 shadow-xs p-6 sticky top-4"
                >
                    <!-- Header -->
                    <div
                        class="flex items-center gap-3 border-b border-slate-100 pb-4 mb-6"
                    >
                        <div
                            class="flex size-10 items-center justify-center rounded-lg bg-main/10 text-main"
                        >
                            <FontAwesomeIcon :icon="faImage" class="text-lg" />
                        </div>
                        <div>
                            <h2
                                class="text-lg font-semibold text-slate-800 leading-tight"
                            >
                                Logo Usaha
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Perbarui logo resmi usaha Anda
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-center p-0 relative">
                        <LogoCropper
                            :url="auth.business.logo_url"
                            @action="saveLogo"
                        />
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
    faSpinner,
} from '@fortawesome/free-solid-svg-icons';

import MainPage from '@/Components/UI/MainPage.vue';
import TextField from '@/Components/Form/TextField.vue';
import EmailField from '@/Components/Form/EmailField.vue';
import NumberField from '@/Components/Form/NumberField.vue';
import TextareaField from '@/Components/Form/TextareaField.vue';
import LogoCropper from './Components/LogoCropper.vue';

const auth = computed(() => usePage().props.auth);

const props = defineProps({
    business: Object,
});

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
