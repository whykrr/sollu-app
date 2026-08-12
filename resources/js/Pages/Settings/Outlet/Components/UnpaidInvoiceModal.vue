<template>
  <Modal
    :show="show"
    title="Pembayaran Tertunda"
    size="max-w-sm"
    type="danger"
    @close="$emit('close')"
  >
    <div class="flex flex-col items-center text-center space-y-3 py-2">
      <div class="space-y-2">
        <h3 class="text-base font-bold text-slate-800">
          Pembayaran Diperlukan
        </h3>
        <p class="text-sm text-slate-500">
          Outlet tidak dapat diaktifkan karena invoice penambahan
          outlet (<strong>{{ unpaidInvoice?.number }}</strong>) belum dilunasi.
        </p>
        <p class="text-xs text-slate-400">
          Silakan selesaikan pembayaran tagihan prorasi terlebih dahulu untuk mengaktifkan outlet ini.
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
        :href="unpaidInvoice?.url || '#'"
        class="btn btn-main flex-1 text-center"
        as="button"
      >
        Bayar Sekarang
      </Link>
    </template>
  </Modal>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import Modal from '@/Components/Notifications/Modal.vue'

defineProps({
    show: Boolean,
    unpaidInvoice: Object,
})

defineEmits(['close'])
</script>
