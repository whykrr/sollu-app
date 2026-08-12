<template>
  <Modal
    :show="show"
    title="Batas Outlet Tercapai"
    size="max-w-sm"
    type="warning"
    @close="$emit('close')"
  >
    <div class="flex flex-col items-center text-center space-y-3 py-2">
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
          Silakan upgrade paket langganan Anda untuk menambahkan outlet baru.
        </p>
      </div>
    </div>

    <template #footer>
      <button
        type="button"
        class="btn btn-slate-400 flex-1"
        @click="$emit('close')"
      >
        Batal
      </button>
      <Link
        :href="route('settings.billing.plans')"
        class="btn btn-main flex-1 text-center"
        as="button"
      >
        Upgrade Paket
      </Link>
    </template>
  </Modal>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import Modal from '@/Components/Notifications/Modal.vue'

defineProps({
    show: Boolean,
    limit: Object,
    subscription: Object,
})

defineEmits(['close'])
</script>
