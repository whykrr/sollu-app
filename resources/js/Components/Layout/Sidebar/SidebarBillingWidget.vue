<template>
    <div
        v-if="shouldShowWidget"
        class="flex flex-col gap-2 mt-2 rounded-xl border border-neutral-200/60 bg-slate-50/50 p-2.5 shadow-sm"
    >
        <div>
            <div
                class="inline-flex items-center gap-1.5 px-2 py-1 bg-gradient-to-r from-main to-secondary-dark text-white rounded-md text-[11px] font-medium tracking-wide shadow-sm"
            >
                <FontAwesomeIcon :icon="faBolt" class="text-xs" />
                {{ subscription?.plan?.name ?? 'Masa Uji Coba' }}
            </div>
        </div>
        <div class="text-xs text-slate-600 leading-relaxed">
            Masa
            {{ subscription ? 'langganan' : 'uji coba gratis' }}
            anda akan berakhir dalam
            <span class="font-semibold text-slate-800"
                >{{ daysLeft }} hari</span
            >
        </div>
        <Link
            v-if="!subscription"
            :href="route('settings.billing.plans')"
            class="btn btn-outline-main btn-sm justify-center w-full mt-1"
        >
            Langganan Sekarang
        </Link>
        <Link
            v-else
            :href="route('settings.billing.index')"
            class="btn btn-outline-info btn-sm justify-center w-full mt-1"
        >
            Perpanjang Langganan
        </Link>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { gapDaysFromNow } from '@/Composable/date';
import { useAuth } from '@/Composable/useAuth';
import { faBolt } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link } from '@inertiajs/vue3';

const { business, subscription } = useAuth();

const shouldShowWidget = computed(() => {
    // Tampilkan selalu jika masih dalam masa uji coba (tidak ada subscription aktif)
    if (!subscription.value) {
        return true;
    }

    // Jika ada subscription, tampilkan hanya jika sisa hari kurang dari 15
    if (subscription.value.expired_at) {
        return gapDaysFromNow(subscription.value.expired_at) < 15;
    }

    return false;
});

const daysLeft = computed(() => {
    if (subscription.value && subscription.value.expired_at) {
        return gapDaysFromNow(subscription.value.expired_at);
    }

    if (business.value && business.value.trial_end_at) {
        return gapDaysFromNow(business.value.trial_end_at);
    }

    return 0;
});
</script>
