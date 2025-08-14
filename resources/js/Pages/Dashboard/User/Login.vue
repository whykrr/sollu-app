<template>
    <form @submit.prevent="login">
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
                            Halo, <br />Selamat Datang
                        </div>
                        <div class="text-sm text-gray-600">
                            Login sekarang dan mulai kelola bisnismu dengan
                            mudah.
                        </div>
                    </div>
                    <div class="alert alert-info mb-2" v-if="flashSuccess">
                        <div class="flex justify-between items-center">
                            <span class="text-sm">{{ flashSuccess }}</span>
                        </div>
                    </div>
                    <div class="mb-2">
                        <div
                            class="form-floating"
                            :class="{
                                'is-invalid': form.errors.email,
                            }"
                        >
                            <input
                                type="text"
                                id="email"
                                placeholder="Email"
                                class="bg-white/40"
                                v-model="form.email"
                            />
                            <label for="name">Email</label>
                        </div>
                        <span class="form-feedback">{{
                            form.errors.email
                        }}</span>
                    </div>
                    <div>
                        <div
                            class="form-floating"
                            :class="{
                                'is-invalid': form.errors.password,
                            }"
                        >
                            <input
                                type="password"
                                id="password"
                                placeholder="Kata Sandi"
                                class="bg-white/40"
                                v-model="form.password"
                            />
                            <label for="name">Kata Sandi</label>
                        </div>
                        <span class="form-feedback">{{
                            form.errors.password
                        }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm mt-2">
                        <div>
                            <div class="form-check items-center">
                                <input
                                    type="checkbox"
                                    name="remember"
                                    id="remember"
                                    v-model="form.remember"
                                />
                                <label for="remember">Ingat Saya</label>
                            </div>
                        </div>
                        <div class="text-blue-800 underline">
                            <Link :href="route('dashboard.forgot')"
                                >Lupa kata sandi</Link
                            >
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-main block! px-12 text-xl">
                    Login
                </button>
            </div>
            <div class="text">
                Belum punya akun ?
                <Link
                    :href="route('dashboard.register')"
                    class="underline text-blue-800"
                    >Daftar sekarang</Link
                >
            </div>
        </div>
    </form>
</template>
<script setup>
import { Link, useForm, usePage } from "@inertiajs/vue3";
import AuthLayout from "@/Layout/Dashboard/AuthLayout.vue";
import { computed } from "vue";

const flashSuccess = computed(() => usePage().props.app.flash.success);

defineOptions({
    layout: AuthLayout,
});

const form = useForm({
    email: null,
    password: null,
    remember: null,
});

const login = () => form.post(route("dashboard.login.attempt"));
</script>
