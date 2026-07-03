<template>
    <div v-if="show" class="overlay-backdrop">
        <div class="overlay-modal max-w-sm">
            <!-- Header -->
            <div class="overlay-header">
                <h3 class="overlay-title">Batas Outlet Tercapai</h3>
                <button
                    type="button"
                    @click="$emit('close')"
                    class="overlay-close"
                >
                    ✖
                </button>
            </div>
            <!-- Body -->
            <div class="flex flex-col items-center text-center p-5 space-y-4">
                <div
                    class="flex size-16 items-center justify-center rounded-full bg-amber-50 text-amber-500"
                >
                    <FontAwesomeIcon
                        :icon="faExclamationTriangle"
                        class="text-3xl"
                    />
                </div>
                <div class="space-y-2">
                    <h3 class="text-base font-bold text-slate-800">
                        Kuota Outlet Terbatas
                    </h3>
                    <p class="text-sm text-slate-500">
                        Paket langganan Anda saat ini ({{
                            limit?.is_trial
                                ? 'Masa Trial'
                                : subscription?.plan?.name ||
                                  'Belum Berlangganan'
                        }}) membatasi maksimal {{ limit?.max }} outlet.
                    </p>
                    <p class="text-xs text-slate-400">
                        Silakan upgrade paket langganan Anda untuk menambahkan
                        outlet baru.
                    </p>
                </div>
            </div>
            <!-- Footer -->
            <div class="overlay-footer">
                <button
                    type="button"
                    class="btn btn-secondary flex-1 justify-center rounded-lg"
                    @click="$emit('close')"
                >
                    Batal
                </button>
                <Link
                    :href="route('settings.billing.plans')"
                    class="btn btn-main flex-1 justify-center text-center rounded-lg"
                    as="button"
                >
                    Upgrade Paket
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faExclamationTriangle } from '@fortawesome/free-solid-svg-icons';

defineProps({
    show: Boolean,
    limit: Object,
    subscription: Object,
});

defineEmits(['close']);
</script>
