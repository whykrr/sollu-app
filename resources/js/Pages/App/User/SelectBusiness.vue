<template>
    <div class="h-full flex flex-col justify-between">
        <!-- Top: Logo -->
        <div class="w-full max-w-md shrink-0">
            <img class="h-7 sm:h-9 object-contain" src="img/logo-colored.png" alt="Logo" />
        </div>

        <!-- Middle: Business Selection -->
        <div class="flex flex-col justify-center my-auto py-2 sm:py-3 space-y-3">
            <div class="space-y-0.5">
                <div
                    class="text-xl sm:text-2xl lg:text-3xl font-bold text-neutral-900 leading-tight"
                >
                    Pilih Akun Bisnis
                </div>
                <div class="text-xs sm:text-sm text-gray-600">
                    Email kamu terdaftar di beberapa bisnis. Pilih bisnis yang ingin kamu kelola
                    sekarang.
                </div>
            </div>

            <div v-if="form.errors.email" class="alert alert-danger py-1.5 px-3">
                <span class="text-xs sm:text-sm">{{ form.errors.email }}</span>
            </div>

            <div class="space-y-2 max-h-[320px] overflow-y-auto pr-1">
                <div
                    v-for="biz in props.businesses"
                    :key="biz.id"
                    class="p-3.5 border border-slate-200 rounded-xl hover:border-main hover:bg-slate-50/70 transition cursor-pointer flex items-center justify-between group"
                    @click="chooseBusiness(biz.id)"
                >
                    <div class="min-w-0 pr-3">
                        <div
                            class="font-bold text-slate-800 text-sm group-hover:text-main truncate"
                        >
                            {{ biz.name }}
                        </div>
                        <span class="badge badge-info text-[10px] mt-1 inline-block">
                            {{ biz.role }}
                        </span>
                    </div>

                    <button
                        type="button"
                        class="btn btn-outline-main btn-xs shrink-0"
                        :disabled="form.processing"
                    >
                        Masuk
                    </button>
                </div>
            </div>
        </div>

        <!-- Bottom: Back to Login -->
        <div class="shrink-0 space-y-2 pt-1">
            <div class="text-xs sm:text-sm text-neutral-600">
                Bukan akun kamu?
                <Link :href="route('login')" class="underline text-blue-800 font-medium">
                    Kembali ke halaman login
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layout/AuthLayout.vue'

defineOptions({
    layout: AuthLayout,
})

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    password: {
        type: String,
        required: true,
    },
    remember: {
        type: Boolean,
        default: false,
    },
    businesses: {
        type: Array,
        default: () => [],
    },
})

const form = useForm({
    email: props.email,
    password: props.password,
    business_id: null,
    remember: props.remember,
})

const chooseBusiness = businessId => {
    form.business_id = businessId
    form.post(route('login.attempt'))
}
</script>
