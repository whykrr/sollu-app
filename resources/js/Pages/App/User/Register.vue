<template>
    <form class="h-full flex flex-col justify-between" @submit.prevent="register">
        <!-- Top: Stepper Bar -->
        <div class="shrink-0 space-y-1">
            <ol class="flex items-center w-full relative gap-1.5 sm:gap-2">
                <li v-for="(stepItem, index) in steps" :key="index" class="flex-1 max-w-[5rem]">
                    <div class="h-1.5 w-full rounded-full overflow-hidden bg-main/10">
                        <div
                            class="h-full origin-left transition-transform duration-500"
                            :class="{
                                'scale-x-100 bg-main': currentStep >= index + 1,
                                'scale-x-0 bg-main': currentStep < index + 1,
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
                        class="z-10 text-xs sm:text-sm text-gray-400 flex flex-row justify-start items-center gap-1.5"
                    >
                        <FontAwesomeIcon :icon="steps[currentStep - 1].icon" class="text-xs" />
                        <span>{{ steps[currentStep - 1].label }}</span>
                    </div>
                </transition>
            </div>
        </div>

        <!-- Middle: Dynamic Step Content -->
        <div class="flex flex-col justify-center my-auto py-1 sm:py-2">
            <div class="relative mb-2 sm:mb-3">
                <transition name="fade" mode="out-in">
                    <div v-if="steps[currentStep - 1]" :key="currentStep" class="space-y-0.5">
                        <div
                            class="text-xl sm:text-2xl lg:text-3xl font-bold text-neutral-900 leading-tight"
                        >
                            {{ steps[currentStep - 1].title }}
                        </div>
                        <div class="text-xs sm:text-sm text-gray-600">
                            {{ steps[currentStep - 1].greetings }}
                        </div>
                    </div>
                </transition>
            </div>

            <!-- Step 1: Jenis Usaha -->
            <div v-if="currentStep === 1" class="space-y-2">
                <div
                    class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-[46vh] sm:max-h-[50vh] overflow-y-auto pr-1"
                >
                    <button
                        v-for="item in business_types"
                        :key="item.value"
                        type="button"
                        class="group relative flex items-center p-3 rounded-xl border text-left transition-all duration-200 cursor-pointer select-none"
                        :class="[
                            form.business_type_id === item.value
                                ? 'border-main bg-main/5 ring-2 ring-main/15 shadow-xs'
                                : 'border-slate-200 bg-white hover:border-main-light/50 hover:bg-slate-50/70 hover:shadow-xs',
                        ]"
                        @click="form.business_type_id = item.value"
                    >
                        <!-- Icon Avatar -->
                        <div
                            class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 transition-colors duration-200"
                            :class="[
                                form.business_type_id === item.value
                                    ? 'bg-main text-white shadow-xs'
                                    : 'bg-slate-100 text-slate-600 group-hover:bg-main-light/10 group-hover:text-main',
                            ]"
                        >
                            <FontAwesomeIcon :icon="getBusinessTypeIcon(item)" class="text-base" />
                        </div>

                        <!-- Name -->
                        <div class="ml-3 flex-1 min-w-0">
                            <span
                                class="font-semibold text-sm leading-snug truncate block transition-colors duration-200"
                                :class="[
                                    form.business_type_id === item.value
                                        ? 'text-main font-bold'
                                        : 'text-slate-800 group-hover:text-slate-900',
                                ]"
                            >
                                {{ item.label }}
                            </span>
                        </div>

                        <!-- Selected Badge / Indicator -->
                        <div class="ml-2 shrink-0">
                            <FontAwesomeIcon
                                v-if="form.business_type_id === item.value"
                                :icon="faCircleCheck"
                                class="text-main text-lg"
                            />
                            <div
                                v-else
                                class="w-4 h-4 rounded-full border border-slate-300 group-hover:border-slate-400"
                            />
                        </div>
                    </button>
                </div>

                <span
                    v-if="form.errors.business_type_id"
                    class="text-danger text-xs sm:text-sm block font-medium"
                >
                    Pilih salah satu jenis usaha!
                </span>
            </div>

            <!-- Step 2: Data Usaha (5 Fields) -->
            <div v-if="currentStep === 2" class="space-y-1.5">
                <div class="grid grid-cols-2 gap-1.5 sm:gap-2">
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
                                placeholder="Nama Usaha"
                                class="bg-white"
                            />
                            <label for="name">Nama Usaha</label>
                        </div>
                        <span class="form-feedback">{{ form.errors.name }}</span>
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
                                class="bg-white"
                            />
                            <label for="outlet_name">Nama Outlet</label>
                        </div>
                        <span class="form-feedback">{{ form.errors.outlet_name }}</span>
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
                                class="bg-white"
                            />
                            <label for="owner_name">Pemilik</label>
                        </div>
                        <span class="form-feedback">{{ form.errors.owner_name }}</span>
                    </div>
                    <div class="col-span-2 sm:col-span-1">
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
                                placeholder="Email Pemilik"
                                class="bg-white"
                            />
                            <label for="email">Email Pemilik</label>
                        </div>
                        <span class="form-feedback">{{ form.errors.email }}</span>
                    </div>
                    <div class="col-span-2 sm:col-span-1">
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
                                placeholder="Telepon Pemilik"
                                class="bg-white"
                            />
                            <label for="phone">Telepon Pemilik</label>
                        </div>
                        <span class="form-feedback">{{ form.errors.phone }}</span>
                    </div>
                </div>
            </div>

            <!-- Step 3: Keamanan -->
            <div v-if="currentStep === 3" class="space-y-2">
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
                            class="bg-white"
                        />
                        <label for="password">Kata Sandi</label>
                    </div>
                    <span class="form-feedback">{{ form.errors.password }}</span>
                </div>
                <div>
                    <div
                        class="form-floating"
                        :class="{
                            'is-invalid': form.errors.password_confirmation,
                        }"
                    >
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            placeholder="Kata Sandi"
                            class="bg-white"
                        />
                        <label for="password_confirmation">Konfirmasi Kata Sandi</label>
                    </div>
                    <span class="form-feedback">{{ form.errors.password_confirmation }}</span>
                </div>
            </div>
        </div>

        <!-- Bottom: Actions & Login Link -->
        <div class="shrink-0 space-y-2 pt-1">
            <div class="flex gap-2">
                <button
                    type="button"
                    class="btn btn-highlight-danger text-base sm:text-lg shrink-0 px-3 py-2 sm:py-2.5"
                    :disabled="currentStep === 1"
                    @click="prevStep"
                >
                    <FontAwesomeIcon :icon="faArrowLeft" />
                </button>
                <button
                    v-if="currentStep < 3"
                    type="button"
                    class="btn btn-main flex-1 sm:flex-none px-8 sm:px-12 py-2 sm:py-2.5 text-base sm:text-lg justify-center"
                    :disabled="currentStep === steps.length"
                    @click="nextStep"
                >
                    Lanjut
                </button>
                <button
                    v-else
                    type="submit"
                    class="btn btn-main flex-1 sm:flex-none px-8 sm:px-12 py-2 sm:py-2.5 text-base sm:text-lg justify-center"
                >
                    Daftar Sekarang
                </button>
            </div>
            <div class="text-xs sm:text-sm text-neutral-600">
                Sudah punya akun ?
                <Link :href="route('login')" class="underline text-blue-800 font-medium">
                    Masuk
                </Link>
            </div>
        </div>
    </form>
</template>
<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { nextTick, ref } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import AuthLayout from '@/Layout/AuthLayout.vue'
import {
    faArrowLeft,
    faCapsules,
    faCartShopping,
    faCircleCheck,
    faLock,
    faMugSaucer,
    faScissors,
    faShirt,
    faSpa,
    faStore,
    faUser,
    faUtensils,
    faWrench,
} from '@fortawesome/free-solid-svg-icons'

const props = defineProps({
    business_types: Array,
})

const getBusinessTypeIcon = type => {
    const code = (type?.code || '').toLowerCase()
    const name = (type?.label || type?.name || '').toLowerCase()
    const combined = `${code} ${name}`

    if (
        combined.includes('kopi') ||
        combined.includes('coffee') ||
        combined.includes('kafe') ||
        combined.includes('cafe')
    ) {
        return faMugSaucer
    }
    if (
        combined.includes('fnb') ||
        combined.includes('restoran') ||
        combined.includes('resto') ||
        combined.includes('makan') ||
        combined.includes('kuliner')
    ) {
        return faUtensils
    }
    if (
        combined.includes('barber') ||
        combined.includes('salon') ||
        combined.includes('cukur') ||
        combined.includes('pangkas')
    ) {
        return faScissors
    }
    if (
        combined.includes('laundry') ||
        combined.includes('cuci') ||
        combined.includes('fashion') ||
        combined.includes('pakaian')
    ) {
        return faShirt
    }
    if (
        combined.includes('apotek') ||
        combined.includes('farmasi') ||
        combined.includes('obat') ||
        combined.includes('klinik')
    ) {
        return faCapsules
    }
    if (
        combined.includes('bengkel') ||
        combined.includes('reparasi') ||
        combined.includes('service') ||
        combined.includes('servis')
    ) {
        return faWrench
    }
    if (combined.includes('spa') || combined.includes('massage') || combined.includes('refleksi')) {
        return faSpa
    }
    if (
        combined.includes('retail') ||
        combined.includes('ritel') ||
        combined.includes('market') ||
        combined.includes('mart') ||
        combined.includes('sembako') ||
        combined.includes('toko') ||
        combined.includes('kelontong')
    ) {
        return faCartShopping
    }

    return faStore
}

const currentStep = ref(1)

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
        greetings: 'Cukup sedikit info bisnismu untuk mulai dengan sistem terbaik.',
    },
    {
        icon: faLock,
        label: 'Keamanan',
        title: 'Yuk, Amankan Akunmu',
        greetings: 'Lindungi akun dan data usahamu dengan kata sandi kuat.',
    },
]

const nextStep = () => {
    if (currentStep.value < 3) currentStep.value++
}

const prevStep = () => {
    if (currentStep.value > 1) currentStep.value--
}

defineOptions({
    layout: AuthLayout,
})

const form = useForm({
    name: null,
    owner_name: null,
    outlet_name: null,
    email: null,
    phone: null,
    business_type_id: null,
    password: null,
    password_confirmation: null,
})

const register = () =>
    form.post(route('register.store'), {
        onError: () => {
            nextTick(() => {
                if (form.errors.business_type_id) {
                    currentStep.value = 1
                } else if (
                    form.errors.name ||
                    form.errors.outlet_name ||
                    form.errors.owner_name ||
                    form.errors.email ||
                    form.errors.phone
                ) {
                    currentStep.value = 2
                } else if (form.errors.password || form.errors.password_confirmation) {
                    currentStep.value = 3
                }
            })
        },
    })
</script>
