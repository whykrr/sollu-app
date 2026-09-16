<template>
    <div class="p-4 space-y-4">
        <!-- Section 1: Profil Pengguna -->
        <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-xs space-y-3">
            <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                <div class="font-semibold text-neutral-800 text-sm flex items-center gap-1.5">
                    <FontAwesomeIcon :icon="faUser" class="text-indigo-600 text-xs" />
                    Informasi Profil
                </div>
                <span
                    class="text-[10px] uppercase font-bold px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded-md"
                >
                    Cockpit User
                </span>
            </div>

            <form class="space-y-3" @submit.prevent="submitProfile">
                <TextField
                    v-model="profileForm.name"
                    label="Nama Lengkap"
                    :error="profileForm.errors.name"
                    placeholder="Masukkan nama pengguna"
                    required
                />

                <div>
                    <TextField :model-value="auth?.email || ''" label="Email Akun" disabled />
                    <p class="text-[11px] text-neutral-400 mt-1">
                        Email akun Cockpit bersifat permanen dan tidak dapat diubah.
                    </p>
                </div>

                <div class="flex justify-end pt-1">
                    <button
                        type="submit"
                        :disabled="profileForm.processing"
                        class="btn bg-indigo-600 hover:bg-indigo-700 text-white text-xs py-1.5 px-3 rounded-lg font-medium transition-colors disabled:opacity-50"
                    >
                        Simpan Profil
                    </button>
                </div>
            </form>
        </div>

        <!-- Section 2: Keamanan / Ubah Kata Sandi -->
        <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-xs space-y-3">
            <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                <div class="font-semibold text-neutral-800 text-sm flex items-center gap-1.5">
                    <FontAwesomeIcon :icon="faKey" class="text-amber-500 text-xs" />
                    Keamanan & Kata Sandi
                </div>
            </div>

            <form class="space-y-3" @submit.prevent="submitPassword">
                <PasswordField
                    v-model="passwordForm.current_password"
                    label="Kata Sandi Saat Ini"
                    :error="passwordForm.errors.current_password"
                    placeholder="••••••••"
                    required
                />

                <PasswordField
                    v-model="passwordForm.new_password"
                    label="Kata Sandi Baru"
                    :error="passwordForm.errors.new_password"
                    placeholder="Minimal 8 karakter"
                    required
                />

                <PasswordField
                    v-model="passwordForm.new_password_confirmation"
                    label="Konfirmasi Kata Sandi Baru"
                    :error="passwordForm.errors.new_password_confirmation"
                    placeholder="Ketik ulang kata sandi baru"
                    required
                />

                <div class="flex justify-end pt-1">
                    <button
                        type="submit"
                        :disabled="passwordForm.processing"
                        class="btn bg-slate-800 hover:bg-slate-900 text-white text-xs py-1.5 px-3 rounded-lg font-medium transition-colors disabled:opacity-50"
                    >
                        Ubah Kata Sandi
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import TextField from '@/Components/Form/TextField.vue'
import PasswordField from '@/Components/Form/PasswordField.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faKey, faUser } from '@fortawesome/free-solid-svg-icons'

defineOptions({
    name: 'CockpitProfilePopUp',
})

const auth = computed(() => usePage().props.auth)

const profileForm = useForm({
    name: auth.value?.name || '',
})

const passwordForm = useForm({
    current_password: '',
    new_password: '',
    new_password_confirmation: '',
})

const submitProfile = () => {
    profileForm.patch(route('cockpit.profile.update'), {
        preserveScroll: true,
    })
}

const submitPassword = () => {
    passwordForm.put(route('cockpit.profile.password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset()
        },
    })
}
</script>
