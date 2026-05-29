<template>
    <div class="px-2 relative space-y-2 pb-2">
        <a
            href="#"
            target="_blank"
            class="flex flex-row gap-2 items-center py-0 px-2 cursor-pointer rounded-lg text-neutral-600 hover:text-neutral-900"
        >
            <FontAwesomeIcon :icon="faCircleQuestion" />
            <div class="text-sm">Pusat Bantuan</div>
            <div class="flex-1" />
            <div>
                <FontAwesomeIcon
                    :icon="faArrowUpFromBracket"
                    class="text-[15px]"
                />
            </div>
        </a>
        <Link
            href="#"
            class="flex flex-row gap-2 items-center py-0 px-2 cursor-pointer rounded-lg text-neutral-600 hover:text-neutral-900"
        >
            <FontAwesomeIcon :icon="faCog" />
            <div class="text-sm grow">Pengaturan</div>
        </Link>

        <div
            v-if="gapDaysFromNow(auth.business.trial_end_at) > 15"
            class="flex flex-col gap-1.5 rounded-lg border border-neutral-300 p-2"
        >
            <div>
                <div
                    class="inline-block p-1.5 bg-gradient-to-r from-main to-secondary-dark text-white rounded-md text-xs"
                >
                    <FontAwesomeIcon :icon="faBolt" class="text-sm" />
                    {{ auth.subscription.plan.name ?? 'Uji Coba' }}
                </div>
            </div>
            <div class="text-xs">
                Masa
                {{
                    auth.subscription.plan.is_trial
                        ? 'uji coba gratis'
                        : 'langganan'
                }}
                anda akan berakhir dalam
                <span class="font-medium"
                    >{{ gapDaysFromNow(auth.business.trial_end_at) }} hari</span
                >
            </div>
            <Link
                v-if="auth.subscription.plan.is_trial"
                :href="route('business.billing.plans')"
                class="btn btn-outline-main btn-sm justify-center"
            >
                Langganan Sekarang
            </Link>
            <Link
                v-else
                :href="route('business.billing.index')"
                class="btn btn-outline-info btn-sm justify-center"
            >
                Perpanjang Langganan
            </Link>
        </div>
    </div>
</template>

<script setup>
import { gapDaysFromNow } from '@/Composable/date';
import {
    faArrowUpFromBracket,
    faBolt,
    faCircleQuestion,
    faCog,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const auth = computed(() => usePage().props.auth);
</script>
