<template>
    <form @submit.prevent="forgot">
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
                            Lupa Kata Sandi
                        </div>
                        <div class="text-sm text-gray-600">
                            Masukkan email Anda untuk Atur ulang kata sandimu
                            sekarang dan lanjutkan bisnismu tanpa hambatan.
                        </div>
                    </div>
                    <div class="alert alert-info mb-2" v-if="flashSuccess">
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
                </div>
            </div>
            <div>
                <button
                    type="submit"
                    class="btn btn-main w-full block! text-lg"
                >
                    Kirim Tautan
                </button>
            </div>
            <div class="text">
                <Link
                    :href="route('dashboard.login')"
                    class="underline text-blue-800"
                >
                    <FontAwesomeIcon :icon="faArrowLeft" />
                    Kembali</Link
                >
            </div>
        </div>
    </form>
</template>
<script setup>
import { Link, useForm, usePage } from "@inertiajs/vue3";
import AuthLayout from "@/Layout/Dashboard/AuthLayout.vue";
import { computed } from "vue";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faArrowLeft } from "@fortawesome/free-solid-svg-icons";

const page = usePage();
const flashSuccess = computed(() => page.props.flash.success);

defineOptions({
    layout: AuthLayout,
});

const form = useForm({
    email: null,
});

const forgot = () => form.post(route("dashboard.forgot.email"));
</script>
