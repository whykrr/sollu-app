<template>
  <div ref="panelRef" class="relative">
    <a
      href="#"
      class="relative flex items-center justify-center w-10 h-10 rounded-full text-slate-700 hover:bg-neutral-100 transition-all duration-200 active:scale-95"
      title="Notifikasi"
      @click.prevent="toggleNotification"
    >
      <FontAwesomeIcon :icon="faBell" class="text-[1.15rem]" />
      <span 
        v-if="unreadCount > 0" 
        class="absolute top-[8px] right-[9px] w-2.5 h-2.5 bg-red-500 ring-2 ring-white rounded-full"
      ></span>
    </a>
    <PopoverNotification
      :is-open="showNotification"
      :unread-count="unreadCount"
      @close="showNotification = false"
      @update-unread-count="count => unreadCount = count"
    />
  </div>
</template>
<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import PopoverNotification from '../PopoverNotification/PopoverNotification.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faBell } from '@fortawesome/free-regular-svg-icons'
import { useDropdown } from '@/Composable/useDropdown'
import { useToastStore } from '@/store/toast'
import { useAuth } from '@/Composable/useAuth'

const { isOpen: showNotification, toggle: toggleNotification, dropdownRef: panelRef } = useDropdown()
const page = usePage()
const { user } = useAuth()
const toast = useToastStore()

const unreadCount = ref(0)
let notificationChannel = null

const handleIncomingNotification = (notification) => {
    unreadCount.value++
    
    // Resolve notification type safely
    const rawType = (notification.type || notification.level || notification.data?.type || 'info').toString().toLowerCase()
    const validTypes = ['success', 'info', 'warning', 'danger', 'error']
    let toastType = 'info'
    if (validTypes.includes(rawType)) {
        toastType = rawType === 'error' ? 'danger' : rawType
    }

    const title = notification.title || notification.data?.title || 'Notifikasi Baru'
    const message = notification.message || notification.data?.message || 'Anda memiliki pemberitahuan baru.'
    const actionUrl = notification.action_url || notification.data?.action_url
    const actionText = notification.action_text || notification.data?.action_text || 'Lihat'

    // Trigger toast
    toast.addToast({
        type: toastType,
        title: title,
        message: message,
        action: actionUrl ? {
            text: actionText,
            onClick: () => {
                window.location.href = actionUrl
            }
        } : null,
    })
}

const setupEchoListener = () => {
    const userId = user.value?.id || page.props.auth?.id || page.props.auth?.user?.id
    if (!userId || !window.Echo || notificationChannel) return

    notificationChannel = window.Echo.private(`App.Models.User.${userId}`)
        .notification((notification) => {
            handleIncomingNotification(notification)
        })
}

// Reactively watch for user ID and window.Echo availability
watch(
    [() => user.value?.id, () => page.props.auth?.id, () => page.props.auth?.user?.id],
    () => {
        setupEchoListener()
    },
    { immediate: true }
)

onMounted(() => {
    // Expose test helper in development or browser console
    window.triggerTestToast = (title = 'Notifikasi Baru', message = 'Ini adalah contoh pesan notifikasi toast realtime.', type = 'info') => {
        handleIncomingNotification({
            type,
            title,
            message,
        })
    }

    setupEchoListener()
})

onUnmounted(() => {
    delete window.triggerTestToast
    const userId = user.value?.id || page.props.auth?.id || page.props.auth?.user?.id
    if (notificationChannel && userId && window.Echo) {
        window.Echo.leave(`App.Models.User.${userId}`)
        notificationChannel = null
    }
})

</script>
