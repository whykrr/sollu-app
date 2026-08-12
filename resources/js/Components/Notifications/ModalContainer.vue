<template>
  <div>
    <!-- Generic Modal Dialog managed by useModalStore -->
    <Modal
      :show="modalStore.activeModal.isVisible"
      :title="modalStore.activeModal.title"
      :type="modalStore.activeModal.type"
      :size="modalStore.activeModal.size"
      @close="handleCancel"
    >
      <div class="flex items-start gap-3 py-1">
        <!-- Icon Badge for Alert/Confirm -->
        <div
          v-if="modalStore.activeModal.type !== 'default'"
          class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-lg shadow-xs"
          :class="iconContainerClasses[modalStore.activeModal.type]"
        >
          <FontAwesomeIcon :icon="computedIcon" />
        </div>

        <div class="flex-1 min-w-0 pt-0.5">
          <p class="text-slate-600 text-sm leading-relaxed">
            {{ modalStore.activeModal.message }}
          </p>
        </div>
      </div>

      <template #footer>
        <button
          v-if="modalStore.activeModal.showCancel"
          type="button"
          class="btn btn-slate-400"
          @click="handleCancel"
        >
          {{ modalStore.activeModal.cancelText }}
        </button>

        <button
          type="button"
          class="btn"
          :class="modalStore.activeModal.confirmClass"
          @click="handleConfirm"
        >
          {{ modalStore.activeModal.confirmText }}
        </button>
      </template>
    </Modal>

    <!-- Legacy Delete / SoftDelete Modal managed by useModalStore.delete -->
    <Modal
      :show="modalStore.delete.isVisible"
      :title="modalStore.delete.header"
      type="danger"
      @close="modalStore.closeModalDelete"
    >
      <div class="flex items-start gap-3 py-1">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-600 text-lg">
          <FontAwesomeIcon :icon="faTrash" />
        </div>
        <div class="flex-1 pt-0.5">
          <p class="text-slate-600 text-sm leading-relaxed">
            {{ modalStore.delete.msg }}
          </p>
        </div>
      </div>

      <template #footer>
        <button
          type="button"
          class="btn btn-slate-400"
          @click="modalStore.closeModalDelete"
        >
          Batal
        </button>
        <Link
          v-if="modalStore.delete.url"
          class="btn btn-danger bg-rose-600 hover:bg-rose-700 text-white"
          :href="modalStore.delete.url"
          as="button"
          method="delete"
          @click="modalStore.closeModalDelete"
        >
          Ya, Hapus
        </Link>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faCheck,
    faInfo,
    faExclamation,
    faXmark,
    faTrash,
    faTriangleExclamation,
} from '@fortawesome/free-solid-svg-icons'
import Modal from '@/Components/Notifications/Modal.vue'
import { useModalStore } from '@/store/notification'

const modalStore = useModalStore()

const computedIcon = computed(() => {
    switch (modalStore.activeModal.type) {
        case 'success':
            return faCheck
        case 'warning':
            return faTriangleExclamation
        case 'danger':
            return faXmark
        case 'info':
        case 'confirm':
        default:
            return faExclamation
    }
})

const iconContainerClasses = {
    success: 'bg-emerald-100 text-emerald-600',
    info: 'bg-blue-100 text-blue-600',
    confirm: 'bg-blue-100 text-blue-600',
    warning: 'bg-amber-100 text-amber-600',
    danger: 'bg-rose-100 text-rose-600',
}

const handleConfirm = () => {
    if (typeof modalStore.activeModal.onConfirm === 'function') {
        modalStore.activeModal.onConfirm()
    }
    modalStore.close()
}

const handleCancel = () => {
    if (typeof modalStore.activeModal.onCancel === 'function') {
        modalStore.activeModal.onCancel()
    }
    modalStore.close()
}
</script>
