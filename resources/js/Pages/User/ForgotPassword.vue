<template>
    <form @submit.prevent="forgot">
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
                    <div class="text-2xl font-bold">Lupa Kata Sandi</div>
                    <div>
                        Masukkan email Anda untuk Atur ulang kata sandimu
                        sekarang dan lanjutkan bisnismu tanpa hambatan.
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
import { computed } from "vue";

const page = usePage();
const flashSuccess = computed(() => page.props.flash.success);

defineOptions({
    layout: AuthLayout,
});

const form = useForm({
    email: null,
});

const forgot = () => form.post(route("forgot.email"));
</script>
