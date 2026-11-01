<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Pusat Akun"
                description="Kelola informasi profil, foto, dan keamanan akun kamu di sini."
            />
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 pb-12">
            <!-- Left Column: Forms -->
            <div class="lg:col-span-7 flex flex-col gap-4">
                <!-- Card 1: Profil Akun -->
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <h3
                        class="text-base font-semibold text-slate-800 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2"
                    >
                        <FontAwesomeIcon :icon="faUser" class="text-main" />
                        <span>Profil Akun</span>
                    </h3>

                    <div class="space-y-2">
                        <TextField
                            id="name"
                            v-model="formProfile.name"
                            label="Nama Lengkap"
                            placeholder="Masukkan nama lengkap Anda"
                            :feedback="formProfile.errors.name"
                        />

                        <div>
                            <EmailField
                                id="email"
                                v-model="formProfile.email"
                                label="Email Utama"
                                placeholder="email@anda.com"
                                :feedback="formProfile.errors.email"
                                disabled
                            />
                            <p
                                class="text-xs text-slate-400 mt-1 flex items-start gap-1.5 leading-relaxed"
                            >
                                <FontAwesomeIcon
                                    :icon="faInfoCircle"
                                    class="text-slate-400 mt-0.5"
                                />
                                <span
                                    >Email utama digunakan untuk autentikasi sistem dan tidak dapat
                                    diubah langsung.</span
                                >
                            </p>
                        </div>

                        <TextField
                            id="phone"
                            v-model="formProfile.phone"
                            label="Nomor Telepon"
                            placeholder="Contoh: 081234567890"
                            :feedback="formProfile.errors.phone"
                            type="tel"
                        />
                    </div>

                    <div class="flex justify-end pt-4 border-t border-slate-100 mt-4">
                        <button
                            class="btn btn-main flex items-center gap-2"
                            :disabled="formProfile.processing || !formProfile.isDirty"
                            @click="saveDetail"
                        >
                            <FontAwesomeIcon :icon="faSave" />
                            <span>{{
                                formProfile.processing ? 'Menyimpan...' : 'Simpan Profil'
                            }}</span>
                        </button>
                    </div>
                </div>

                <!-- Card 2: Ganti Password -->
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <h3
                        class="text-base font-semibold text-slate-800 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2"
                    >
                        <FontAwesomeIcon :icon="faLock" class="text-main" />
                        <span>Ubah Kata Sandi</span>
                    </h3>

                    <div class="space-y-2">
                        <PasswordField
                            id="current_password"
                            v-model="formChangePassword.current_password"
                            label="Kata Sandi Lama"
                            placeholder="Masukkan kata sandi lama Anda"
                            :feedback="formChangePassword.errors.current_password"
                        />

                        <PasswordField
                            id="new_password"
                            v-model="formChangePassword.new_password"
                            label="Kata Sandi Baru"
                            placeholder="Minimal 8 karakter"
                            :feedback="formChangePassword.errors.new_password"
                        />

                        <PasswordField
                            id="new_password_confirmation"
                            v-model="formChangePassword.new_password_confirmation"
                            label="Konfirmasi Kata Sandi Baru"
                            placeholder="Ulangi kata sandi baru Anda"
                            :feedback="formChangePassword.errors.new_password_confirmation"
                        />
                    </div>

                    <div class="flex justify-end pt-4 border-t border-slate-100 mt-5">
                        <button
                            class="btn btn-main flex items-center gap-2"
                            :disabled="formChangePassword.processing || !formChangePassword.isDirty"
                            @click="changePassword"
                        >
                            <FontAwesomeIcon :icon="faSave" />
                            <span>{{
                                formChangePassword.processing ? 'Menyimpan...' : 'Simpan Kata Sandi'
                            }}</span>
                        </button>
                    </div>
                </div>

                <!-- Card 3: PIN Kasir & Supervisi POS -->
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <h3
                        class="text-base font-semibold text-slate-800 border-b border-slate-100 pb-3 mb-2 flex items-center gap-2"
                    >
                        <FontAwesomeIcon :icon="faKey" class="text-main" />
                        <span>PIN Kasir & Supervisi POS</span>
                    </h3>

                    <p class="text-xs text-slate-400 mb-4 leading-relaxed">
                        PIN 6 digit digunakan untuk login cepat di aplikasi kasir (POS) serta
                        otorisasi supervisi transaksi (void, refund, dan diskon).
                    </p>

                    <div class="space-y-3">
                        <div class="space-y-1">
                            <label for="pin" class="label">PIN Baru (6 Digit)</label>
                            <div class="relative">
                                <input
                                    id="pin"
                                    v-model="formChangePin.pin"
                                    :type="showPin ? 'text' : 'password'"
                                    maxlength="6"
                                    inputmode="numeric"
                                    placeholder="Masukkan 6 angka PIN"
                                    class="form pr-10"
                                    :class="{ 'is-invalid': formChangePin.errors.pin }"
                                    @input="
                                        formChangePin.pin = formChangePin.pin.replace(/[^0-9]/g, '')
                                    "
                                />
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none"
                                    @click="showPin = !showPin"
                                >
                                    <FontAwesomeIcon
                                        :icon="showPin ? faEyeSlash : faEye"
                                        class="text-xs"
                                    />
                                </button>
                            </div>
                            <span v-if="formChangePin.errors.pin" class="form-feedback text-danger">
                                {{ formChangePin.errors.pin }}
                            </span>
                        </div>

                        <div class="space-y-1">
                            <label for="pin_confirmation" class="label">Konfirmasi PIN Baru</label>
                            <div class="relative">
                                <input
                                    id="pin_confirmation"
                                    v-model="formChangePin.pin_confirmation"
                                    :type="showPinConfirmation ? 'text' : 'password'"
                                    maxlength="6"
                                    inputmode="numeric"
                                    placeholder="Ulangi 6 angka PIN baru"
                                    class="form pr-10"
                                    :class="{ 'is-invalid': formChangePin.errors.pin_confirmation }"
                                    @input="
                                        formChangePin.pin_confirmation =
                                            formChangePin.pin_confirmation.replace(/[^0-9]/g, '')
                                    "
                                />
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none"
                                    @click="showPinConfirmation = !showPinConfirmation"
                                >
                                    <FontAwesomeIcon
                                        :icon="showPinConfirmation ? faEyeSlash : faEye"
                                        class="text-xs"
                                    />
                                </button>
                            </div>
                            <span
                                v-if="formChangePin.errors.pin_confirmation"
                                class="form-feedback text-danger"
                            >
                                {{ formChangePin.errors.pin_confirmation }}
                            </span>
                        </div>
                    </div>

                    <div class="flex justify-end pt-4 border-t border-slate-100 mt-5">
                        <button
                            class="btn btn-main flex items-center gap-2"
                            :disabled="formChangePin.processing || !formChangePin.isDirty"
                            @click="changePin"
                        >
                            <FontAwesomeIcon :icon="faSave" />
                            <span>{{
                                formChangePin.processing ? 'Menyimpan...' : 'Simpan PIN'
                            }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Column: Profile Photo Card -->
            <div class="lg:col-span-5">
                <div
                    class="sticky top-0 bg-white rounded-xl border border-slate-200 p-5 flex flex-col gap-4"
                >
                    <h3
                        class="text-base font-semibold text-slate-800 border-b border-slate-100 pb-3 mb-2 flex items-center gap-2"
                    >
                        <FontAwesomeIcon :icon="faImage" class="text-main" />
                        <span>Foto Profil</span>
                    </h3>

                    <div class="flex justify-center p-0 relative">
                        <PhotoCropper :url="profile.photo_url" @action="savePhoto" />
                    </div>

                    <div
                        class="mt-2 p-3 rounded-lg bg-blue-50/70 border border-blue-100 text-[11px] text-blue-800 leading-relaxed"
                    >
                        <strong>Petunjuk:</strong> Gunakan foto rasio 1:1 (persegi) berformat PNG,
                        JPG, atau WEBP dengan ukuran maksimal 2MB untuk hasil tampilan profil
                        terbaik.
                    </div>
                </div>
            </div>
        </div>
    </MainPage>
</template>

<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faEye,
    faEyeSlash,
    faImage,
    faInfoCircle,
    faKey,
    faLock,
    faSave,
    faUser,
} from '@fortawesome/free-solid-svg-icons'

import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import TextField from '@/Components/Form/TextField.vue'
import EmailField from '@/Components/Form/EmailField.vue'
import PasswordField from '@/Components/Form/PasswordField.vue'
import PhotoCropper from './Components/PhotoCropper.vue'

const props = defineProps({
    profile: Object,
})

const showPin = ref(false)
const showPinConfirmation = ref(false)

const formProfile = useForm({
    id: props.profile.id,
    name: props.profile.name,
    email: props.profile.email,
    phone: props.profile.phone,
})

const formPhoto = useForm({
    photo: null,
})

const formChangePassword = useForm({
    current_password: null,
    new_password: null,
    new_password_confirmation: null,
})

const formChangePin = useForm({
    pin: '',
    pin_confirmation: '',
})

const saveDetail = () => {
    formProfile.put(route('settings.account.profile.save'), {
        preserveScroll: true,
        only: ['auth', 'profile'],
    })
}

const changePassword = () => {
    formChangePassword.put(route('settings.account.profile.save.password'), {
        preserveScroll: true,
        preserveState: false,
    })
}

const changePin = () => {
    formChangePin.put(route('settings.account.profile.save.pin'), {
        preserveScroll: true,
        onSuccess: () => {
            formChangePin.reset()
        },
    })
}

const savePhoto = photo => {
    formPhoto.photo = photo
    formPhoto.post(route('settings.account.profile.save.photo'), {
        preserveScroll: true,
    })
}
</script>
