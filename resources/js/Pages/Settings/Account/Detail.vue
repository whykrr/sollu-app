<template>
    <Container>
        <div class="flex flex-row gap-4 min-h-full justify-center">
            <div class="w-[50%] space-y-4">
                <div
                    class="flex w-full flex-col rounded-lg border bg-white p-4"
                >
                    <!-- Header -->
                    <div class="mb-4 flex items-start justify-between">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-800">
                                Profil Akun
                            </h2>

                            <p class="text-sm text-slate-500">
                                Kelola identitas dan informasi akun anda
                            </p>
                        </div>
                    </div>

                    <!-- Form -->
                    <div class="space-y-2">
                        <!-- Business Name -->
                        <div class="space-y-0">
                            <TextField
                                v-model="formProfile.name"
                                label="Nama"
                                placeholder="Contoh: Sollu Coffee"
                                :class="{
                                    'is-invalid': formProfile.errors.name,
                                }"
                                :feedback="formProfile.errors.name"
                            />
                        </div>

                        <!-- Email -->
                        <div class="space-y-0">
                            <EmailField
                                v-model="formProfile.email"
                                label="Email"
                                placeholder="business@email.com"
                                :class="{
                                    'is-invalid': formProfile.errors.email,
                                }"
                                :feedback="formProfile.errors.email"
                                disabled
                            />

                            <p class="text-xs leading-relaxed text-slate-400">
                                Digunakan untuk masuk aplikasi dan notifikasi,
                            </p>
                        </div>

                        <!-- Phone -->
                        <div class="space-y-0">
                            <NumberField
                                v-model="formProfile.phone"
                                label="Nomor Telepon"
                                placeholder="08xxxxxxxxxx"
                                :class="{
                                    'is-invalid': formProfile.errors.phone,
                                }"
                                :feedback="formProfile.errors.phone"
                            />
                        </div>
                    </div>

                    <!-- Footer Action -->
                    <div class="border-slate-100 pt-5">
                        <button
                            class="btn btn-success w-full justify-center rounded-lg"
                            :disabled="formProfile.processing"
                            @click="saveDetail"
                        >
                            <span v-if="formProfile.processing">
                                Menyimpan...
                            </span>

                            <span v-else> Simpan Perubahan </span>
                        </button>
                    </div>
                </div>

                <!-- Ganti Password -->
                <div
                    class="flex w-full flex-col rounded-lg border bg-white p-4"
                >
                    <!-- Header -->
                    <div class="mb-4 flex items-start justify-between">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-800">
                                Ubah Kata Sandi
                            </h2>

                            <p class="text-sm text-slate-500">
                                Kelola kata sandi akun anda
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <!-- Old Password -->
                        <div class="space-y-0">
                            <PasswordField
                                v-model="formChangePassword.current_password"
                                label="Kata Sandi Lama"
                                :class="{
                                    'is-invalid':
                                        formChangePassword.errors
                                            .current_password,
                                }"
                                :feedback="
                                    formChangePassword.errors.current_password
                                "
                            />
                        </div>

                        <!-- New Password -->
                        <div class="space-y-0">
                            <PasswordField
                                v-model="formChangePassword.new_password"
                                label="Kata Sandi Baru"
                                :class="{
                                    'is-invalid':
                                        formChangePassword.errors.new_password,
                                }"
                                :feedback="
                                    formChangePassword.errors.new_password
                                "
                            />
                        </div>

                        <!-- New Password Confirm-->
                        <div class="space-y-0">
                            <PasswordField
                                v-model="
                                    formChangePassword.new_password_confirmation
                                "
                                label="Konfirmasi Kata Sandi Baru"
                                :class="{
                                    'is-invalid':
                                        formChangePassword.errors
                                            .new_password_confirmation,
                                }"
                                :feedback="
                                    formChangePassword.errors
                                        .new_password_confirmation
                                "
                            />
                        </div>
                    </div>

                    <!-- Footer Action -->
                    <div class="border-slate-100 pt-5">
                        <button
                            class="btn btn-success w-full justify-center rounded-lg"
                            :disabled="changePassword.processing"
                            @click="changePassword"
                        >
                            <span v-if="changePassword.processing">
                                Menyimpan...
                            </span>

                            <span v-else> Simpan Kata Sandi </span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="w-80">
                <div
                    class="bg-white rounded-lg p-4 space-y-2 border sticky top-0"
                >
                    <div class="font-semibold text-lg">Foto Profil</div>
                    <div class="p-0 relative">
                        <PhotoCropper
                            @action="savePhoto"
                            :url="profile.photo"
                        />
                    </div>
                </div>
            </div>
        </div>
        <Modal
            title="Upload Logo"
            :class="{ show: showModalUploadLogo }"
            @close="showModalUploadLogo = false"
        >
            <LogoCropper @action="saveLogo" />
        </Modal>
    </Container>
</template>
<script setup>
import EmailField from '@/Components/Form/EmailField.vue';
import NumberField from '@/Components/Form/NumberField.vue';
import TextareaField from '@/Components/Form/TextareaField.vue';
import TextField from '@/Components/Form/TextField.vue';
import Container from '@/Components/UI/Container.vue';
import { faCamera, faPencil } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useForm, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Notifications/Modal.vue';
import 'vue-advanced-cropper/dist/style.css';
import { computed, ref } from 'vue';
import PhotoCropper from './Components/PhotoCropper.vue';
import PasswordField from '@/Components/Form/PasswordField.vue';

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
