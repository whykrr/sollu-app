<template>
  <div
    class="fixed top-5 right-5 z-[9999] flex flex-col gap-2.5 max-w-sm w-full pointer-events-none items-end pr-2"
  >
    <TransitionGroup name="toast">
      <Toast
        v-for="toast in toastStore.toasts"
        :key="toast.id"
        :type="toast.type"
        :title="toast.title"
        :message="toast.message"
        :icon="toast.icon"
        :action="toast.action"
        :dismissible="toast.dismissible"
        @dismiss="toastStore.removeToast(toast.id)"
      />
    </TransitionGroup>
  </div>
</template>

<script setup>
import { watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import Toast from '@/Components/Notifications/Toast.vue'
import { useToastStore } from '@/store/toast'

const toastStore = useToastStore()
const page = usePage()

// Watch Inertia flash messages and display them as toasts automatically
watch(
    () => page.props.app?.flash,
    (flash) => {
        if (!flash) return

        if (flash.success) {
            toastStore.success(flash.success)
            if (page.props.app.flash) page.props.app.flash.success = null
        }

        if (flash.failed || flash.error) {
            toastStore.danger(flash.failed || flash.error)
            if (page.props.app.flash) {
                page.props.app.flash.failed = null
                page.props.app.flash.error = null
            }
        }

        if (flash.info) {
            toastStore.info(flash.info)
            if (page.props.app.flash) page.props.app.flash.info = null
        }
    },
    { immediate: true, deep: true }
)
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.toast-enter-from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
}


.toast-leave-to {
    opacity: 0;
    transform: translateX(60px) scale(0.9);
}

.toast-move {
    transition: transform 0.3s ease;
}
</style>
