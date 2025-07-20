<template>
    <form @submit.prevent="register">
        <div class="flex flex-col gap-2 min-h-full justify-center">
            <div>
                <img
                    class="h-[60px]"
                    src="img/logo-fit-color.png"
                    alt="banner"
                />
            </div>
            <div>
                <div class="flex flex-col gap-1">
                    <div class="text-2xl font-bold">Halo !</div>
                    <div>
                        Daftar sekarang dan kelola bisnismu lebih mudah dan
                        efisien bersama kami
                    </div>
                    <!-- <div>Sign In to your account</div> -->
                    <div class="my-2">
                        <ol class="flex items-center w-full relative">
                            <li
                                v-for="(stepItem, index) in steps"
                                :key="index"
                                class="relative flex items-center w-full justify-center"
                            >
                                <div
                                    v-if="index < steps.length - 1"
                                    class="absolute top-1/2 left-[50%] w-full h-1 -translate-y-1/2"
                                    :class="{
                                        'bg-main': index < currentStep - 1,
                                        'bg-main-lighter':
                                            index >= currentStep - 1,
                                    }"
                                ></div>

                                <span
                                    class="z-10 flex items-center justify-center w-8 h-8 rounded-full lg:h-10 lg:w-10 shrink-0"
                                    :class="{
                                        'bg-main text-white':
                                            currentStep >= index + 1,
                                        'bg-main-lighter text-white':
                                            currentStep < index + 1,
                                    }"
                                >
                                    <fa :icon="stepItem.icon" />
                                </span>
                            </li>
                        </ol>
                    </div>
                    <div v-if="currentStep === 1">
                        <div class="mb-1">Pilih jenis usahamu!</div>
                        <div class="flex flex-wrap gap-2">
                            <div v-for="type in merchant_types">
                                <input
                                    type="radio"
                                    class="btn-check peer"
                                    name="merchant_type_id"
                                    :id="'button' + type.id"
                                    v-model="form.merchant_type_id"
                                    :value="type.id"
                                />
                                <label
                                    class="btn btn-outline-main rounded-full"
                                    :for="'button' + type.id"
                                    >{{ type.name }}</label
                                >
                            </div>
                        </div>
                        <span
                            class="text-danger text-sm"
                            v-if="form.errors.merchant_type_id"
                        >
                            Pilih salah satu jenis usaha!
                        </span>
                    </div>
                    <div class="flex flex-col gap-2" v-if="currentStep === 2">
                        <div class="grid grid-cols-2 gap-2">
                            <div class="col-span-2">
                                <div
                                    class="form-floating"
                                    :class="{
                                        'is-invalid': form.errors.name,
                                    }"
                                >
                                    <input
                                        type="text"
                                        id="name"
                                        placeholder="Email"
                                        class="bg-white/40"
                                        v-model="form.name"
                                    />
                                    <label for="name">Nama Usaha</label>
                                </div>
                                <span class="form-feedback">{{
                                    form.errors.name
                                }}</span>
                            </div>
                            <div class="col-span-2">
                                <div
                                    class="form-floating"
                                    :class="{
                                        'is-invalid': form.errors.outlet_name,
                                    }"
                                >
                                    <input
                                        type="text"
                                        id="outlet_name"
                                        placeholder="Nama Outlet"
                                        class="bg-white/40"
                                        v-model="form.outlet_name"
                                    />
                                    <label for="outlet_name">Nama Outlet</label>
                                </div>
                                <span class="form-feedback">{{
                                    form.errors.outlet_name
                                }}</span>
                            </div>
                            <div class="col-span-2">
                                <div
                                    class="form-floating"
                                    :class="{
                                        'is-invalid': form.errors.owner_name,
                                    }"
                                >
                                    <input
                                        type="text"
                                        id="outlet_name"
                                        placeholder="Nama Outlet"
                                        class="bg-white/40"
                                        v-model="form.owner_name"
                                    />
                                    <label for="outlet_name">Pemilik</label>
                                </div>
                                <span class="form-feedback">{{
                                    form.errors.owner_name
                                }}</span>
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
                                        placeholder="Nama Outlet"
                                        class="bg-white/40"
                                        v-model="form.email"
                                    />
                                    <label for="email">Email Pemilik</label>
                                </div>
                                <span class="form-feedback">{{
                                    form.errors.email
                                }}</span>
                            </div>
                            <div>
                                <div
                                    class="form-floating"
                                    :class="{
                                        'is-invalid': form.errors.phone,
                                    }"
                                >
                                    <input
                                        type="text"
                                        id="phone"
                                        placeholder="Nama Outlet"
                                        class="bg-white/40"
                                        v-model="form.phone"
                                    />
                                    <label for="phone">Telepon Pemilik</label>
                                </div>
                                <span class="form-feedback">{{
                                    form.errors.phone
                                }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-if="currentStep === 3">
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
                                    placeholder="Kata Sandi"
                                    class="bg-white/40"
                                    v-model="form.password"
                                />
                                <label for="password">Kata Sandi</label>
                            </div>
                            <span class="form-feedback">{{
                                form.errors.password
                            }}</span>
                        </div>
                        <div>
                            <div
                                class="form-floating"
                                :class="{
                                    'is-invalid':
                                        form.errors.password_confirmation,
                                }"
                            >
                                <input
                                    type="password"
                                    id="password_confirmation"
                                    placeholder="Kata Sandi"
                                    class="bg-white/40"
                                    v-model="form.password_confirmation"
                                />
                                <label for="password_confirmation"
                                    >Konfirmasi Kata Sandi</label
                                >
                            </div>
                            <span class="form-feedback">{{
                                form.errors.password_confirmation
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <div class="flex gap-2">
                    <button
                        type="button"
                        class="btn btn-highlight-danger text-lg"
                        @click="prevStep"
                        :disabled="currentStep === 1"
                    >
                        <fa icon="fa-arrow-left" />
                    </button>
                    <button
                        type="button"
                        v-if="currentStep < 3"
                        class="btn btn-main w-full block! text-lg"
                        @click="nextStep"
                        :disabled="currentStep === steps.length"
                    >
                        Lanjut
                    </button>
                    <button
                        v-else
                        type="submit"
                        class="btn btn-main w-full block! text-lg"
                    >
                        Daftar
                    </button>
                </div>
            </div>
            <div class="text">
                Sudah punya akun ?
                <Link :href="route('login')" class="underline text-blue-800"
                    >Masuk</Link
                >
            </div>
        </div>
    </form>
</template>
<script setup>
import BlankLayout from "@/Layout/BlankLayout.vue";
import { Link, useForm } from "@inertiajs/vue3";
import { nextTick, ref } from "vue";
import { FontAwesomeIcon as Fa } from "@fortawesome/vue-fontawesome";
import AuthLayout from "@/Layout/AuthLayout.vue";

const props = defineProps({
    merchant_types: Array,
});

const currentStep = ref(1);

const steps = [{ icon: "fa-store" }, { icon: "fa-user" }, { icon: "fa-lock" }];

const nextStep = () => {
    if (currentStep.value < 3) currentStep.value++;
};

const prevStep = () => {
    if (currentStep.value > 1) currentStep.value--;
};

defineOptions({
    layout: AuthLayout,
});

const form = useForm({
    name: null,
    owner_name: null,
    outlet_name: null,
    email: null,
    phone: null,
    merchant_type_id: null,
    password: null,
    password_confirmation: null,
});

const register = () =>
    form.post(route("register.store"), {
        onError: () => {
            nextTick(() => {
                if (form.errors.merchant_type_id) {
                    currentStep.value = 1;
                } else if (
                    form.errors.name ||
                    form.errors.outlet_name ||
                    form.errors.owner_name ||
                    form.errors.email ||
                    form.errors.phone
                ) {
                    currentStep.value = 2;
                } else if (
                    form.errors.password ||
                    form.errors.password_confirmation
                ) {
                    currentStep.value = 3;
                }
            });
        },
    });
</script>
