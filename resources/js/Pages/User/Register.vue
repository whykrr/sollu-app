<template>
    <form @submit.prevent="register">
        <div class="flex flex-col gap-2 h-full">
            <div class="">
                <ol class="flex items-center w-full relative gap-2">
                    <li v-for="(stepItem, index) in steps">
                        <div
                            class="h-1.5 w-20 rounded-full overflow-hidden bg-main/10"
                        >
                            <div
                                class="h-full origin-left transition-transform duration-500"
                                :class="{
                                    'scale-x-100 bg-main':
                                        currentStep >= index + 1,
                                    'scale-x-0 bg-main':
                                        currentStep < index + 1,
                                }"
                            />
                        </div>
                    </li>
                </ol>
                <div class="flex-1 justify-start">
                    <transition name="fade" mode="out-in">
                        <div
                            v-if="steps[currentStep - 1]"
                            :key="currentStep"
                            class="z-10 text-base text-gray-400 flex flex-row justify-start items-center gap-2 my-2"
                        >
                            <FontAwesomeIcon
                                :icon="steps[currentStep - 1].icon"
                                class=""
                            />
                            <span class="text-sm">{{
                                steps[currentStep - 1].label
                            }}</span>
                        </div>
                    </transition>
                </div>
            </div>
            <div class="flex-1">
                <div class="flex flex-col gap-1 h-full justify-center">
                    <div class="relative">
                        <transition name="fade-slide" mode="out-in">
                            <div
                                v-if="steps[currentStep - 1]"
                                :key="currentStep"
                                class="space-y-1 mb-4"
                            >
                                <div class="text-3xl font-semibold">
                                    {{ steps[currentStep - 1].title }}
                                </div>
                                <div class="text-sm text-gray-600">
                                    {{ steps[currentStep - 1].greetings }}
                                </div>
                            </div>
                        </transition>
                    </div>

                    <div class="bg-slate-50/60 border border-slate-200 p-3 rounded-xl space-y-2 mb-4" v-if="currentStep === 1">
                        <SelectionGroupField
                            v-model="form.business_type_id"
                            name="business_type"
                            :options="business_types"
                            class="sm"
                        />
                        <span
                            v-if="form.errors.business_type_id"
                            class="text-danger text-sm"
                        >
                            Pilih salah satu jenis usaha!
                        </span>
                    </div>
                    <div v-if="currentStep === 2" class="flex flex-col gap-2">
                        <div class="grid grid-cols-2 gap-2">
                            <div class="col-span-2">
                                <div
                                    class="form-floating"
                                    :class="{
                                        'is-invalid': form.errors.name,
                                    }"
                                >
                                    <input
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        placeholder="Email"
                                        class="bg-white/40"
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
                                        id="outlet_name"
                                        v-model="form.outlet_name"
                                        type="text"
                                        placeholder="Nama Outlet"
                                        class="bg-white/40"
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
                                        id="owner_name"
                                        v-model="form.owner_name"
                                        type="text"
                                        placeholder="Nama Pemilik"
                                        class="bg-white/40"
                                    />
                                    <label for="owner_name">Pemilik</label>
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
                                        id="email"
                                        v-model="form.email"
                                        type="text"
                                        placeholder="Nama Outlet"
                                        class="bg-white/40"
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
                                        id="phone"
                                        v-model="form.phone"
                                        type="text"
                                        placeholder="Nama Outlet"
                                        class="bg-white/40"
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
                        <div>
                            <div
                                class="form-floating"
                                :class="{
                                    'is-invalid':
                                        form.errors.password_confirmation,
                                }"
                            >
                                <input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    type="password"
                                    placeholder="Kata Sandi"
                                    class="bg-white/40"
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
                        class="btn btn-highlight-danger text-xl"
                        :disabled="currentStep === 1"
                        @click="prevStep"
                    >
                        <FontAwesomeIcon :icon="faArrowLeft" />
                    </button>
                    <button
                        v-if="currentStep < 3"
                        type="button"
                        class="btn btn-main block! px-12 text-xl"
                        :disabled="currentStep === steps.length"
                        @click="nextStep"
                    >
                        Lanjut
                    </button>
                    <button
                        v-else
                        type="submit"
                        class="btn btn-main block! px-12 text-xl"
                    >
                        Daftar Sekarang
                    </button>
                </div>
            </div>
            <div class="text">
                Sudah punya akun ?
                <Link :href="route('login')" class="underline text-blue-800">
                    Masuk
                </Link>
            </div>
        </div>
    </form>
</template>
<script setup>
import BlankLayout from '@/Layout/BlankLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import {
    FontAwesomeIcon as Fa,
    FontAwesomeIcon,
} from '@fortawesome/vue-fontawesome';
import AuthLayout from '@/Layout/AuthLayout.vue';
import {
    faArrowLeft,
    faLock,
    faStore,
    faUser,
} from '@fortawesome/free-solid-svg-icons';
import PasswordField from '@/Components/Form/PasswordField.vue';
import TextField from '@/Components/Form/TextField.vue';
import SelectionGroupField from '@/Components/Form/SelectionGroupField.vue';
import NumberField from '@/Components/Form/NumberField.vue';

const props = defineProps({
    business_types: Array,
});

const currentStep = ref(1);

const steps = [
    {
        icon: faStore,
        label: 'Jenis Usaha',
        title: 'Halo,',
        greetings: 'Pilih jenis usaha kamu untuk menyesuaikan fitur.',
    },
    {
        icon: faUser,
        label: 'Data Usaha',
        title: 'Lengkapi Data Usaha',
        greetings:
            'Cukup sedikit info bisnismu untuk mulai dengan sistem terbaik.',
    },
    {
        icon: faLock,
        label: 'Keamanan',
        title: 'Yuk, Amankan Akunmu',
        greetings: 'Lindungi akun dan data usahamu dengan kata sandi kuat.',
    },
];

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
    business_type_id: null,
    password: null,
    password_confirmation: null,
});

const register = () =>
    form.post(route('register.store'), {
        onError: () => {
            nextTick(() => {
                if (form.errors.business_type_id) {
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
