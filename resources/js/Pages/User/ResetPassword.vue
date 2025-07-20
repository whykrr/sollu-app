<template>
    <form @submit.prevent="reset">
        <div class="flex flex-col gap-2 min-h-full justify-center">
            <div>
                <img
                    class="h-[75px]"
                    src="img/logo-fit-color.png"
                    alt="banner"
                />
            </div>
            <div>
                <div class="flex flex-col gap-1">
                    <div class="text-2xl font-bold">Atur Ulang Kata Sandi</div>
                    <div>
                        Masukkan kata sandi baru Anda dan lanjutkan aktivitas
                        bisnis Anda.
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
                <Link :href="route('login')" class="underline text-blue-800">
                    <fa icon="fa-arrow-left" />
                    Kembali</Link
                >
            </div>
        </div>
    </form>
</template>
<script setup>
import { Link, useForm, usePage } from "@inertiajs/vue3";
import AuthLayout from "@/Layout/AuthLayout.vue";

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

const reset = () => form.post(route("password.reset.attempt"));
</script>
