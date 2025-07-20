<template>
    <form @submit.prevent="login">
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
                    <div class="text-2xl font-bold">Selamat Datang !</div>
                    <div>
                        Login sekarang dan mulai kelola bisnismu dengan mudah.
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
                    <div
                        class="flex flex-row justify-between items-center mt-2"
                    >
                        <div>
                            <div class="form-check">
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
                            <Link :href="route('forgot')">Lupa kata sandi</Link>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <button
                    type="submit"
                    class="btn btn-main w-full block! text-lg"
                >
                    Login
                </button>
            </div>
            <div class="text">
                Belum punya akun ?
                <Link :href="route('register')" class="underline text-blue-800"
                    >Daftar sekarang</Link
                >
            </div>
        </div>
    </form>
</template>
<script setup>
import { Link, useForm, usePage } from "@inertiajs/vue3";
import AuthLayout from "@/Layout/AuthLayout.vue";
import { computed } from "vue";

const page = usePage();
const flashSuccess = computed(() => page.props.flash.success);

defineOptions({
    layout: AuthLayout,
});

const form = useForm({
    email: null,
    password: null,
    remember: null,
});

const login = () => form.post(route("login.attempt"));
</script>
