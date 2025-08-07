<template>
    <form @submit.prevent="reset">
        <div class="flex flex-col gap-2 h-full">
            <div class="w-full max-w-md space-y-6">
                <div>
                    <img
                        class="h-[60px]"
                        src="img/logo-fit-color.png"
                        alt="Logo"
                    />
                </div>
            </div>
            <div class="flex-1">
                <div class="flex flex-col gap-1 justify-center h-full">
                    <div class="space-y-1 mb-4">
                        <div class="text-3xl font-semibold">
                            Atur Ulang Kata Sandi
                        </div>
                        <div class="text-sm text-gray-600">
                            Masukkan kata sandi baru Anda dan lanjutkan
                            aktivitas bisnis Anda.
                        </div>
                    </div>
                    <div class="mb-2">
                        <div
                            class="form-floating"
                            :class="{
                                'is-invalid': form.errors.password,
                            }"
                        >
                            <input
                                type="password"
                                id="password"
                                placeholder="Kata Sandi Baru"
                                class="bg-white/40"
                                v-model="form.password"
                            />
                            <label for="name">Kata Sandi Baru</label>
                        </div>
                        <span class="form-feedback">{{
                            form.errors.password
                        }}</span>
                    </div>
                    <div>
                        <div
                            class="form-floating"
                            :class="{
                                'is-invalid': form.errors.password_confirmation,
                            }"
                        >
                            <input
                                type="password"
                                id="password"
                                placeholder="Konfirmasi Kata Sandi"
                                class="bg-white/40"
                                v-model="form.password_confirmation"
                            />
                            <label for="name">Konfirmasi Kata Sandi</label>
                        </div>
                        <span class="form-feedback">{{
                            form.errors.password_confirmation
                        }}</span>
                    </div>
                </div>
            </div>
            <div>
                <button
                    type="submit"
                    class="btn btn-main w-full block! text-lg"
                >
                    Atur Ulang
                </button>
            </div>
            <div class="text">
                <Link
                    :href="route('dashboard.login')"
                    class="underline text-blue-800"
                >
                    <fa icon="fa-arrow-left" />
                    Kembali</Link
                >
            </div>
        </div>
    </form>
</template>
<script setup>
import { Link, useForm, usePage } from "@inertiajs/vue3";
import AuthLayout from "@/Layout/Dashboard/AuthLayout.vue";

const props = defineProps({
    token: String,
    email: String,
});

defineOptions({
    layout: AuthLayout,
});

const form = useForm({
    email: props.email,
    token: props.token,
    password: null,
    password_confirmation: null,
});

const reset = () => form.post(route("dashboard.password.reset.attempt"));
</script>
