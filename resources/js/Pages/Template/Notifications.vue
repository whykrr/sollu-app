<template>
  <MainPage>
    <div class="flex flex-col gap-4">
      <!-- Toast Section -->
      <Card title="Toast Notifications (Generic & Stackable)">
        <div class="flex flex-col gap-4">
          <p class="text-sm text-slate-600">
            Toast notifications now support global state management (<code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">useToastStore()</code>), stacked display, custom actions, and styled cards matching modern design standards.
          </p>

          <!-- Trigger Buttons -->
          <div class="flex flex-wrap items-center gap-2">
            <button
              class="btn bg-emerald-600 hover:bg-emerald-700 text-white border-none"
              @click="showSuccessToast"
            >
              Trigger Success Toast
            </button>
            <button
              class="btn bg-blue-600 hover:bg-blue-700 text-white border-none"
              @click="showInfoToast"
            >
              Trigger Info Toast
            </button>
            <button
              class="btn bg-amber-500 hover:bg-amber-600 text-white border-none"
              @click="showWarningToast"
            >
              Trigger Warning Toast
            </button>
            <button
              class="btn bg-rose-600 hover:bg-rose-700 text-white border-none"
              @click="showDangerToast"
            >
              Trigger Danger Toast
            </button>

            <button
              class="btn btn-outline-main"
              @click="triggerStackedToasts"
            >
              Tampilkan Multi Toast (Bertingkat)
            </button>

            <button
              class="btn btn-slate-200 text-slate-700"
              @click="toastStore.clearToasts()"
            >
              Bersihkan Semua Toast
            </button>
          </div>

          <!-- Static Toast Preview Stack (Matching Image Reference) -->
          <div class="mt-4 border-t border-slate-200 pt-4">
            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">
              Preview Komponen Toast (Visual Variant Reference)
            </h4>

            <div class="flex flex-col gap-3 max-w-md">
              <Toast
                type="success"
                title="Congratulations!"
                message="Your OS has been updated to the latest version."
              />

              <Toast
                type="info"
                title="Did you know?"
                message="You can switch between artboards using ⌘ + T"
              />

              <Toast
                type="warning"
                title="Warning"
                message="Your password strength is low."
              />

              <Toast
                type="danger"
                title="Something went wrong!"
                message="The program has turned off unexpectedly."
                :action="{ text: 'Send report', onClick: () => handleReport() }"
              />
            </div>
          </div>
        </div>
      </Card>

      <!-- Generic Modal Section -->
      <Card title="Generic Modal Dialog (useModalStore)">
        <div class="flex flex-col gap-4">
          <p class="text-sm text-slate-600">
            Modal dialogs can now be triggered from any component globally using <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">useModalStore()</code>.
          </p>

          <div class="flex flex-wrap items-center gap-2">
            <button
              class="btn btn-main"
              @click="triggerConfirmModal"
            >
              Trigger Confirm Modal
            </button>
            <button
              class="btn bg-rose-600 hover:bg-rose-700 text-white border-none"
              @click="triggerDangerConfirmModal"
            >
              Trigger Danger Confirm Modal
            </button>
            <button
              class="btn btn-outline-main"
              @click="triggerAlertModal"
            >
              Trigger Alert Modal
            </button>

            <button
              class="btn btn-slate-200 text-slate-700"
              @click="showInlineModal = true"
            >
              Buka Inline Component Modal
            </button>
          </div>

          <!-- Inline Modal Example -->
          <Modal
            :show="showInlineModal"
            title="Inline Modal Example"
            type="info"
            @close="showInlineModal = false"
          >
            <p class="text-slate-600 text-sm">
              Ini adalah contoh penggunaan komponen <code class="text-xs bg-slate-100 px-1 py-0.5 rounded">&lt;Modal :show="..." /&gt;</code> secara langsung di dalam template komponen.
            </p>
            <template #footer>
              <button
                class="btn btn-main"
                @click="showInlineModal = false"
              >
                Tutup
              </button>
            </template>
          </Modal>
        </div>
      </Card>

      <!-- Alert Card -->
      <Card title="Alert">
        <div class="flex flex-col gap-2">
          <div class="alert">Basic Alert</div>
          <div class="alert alert-success">Alert Success</div>
          <div class="alert alert-warning">Alert Warning</div>
          <div class="alert alert-danger">Alert Danger</div>
          <div class="alert alert-success">
            <span class="font-semibold">Well Done!</span>
            <div class="text-sm">
              Lorem ipsum dolor sit, amet consectetur adipisicing elit.
            </div>
          </div>
        </div>
      </Card>

      <!-- Badge Card -->
      <Card title="Badge">
        <div class="flex flex-col gap-1">
          <p>Available for all color and shades</p>
          <div class="flex flex-wrap items-center gap-2">
            <span class="badge badge-main text-lg">Main</span>
            <span class="badge badge-secondary text-base">Secondary</span>
            <span class="badge badge-info text-base">Info</span>
            <span class="badge badge-success text-base">Success</span>
            <span class="badge badge-warning text-sm">Warning</span>
            <span class="badge badge-danger text-xs">Danger</span>
          </div>
        </div>
      </Card>
    </div>
  </MainPage>
</template>

<script setup>
import { ref } from 'vue'
import MainPage from '@/Components/UI/MainPage.vue'
import Card from '@/Components/UI/Card/Card.vue'
import Modal from '@/Components/Notifications/Modal.vue'
import Toast from '@/Components/Notifications/Toast.vue'
import { useToastStore, useModalStore } from '@/store/notification'

const toastStore = useToastStore()
const modalStore = useModalStore()

const showInlineModal = ref(false)

// Toast actions
const showSuccessToast = () => {
    toastStore.success('Your OS has been updated to the latest version.', {
        title: 'Congratulations!',
    })
}

const showInfoToast = () => {
    toastStore.info('You can switch between artboards using ⌘ + T', {
        title: 'Did you know?',
    })
}

const showWarningToast = () => {
    toastStore.warning('Your password strength is low.', {
        title: 'Warning',
    })
}

const showDangerToast = () => {
    toastStore.danger('The program has turned off unexpectedly.', {
        title: 'Something went wrong!',
        action: {
            text: 'Send report',
            onClick: () => handleReport(),
        },
    })
}

const triggerStackedToasts = () => {
    showSuccessToast()
    setTimeout(() => showInfoToast(), 150)
    setTimeout(() => showWarningToast(), 300)
    setTimeout(() => showDangerToast(), 450)
}

const handleReport = () => {
    toastStore.info('Laporan telah dikirimkan ke tim pengembang.', {
        title: 'Laporan Terkirim',
    })
}

// Modal actions using useModalStore
const triggerConfirmModal = () => {
    modalStore.confirm({
        title: 'Konfirmasi Perubahan',
        message: 'Apakah Anda yakin ingin menyimpan perubahan data ini?',
        type: 'warning',
        confirmText: 'Ya, Simpan',
        onConfirm: () => {
            toastStore.success('Perubahan data berhasil disimpan!')
        },
    })
}

const triggerDangerConfirmModal = () => {
    modalStore.confirm({
        title: 'Hapus Data Pelanggan',
        message: 'Data pelanggan yang dihapus tidak dapat dikembalikan secara langsung.',
        type: 'danger',
        confirmText: 'Ya, Hapus Data',
        onConfirm: () => {
            toastStore.danger('Data pelanggan telah berhasil dihapus.')
        },
    })
}

const triggerAlertModal = () => {
    modalStore.alert({
        title: 'Pemeliharaan Sistem',
        message: 'Sistem akan mengalami pemeliharaan rutin pada pukul 23.00 WIB malam ini.',
        type: 'info',
        confirmText: 'Saya Mengerti',
    })
}
</script>
