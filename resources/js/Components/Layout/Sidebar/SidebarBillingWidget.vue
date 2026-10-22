<template>
    <div
        v-if="shouldShowWidget"
        class="flex flex-col gap-2 rounded-xl border border-neutral-200/80 bg-slate-50 p-2.5"
    >
        <div>
            <div
                class="inline-flex items-center gap-1.5 px-2 py-1 bg-gradient-to-r from-main to-secondary-dark text-white rounded-md text-[11px] font-medium tracking-wide"
            >
                <FontAwesomeIcon :icon="faBolt" class="text-xs" />
                {{ planBadgeLabel }}
            </div>
        </div>
        <div class="text-xs text-slate-600 leading-relaxed">
            Masa
            {{ isTrial ? 'uji coba gratis' : 'langganan' }}
            anda akan berakhir dalam
            <span class="font-semibold text-slate-800">{{ daysLeft }} hari</span>
        </div>
        <Link
            v-if="isTrial || !subscription"
            :href="route('settings.billing.plans')"
            class="btn btn-outline-main btn-sm justify-center w-full mt-1 min-h-[36px] touch-manipulation"
        >
            Langganan Sekarang
        </Link>
        <Link
            v-else
            :href="
                route('settings.billing.checkout', {
                    plan_id: subscription?.plan?.id,
                    is_renewal: 1,
                })
            "
            class="btn btn-outline-info btn-sm justify-center w-full mt-1 min-h-[36px] touch-manipulation"
        >
            Perpanjang Langganan
        </Link>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { gapDaysFromNow } from '@/Composable/date'
import { useAuth } from '@/Composable/useAuth'
import { faBolt } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { Link, usePage } from '@inertiajs/vue3'

const { subscription } = useAuth()
const page = usePage()

const isTrial = computed(() => Boolean(subscription.value?.is_trial))

const planBadgeLabel = computed(() => {
    if (!subscription.value?.plan) return 'Masa Uji Coba'
    return isTrial.value ? `${subscription.value.plan.name} (Trial)` : subscription.value.plan.name
})

const daysLeft = computed(() => {
    if (subscription.value?.expired_at) {
        return Math.max(0, gapDaysFromNow(subscription.value.expired_at))
    }
    return 0
})

const shouldShowWidget = computed(() => {
    // Sembunyikan widget jika ada invoice perpanjangan yang belum dibayar
    if (page.props.auth?.has_pending_renewal_invoice) {
        return false
    }

    if (!subscription.value) {
        return false
    }

    // Tampilkan selalu jika masih dalam masa trial
    if (isTrial.value) {
        return true
    }

    // Jika subscription berbayar, tampilkan jika sisa hari kurang dari 15
    if (subscription.value.expired_at) {
        return daysLeft.value < 15
    }

    return false
})
</script>
