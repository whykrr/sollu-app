<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { useModalStore } from '@/store/notification'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faLock } from '@fortawesome/free-solid-svg-icons'

const props = defineProps({
    feature: {
        type: String,
        default: 'Fitur',
    },
})

const page = usePage()
const modalStore = useModalStore()

const isSubscribed = computed(() => {
    const subscription = page.props.auth?.subscription
    return Boolean(subscription && subscription.status === 'active')
})

const featureLabel = computed(() => {
    const enums = page.props.enums
    const meta = enums?.FeatureEnum?._meta?.[props.feature]
    if (meta?.label) {
        return meta.label
    }
    return props.feature
        ? props.feature.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
        : 'Fitur Eksklusif'
})
</script>

<template>
    <div class="text-center">
        <div
            class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-tr from-main/15 to-secondary/20 mb-4 shadow-inner text-main"
        >
            <FontAwesomeIcon :icon="faLock" size="2x" class="h-8 w-8 text-main" />
        </div>

        <h3 class="text-lg font-semibold text-gray-900 mb-1">Fitur Terkunci</h3>

        <div
            class="inline-block px-2.5 py-0.5 mb-3 text-xs font-medium text-main bg-sky-50 border border-sky-200 rounded-full"
        >
            {{ featureLabel }}
        </div>

        <p class="text-sm text-gray-500 mb-6 leading-relaxed">
            <template v-if="isSubscribed">
                Fitur ini tersedia pada tingkatan paket yang lebih tinggi. Tingkatkan paket
                langganan Anda sekarang untuk menikmati akses penuh dan fitur premium lainnya.
            </template>
            <template v-else>
                Fitur ini tidak tersedia pada masa uji coba atau paket gratis. Berlangganan sekarang
                untuk menikmati akses penuh ke fitur ini dan fitur eksklusif lainnya.
            </template>
        </p>

        <div class="flex flex-col gap-2">
            <Link
                :href="route('settings.billing.plans')"
                class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-sm font-semibold shadow-md bg-gradient-to-r from-main to-secondary hover:from-main-dark hover:to-secondary-dark text-white transition-all duration-200 hover:scale-[1.01] active:scale-[0.99] cursor-pointer"
                @click="modalStore.close()"
            >
                {{ isSubscribed ? 'Tingkatkan Paket Langganan' : 'Pilih Paket Langganan' }}
            </Link>

            <button
                type="button"
                class="btn btn-slate-400 justify-center"
                @click="modalStore.close()"
            >
                Nanti Saja
            </button>
        </div>
    </div>
</template>
