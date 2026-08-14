<template>
    <form @submit.prevent="forgot">
        <div class="flex flex-col gap-2 h-full justify-between">
            <div class="w-full max-w-md space-y-4 mb-2">
                <div>
                    <img
                        class="h-[48px] sm:h-[60px]"
                        src="img/logo-fit-color.png"
                        alt="Logo"
                    />
                </div>
            </div>
            <div class="flex-1">
                <div class="flex flex-col gap-1 justify-center h-full">
                    <div class="space-y-1 mb-4">
                        <div class="text-2xl sm:text-3xl font-semibold text-neutral-900 leading-tight">
                            Lupa Kata Sandi
                        </div>
                        <div class="text-xs sm:text-sm text-gray-600">
                            Masukkan email Anda untuk Atur ulang kata sandimu
                            sekarang dan lanjutkan bisnismu tanpa hambatan.
                        </div>
                    </div>
                    <div v-if="flashSuccess" class="alert alert-info mb-2">
                        <div class="flex justify-between items-center">
                            <span class="text-sm">{{ flashSuccess }}</span>
                        </div>
                    </div>
                    <div>
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
                </div>
            </div>
            <div class="mt-4">
                <button
                    type="submit"
                    class="btn btn-main w-full sm:w-auto px-10 text-lg sm:text-xl justify-center"
                >
                    Kirim Tautan
                </button>
            </div>
            <div class="text-sm text-neutral-600 mt-2">
                <Link :href="route('login')" class="underline text-blue-800 font-medium inline-flex items-center gap-1.5">
                    <FontAwesomeIcon :icon="faArrowLeft" />
                    Kembali ke Login
                </Link>
            </div>
        </div>
    </form>
</template>
<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AuthLayout from '@/Layout/AuthLayout.vue';
import { computed } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faArrowLeft } from '@fortawesome/free-solid-svg-icons';

const flashSuccess = computed(() => usePage().props.app.flash.success);

defineOptions({
    layout: AuthLayout,
});

const form = useForm({
    email: null,
});

const forgot = () => form.post(route('forgot.email'));
</script>
