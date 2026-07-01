<template>
    <div
        v-if="gapDaysFromNow(business.created_at) < 15"
        class="flex flex-col gap-2 rounded-xl border border-neutral-200/60 bg-slate-50/50 p-2.5"
    >
        <div>
            <div
                class="inline-flex items-center gap-1.5 px-2 py-1 bg-gradient-to-r from-main to-secondary-dark text-white rounded-md text-[11px] font-medium tracking-wide"
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
                >{{ gapDaysFromNow(business.trial_end_at) }} hari</span
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
import { gapDaysFromNow } from '@/Composable/date';
import { useAuth } from '@/Composable/useAuth';
import { faBolt } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link } from '@inertiajs/vue3';

const { business, subscription } = useAuth();
</script>
