<template>
    <form @submit.prevent="login">
        <div class="flex flex-col gap-2 h-full justify-between">
            <div class="w-full max-w-md space-y-4 mb-2">
                <div>
                    <img
                        class="h-[36px] sm:h-[40px]"
                        src="img/logo-colored.png"
                        alt="Logo"
                    />
                </div>
            </div>

            <div class="flex-1">
                <div class="flex flex-col gap-1 justify-center h-full">
                    <div class="space-y-1 mb-4">
                        <div class="text-2xl sm:text-3xl font-semibold text-neutral-900 leading-tight">
                            Halo, <br />Selamat Datang
                        </div>
                        <div class="text-xs sm:text-sm text-gray-600">
                            Login sekarang dan mulai kelola bisnismu dengan
                            mudah.
                        </div>
                    </div>
                    <div v-if="flashSuccess" class="alert alert-info mb-2">
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
                                id="email"
                                v-model="form.email"
                                type="text"
                                placeholder="Email"
                                class="bg-white/40"
                            />
                            <label for="email">Email</label>
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
                                id="password"
                                v-model="form.password"
                                type="password"
                                placeholder="Kata Sandi"
                                class="bg-white/40"
                            />
                            <label for="password">Kata Sandi</label>
                        </div>
                        <span class="form-feedback">{{
                            form.errors.password
                        }}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs sm:text-sm mt-2">
                        <div>
                            <div class="form-check items-center">
                                <input
                                    id="remember"
                                    v-model="form.remember"
                                    type="checkbox"
                                    name="remember"
                                />
                                <label for="remember">Ingat Saya</label>
                            </div>
                        </div>
                        <div class="text-blue-800 underline">
                            <Link :href="route('forgot')">
                                Lupa kata sandi
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-main w-full sm:w-auto px-10 text-lg sm:text-xl justify-center">
                    Login
                </button>
            </div>
            <div class="text-sm text-neutral-600 mt-2">
                Belum punya akun ?
                <Link :href="route('register')" class="underline text-blue-800 font-medium">
                    Daftar sekarang
                </Link>
            </div>
        </div>
    </form>
</template>
<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AuthLayout from '@/Layout/AuthLayout.vue';
import { computed } from 'vue';

const flashSuccess = computed(() => usePage().props.app.flash.success);

defineOptions({
    layout: AuthLayout,
});

const form = useForm({
    email: null,
    password: null,
    remember: null,
});

const login = () => form.post(route('login.attempt'));
</script>
