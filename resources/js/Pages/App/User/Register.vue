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

            <!-- Step 1: Pilih Paket Langganan & Mode Aktivasi -->
            <div v-if="currentStep === 1" class="space-y-3">
                <div
                    class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 max-h-[46vh] sm:max-h-[50vh] overflow-y-auto pr-1"
                >
                    <div
                        v-for="plan in plans"
                        :key="plan.id"
                        class="group relative flex flex-col justify-between p-3 sm:p-3.5 rounded-xl border text-left transition-all duration-200 cursor-pointer select-none"
                        :class="[
                            form.plan_id === plan.id
                                ? 'border-main bg-main/5 ring-2 ring-main/15 shadow-xs'
                                : 'border-slate-200 bg-white hover:border-main-light/50 hover:bg-slate-50/70 hover:shadow-xs',
                        ]"
                        @click="selectPlan(plan)"
                    >
                        <div>
                            <div class="flex items-start justify-between gap-1 mb-1.5">
                                <span class="font-bold text-sm sm:text-base text-slate-900">
                                    {{ plan.name }}
                                </span>
                                <div class="shrink-0">
                                    <FontAwesomeIcon
                                        v-if="form.plan_id === plan.id"
                                        :icon="faCircleCheck"
                                        class="text-main text-base"
                                    />
                                    <div
                                        v-else
                                        class="w-4 h-4 rounded-full border border-slate-300 group-hover:border-slate-400"
                                    />
                                </div>
                            </div>

                            <!-- Badges -->
                            <div class="flex flex-wrap items-center gap-1.5 mb-2">
                                <span
                                    v-if="plan.is_unlimited_outlets"
                                    class="px-2 py-0.5 text-[11px] font-semibold rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200"
                                >
                                    Unlimited Cabang
                                </span>
                                <span
                                    v-else
                                    class="px-2 py-0.5 text-[11px] font-semibold rounded-md bg-slate-100 text-slate-700 border border-slate-200"
                                >
                                    Maks. {{ plan.max_outlets }} Cabang
                                </span>

                                <span
                                    v-if="plan.has_trial"
                                    class="px-2 py-0.5 text-[11px] font-semibold rounded-md bg-amber-50 text-amber-700 border border-amber-200"
                                >
                                    Trial {{ plan.trial_duration_days }} Hari
                                </span>
                            </div>

                            <!-- Pricing -->
                            <div class="text-main font-bold text-base sm:text-lg">
                                {{ formatIDR(plan.price_per_outlet) }}
                                <span class="text-xs font-normal text-slate-500">/ outlet / bln</span>
                            </div>
                            <div
                                v-if="plan.yearly_discount_percent > 0"
                                class="text-[11px] font-medium text-emerald-600 mt-0.5"
                            >
                                Diskon tahunan {{ plan.yearly_discount_percent }}%
                            </div>

                            <!-- Features List Preview -->
                            <ul
                                v-if="plan.features && plan.features.length > 0"
                                class="mt-2.5 pt-2 border-t border-slate-100 space-y-1 text-xs text-slate-600"
                            >
                                <li
                                    v-for="feat in plan.features.slice(0, 3)"
                                    :key="feat.id"
                                    class="flex items-center gap-1.5"
                                >
                                    <FontAwesomeIcon :icon="faCheck" class="text-emerald-500 text-[10px]" />
                                    <span class="truncate">{{ feat.name }}</span>
                                </li>
                                <li
                                    v-if="plan.features.length > 3"
                                    class="text-[11px] text-slate-400 italic pl-4"
                                >
                                    + {{ plan.features.length - 3 }} fitur lainnya
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Activation Mode Options for Selected Plan -->
                <div v-if="selectedPlan" class="pt-2 border-t border-slate-200/80 space-y-2">
                    <div class="text-xs font-semibold text-slate-700">Pilihan Aktivasi:</div>

                    <div v-if="selectedPlan.has_trial" class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <!-- Option A: Free Trial -->
                        <div
                            class="p-2.5 rounded-lg border text-left cursor-pointer transition-all"
                            :class="[
                                form.use_trial === true
                                    ? 'border-main bg-main/5 ring-1 ring-main'
                                    : 'border-slate-200 bg-white hover:border-slate-300',
                            ]"
                            @click="form.use_trial = true"
                        >
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-bold text-slate-900">
                                    Coba Gratis (Trial {{ selectedPlan.trial_duration_days }} Hari)
                                </span>
                                <span
                                    class="px-1.5 py-0.5 text-[10px] font-bold rounded bg-emerald-100 text-emerald-800"
                                >
                                    Gratis
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 leading-snug">
                                Uji coba penuh tanpa biaya. Tidak memerlukan kartu kredit sekarang.
                            </p>
                        </div>

                        <!-- Option B: Direct Paid -->
                        <div
                            class="p-2.5 rounded-lg border text-left cursor-pointer transition-all"
                            :class="[
                                form.use_trial === false
                                    ? 'border-main bg-main/5 ring-1 ring-main'
                                    : 'border-slate-200 bg-white hover:border-slate-300',
                            ]"
                            @click="form.use_trial = false"
                        >
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-bold text-slate-900">
                                    Langsung Berlangganan
                                </span>
                                <span
                                    class="px-1.5 py-0.5 text-[10px] font-bold rounded bg-blue-100 text-blue-800"
                                >
                                    Bayar
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 leading-snug">
                                Mulai langsung dengan paket berbayar tanpa periode uji coba.
                            </p>
                        </div>
                    </div>

                    <!-- Non-Trial Notice -->
                    <div
                        v-else
                        class="p-2.5 rounded-lg border border-slate-200 bg-slate-50 text-xs text-slate-600"
                    >
                        Paket ini tidak menyediakan periode uji coba gratis. Anda akan diarahkan ke tagihan pembayaran awal setelah akun dibuat.
                    </div>

                    <!-- Billing Cycle Selector (if not trial or non-trial) -->
                    <div
                        v-if="!form.use_trial"
                        class="flex items-center gap-2 pt-1 text-xs"
                    >
                        <span class="text-slate-600 font-medium">Siklus Tagihan:</span>
                        <div class="inline-flex rounded-lg border border-slate-200 p-0.5 bg-slate-50">
                            <button
                                type="button"
                                class="px-3 py-1 rounded-md font-medium text-xs transition-colors cursor-pointer"
                                :class="[
                                    form.billing_cycle === 'monthly'
                                        ? 'bg-white text-main shadow-xs font-bold'
                                        : 'text-slate-600 hover:text-slate-900',
                                ]"
                                @click="form.billing_cycle = 'monthly'"
                            >
                                Bulanan
                            </button>
                            <button
                                type="button"
                                class="px-3 py-1 rounded-md font-medium text-xs transition-colors cursor-pointer"
                                :class="[
                                    form.billing_cycle === 'yearly'
                                        ? 'bg-white text-main shadow-xs font-bold'
                                        : 'text-slate-600 hover:text-slate-900',
                                ]"
                                @click="form.billing_cycle = 'yearly'"
                            >
                                Tahunan
                                <span
                                    v-if="selectedPlan.yearly_discount_percent > 0"
                                    class="text-[10px] text-emerald-600 font-bold ml-0.5"
                                >
                                    (-{{ selectedPlan.yearly_discount_percent }}%)
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <span v-if="form.errors.plan_id" class="text-danger text-xs sm:text-sm block font-medium">
                    {{ form.errors.plan_id }}
                </span>
            </div>

            <!-- Step 2: Jenis Usaha -->
            <div v-if="currentStep === 2" class="space-y-2">
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

            <!-- Step 3: Data Usaha & Outlet (5 Fields) -->
            <div v-if="currentStep === 3" class="space-y-1.5">
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

            <!-- Step 4: Keamanan Akun -->
            <div v-if="currentStep === 4" class="space-y-2">
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
                    v-if="currentStep < steps.length"
                    type="button"
                    class="btn btn-main flex-1 sm:flex-none px-8 sm:px-12 py-2 sm:py-2.5 text-base sm:text-lg justify-center"
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
import { computed, nextTick, onMounted, ref } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import AuthLayout from '@/Layout/AuthLayout.vue'
import { formatIDR } from '@/Composable/currency-format'
import {
    faArrowLeft,
    faBoxArchive,
    faCapsules,
    faCartShopping,
    faCheck,
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
    plans: Array,
    initial_plan_code: String,
    initial_use_trial: Boolean,
    initial_billing_cycle: String,
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
        icon: faBoxArchive,
        label: 'Pilih Paket',
        title: 'Pilih Paket Usahamu',
        greetings: 'Mulai dengan uji coba gratis atau langsung berlangganan fitur lengkap.',
    },
    {
        icon: faStore,
        label: 'Jenis Usaha',
        title: 'Apa Jenis Usahamu?',
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

const form = useForm({
    plan_id: null,
    use_trial: true,
    billing_cycle: 'monthly',
    name: null,
    owner_name: null,
    outlet_name: null,
    email: null,
    phone: null,
    business_type_id: null,
    password: null,
    password_confirmation: null,
})

const selectedPlan = computed(() => {
    return props.plans?.find(p => p.id === form.plan_id) || null
})

const selectPlan = plan => {
    form.plan_id = plan.id
    if (!plan.has_trial) {
        form.use_trial = false
    }
}

onMounted(() => {
    if (props.initial_plan_code && props.plans && props.plans.length > 0) {
        const matched = props.plans.find(
            p => p.code?.toLowerCase() === props.initial_plan_code.toLowerCase()
        )
        if (matched) {
            form.plan_id = matched.id
            if (props.initial_use_trial !== null && props.initial_use_trial !== undefined) {
                form.use_trial = matched.has_trial ? Boolean(props.initial_use_trial) : false
            } else {
                form.use_trial = Boolean(matched.has_trial)
            }
            if (props.initial_billing_cycle) {
                form.billing_cycle = props.initial_billing_cycle
            }
            // Auto bypass Step 1 if valid CTA plan was given
            currentStep.value = 2
            return
        }
    }

    // Default to the first available plan
    if (props.plans && props.plans.length > 0) {
        const defaultPlan = props.plans[0]
        form.plan_id = defaultPlan.id
        form.use_trial = Boolean(defaultPlan.has_trial)
        if (props.initial_billing_cycle) {
            form.billing_cycle = props.initial_billing_cycle
        }
    }
})

const nextStep = () => {
    if (currentStep.value === 1) {
        if (!form.plan_id) {
            form.errors.plan_id = 'Pilih salah satu paket langganan terlebih dahulu.'
            return
        }
        delete form.errors.plan_id
    }

    if (currentStep.value === 2) {
        if (!form.business_type_id) {
            form.errors.business_type_id = 'Pilih salah satu jenis usaha!'
            return
        }
        delete form.errors.business_type_id
    }

    if (currentStep.value < steps.length) {
        currentStep.value++
    }
}

const prevStep = () => {
    if (currentStep.value > 1) currentStep.value--
}

defineOptions({
    layout: AuthLayout,
})

const register = () =>
    form.post(route('register.store'), {
        onError: () => {
            nextTick(() => {
                if (form.errors.plan_id || form.errors.use_trial || form.errors.billing_cycle) {
                    currentStep.value = 1
                } else if (form.errors.business_type_id) {
                    currentStep.value = 2
                } else if (
                    form.errors.name ||
                    form.errors.outlet_name ||
                    form.errors.owner_name ||
                    form.errors.email ||
                    form.errors.phone
                ) {
                    currentStep.value = 3
                } else if (form.errors.password || form.errors.password_confirmation) {
                    currentStep.value = 4
                }
            })
        },
    })
</script>
