<template>
    <form class="h-full flex flex-col justify-between" @submit.prevent="forgot">
        <!-- Top: Logo -->
        <div class="w-full max-w-md shrink-0">
            <img
                class="h-7 sm:h-9 object-contain"
                src="img/logo-colored.png"
                alt="Logo"
            />
        </div>

        <!-- Middle: Form Inputs -->
        <div
            class="flex flex-col justify-center my-auto py-2 sm:py-3 space-y-2 sm:space-y-3"
        >
            <div class="space-y-0.5">
                <div
                    class="text-xl sm:text-2xl lg:text-3xl font-bold text-neutral-900 leading-tight"
                >
                    Lupa Kata Sandi
                </div>
                <div class="text-xs sm:text-sm text-gray-600">
                    Masukkan email Anda untuk atur ulang kata sandimu dan
                    lanjutkan bisnismu.
                </div>
            </div>

            <div v-if="flashSuccess" class="alert alert-info py-1.5 px-3 mb-1">
                <div class="flex justify-between items-center">
                    <span class="text-xs sm:text-sm">{{ flashSuccess }}</span>
                </div>
            </div>

            <div class="space-y-2">
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
                            class="bg-white"
                        />
                        <label for="email">Email</label>
                    </div>
                    <span class="form-feedback">{{ form.errors.email }}</span>
                </div>
            </div>
        </div>

        <!-- Bottom: Actions & Back Link -->
        <div class="shrink-0 space-y-2 pt-1">
            <button
                type="submit"
                class="btn btn-main w-full sm:w-auto px-10 py-2 sm:py-2.5 text-base sm:text-lg justify-center"
            >
                Kirim Tautan
            </button>
            <div class="text-xs sm:text-sm text-neutral-600">
                <Link
                    :href="route('login')"
                    class="underline text-blue-800 font-medium inline-flex items-center gap-1.5"
                >
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
