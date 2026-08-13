<template>
    <MainPage>
        <div
            class="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-6xl mx-auto items-start p-1"
        >
            <!-- Left Column: Forms -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Profil Akun Card -->
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
                            <FontAwesomeIcon :icon="faUser" class="text-lg" />
                        </div>
                        <div>
                            <h2
                                class="text-lg font-semibold text-slate-800 leading-tight"
                            >
                                Profil Akun
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Kelola identitas dan informasi akun Anda
                            </p>
                        </div>
                    </div>

                    <!-- Form -->
                    <div class="space-y-4">
                        <!-- Name -->
                        <div>
                            <TextField
                                v-model="formProfile.name"
                                label="Nama Lengkap"
                                placeholder="Masukkan nama lengkap Anda"
                                :class="{
                                    'is-invalid': formProfile.errors.name,
                                }"
                                :error="formProfile.errors.name"
                            />
                        </div>

                        <!-- Email -->
                        <div>
                            <EmailField
                                v-model="formProfile.email"
                                label="Email"
                                placeholder="email@anda.com"
                                :class="{
                                    'is-invalid': formProfile.errors.email,
                                }"
                                :error="formProfile.errors.email"
                                disabled
                            />
                            <p
                                class="text-xs leading-relaxed text-slate-400 mt-1.5 flex items-start gap-1.5"
                            >
                                <FontAwesomeIcon
                                    :icon="faInfoCircle"
                                    class="text-slate-400 mt-0.5"
                                />
                                <span
                                    >Email utama digunakan untuk masuk ke
                                    aplikasi dan tidak dapat diubah.</span
                                >
                            </p>
                        </div>

                        <!-- Phone -->
                        <div>
                            <NumberField
                                v-model="formProfile.phone"
                                label="Nomor Telepon"
                                placeholder="Contoh: 081234567890"
                                :class="{
                                    'is-invalid': formProfile.errors.phone,
                                }"
                                :error="formProfile.errors.phone"
                            />
                        </div>
                    </div>

                    <!-- Footer Action -->
                    <div
                        class="border-t border-slate-100 pt-5 mt-6 flex justify-end"
                    >
                        <button
                            class="btn btn-success px-6 justify-center rounded-lg w-full sm:w-auto"
                            :disabled="formProfile.processing"
                            @click="saveDetail"
                        >
                            <FontAwesomeIcon
                                v-if="formProfile.processing"
                                :icon="faSpinner"
                                class="animate-spin"
                            />
                            <span>{{
                                formProfile.processing
                                    ? 'Menyimpan...'
                                    : 'Simpan Perubahan'
                            }}</span>
                        </button>
                    </div>
                </div>

                <!-- Ganti Password Card -->
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
                            <FontAwesomeIcon :icon="faLock" class="text-lg" />
                        </div>
                        <div>
                            <h2
                                class="text-lg font-semibold text-slate-800 leading-tight"
                            >
                                Ubah Kata Sandi
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Amankan akun Anda dengan memperbarui kata sandi
                                secara berkala
                            </p>
                        </div>
                    </div>

                    <!-- Form -->
                    <div class="space-y-4">
                        <!-- Old Password -->
                        <div>
                            <PasswordField
                                v-model="formChangePassword.current_password"
                                label="Kata Sandi Lama"
                                placeholder="Masukkan kata sandi lama Anda"
                                :class="{
                                    'is-invalid':
                                        formChangePassword.errors
                                            .current_password,
                                }"
                                :error="
                                    formChangePassword.errors.current_password
                                "
                            />
                        </div>

                        <!-- New Password -->
                        <div>
                            <PasswordField
                                v-model="formChangePassword.new_password"
                                label="Kata Sandi Baru"
                                placeholder="Masukkan kata sandi baru (min. 8 karakter)"
                                :class="{
                                    'is-invalid':
                                        formChangePassword.errors.new_password,
                                }"
                                :error="formChangePassword.errors.new_password"
                            />
                        </div>

                        <!-- New Password Confirm-->
                        <div>
                            <PasswordField
                                v-model="
                                    formChangePassword.new_password_confirmation
                                "
                                label="Konfirmasi Kata Sandi Baru"
                                placeholder="Masukkan kembali kata sandi baru Anda"
                                :class="{
                                    'is-invalid':
                                        formChangePassword.errors
                                            .new_password_confirmation,
                                }"
                                :error="
                                    formChangePassword.errors
                                        .new_password_confirmation
                                "
                            />
                        </div>
                    </div>

                    <!-- Footer Action -->
                    <div
                        class="border-t border-slate-100 pt-5 mt-6 flex justify-end"
                    >
                        <button
                            class="btn btn-success px-6 justify-center rounded-lg w-full sm:w-auto"
                            :disabled="formChangePassword.processing"
                            @click="changePassword"
                        >
                            <FontAwesomeIcon
                                v-if="formChangePassword.processing"
                                :icon="faSpinner"
                                class="animate-spin"
                            />
                            <span>{{
                                formChangePassword.processing
                                    ? 'Menyimpan...'
                                    : 'Simpan Kata Sandi'
                            }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Column: Profile Picture -->
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
                                Foto Profil
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Perbarui foto profil akun Anda
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-center p-0 relative">
                        <PhotoCropper
                            @action="savePhoto"
                            :url="profile.photo"
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
    faUser,
    faLock,
    faImage,
    faSpinner,
    faInfoCircle,
} from '@fortawesome/free-solid-svg-icons';

import MainPage from '@/Components/UI/MainPage.vue';
import TextField from '@/Components/Form/TextField.vue';
import EmailField from '@/Components/Form/EmailField.vue';
import NumberField from '@/Components/Form/NumberField.vue';
import PasswordField from '@/Components/Form/PasswordField.vue';
import PhotoCropper from './Components/PhotoCropper.vue';

const auth = computed(() => usePage().props.auth);

const props = defineProps({
    profile: Object,
});

const formProfile = useForm({
    id: props.profile.id,
    name: props.profile.name,
    email: props.profile.email,
    phone: props.profile.phone,
});

const formPhoto = useForm({
    photo: null,
});

const formChangePassword = useForm({
    current_password: null,
    new_password: null,
    new_password_confirmation: null,
});

const saveDetail = () => {
    formProfile.put(route('settings.account.profile.save'), {
        preserveScroll: true,
        only: ['auth', 'profile'],
    });
};

const changePassword = () => {
    formChangePassword.put(route('settings.account.profile.save.password'), {
        preserveScroll: true,
        preserveState: false,
    });
};

const savePhoto = (photo) => {
    formPhoto.photo = photo;
    formPhoto.post(route('settings.account.profile.save.photo'), {
        preserveScroll: true,
    });
};
</script>
