<template>
    <Teleport to="body" :disabled="!isMobile">
        <!-- Backdrop (Desktop only or optional overlay) -->
        <div v-if="props.isOpen">
            <!-- Fullscreen Mobile Dialog (< sm) -->
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="translate-y-full opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="translate-y-full opacity-0"
            >
                <div
                    v-if="isMobile"
                    class="fixed inset-0 z-[100] w-full h-[100dvh] bg-white flex flex-col overflow-hidden"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="mobileNotificationTitle"
                    @click.stop
                >
                    <!-- Mobile Header (Sticky Top with Safe Area) -->
                    <div
                        class="sticky top-0 z-20 bg-white border-b border-neutral-200 px-4 py-2.5 pt-[max(0.75rem,env(safe-area-inset-top,0px))] flex items-center justify-between gap-3 shrink-0"
                    >
                        <div class="flex items-center gap-2.5 min-w-0">
                            <button
                                type="button"
                                class="w-11 h-11 -ml-2 flex items-center justify-center rounded-xl text-neutral-600 hover:text-neutral-900 active:bg-neutral-100 transition touch-manipulation cursor-pointer"
                                aria-label="Tutup notifikasi"
                                @click="closeNotification"
                            >
                                <FontAwesomeIcon :icon="faClose" class="text-lg" />
                            </button>
                            <div class="flex items-center gap-2">
                                <h2
                                    id="mobileNotificationTitle"
                                    class="text-lg font-bold text-neutral-900"
                                >
                                    Notifikasi
                                </h2>
                                <span
                                    v-if="unreadCount > 0"
                                    class="bg-main text-white text-[10px] px-1.5 py-0.5 rounded-full font-bold"
                                >
                                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                                </span>
                            </div>
                        </div>

                        <button
                            :disabled="unreadCount === 0"
                            class="text-xs font-semibold text-main hover:text-main/80 active:opacity-60 transition disabled:opacity-40 disabled:no-underline py-2 px-2.5 rounded-lg touch-manipulation cursor-pointer"
                            @click="markAllAsRead"
                        >
                            Tandai semua dibaca
                        </button>
                    </div>

                    <!-- Mobile Filter Tabs: Semua, Sistem, Pesanan, Stok -->
                    <div
                        class="bg-neutral-50 border-b border-neutral-200/80 px-3 py-2 shrink-0"
                    >
                        <div
                            class="bg-neutral-200/60 p-1 rounded-xl grid grid-cols-4 gap-1 text-xs font-medium text-neutral-500"
                        >
                            <button
                                class="py-2 px-1 min-h-[40px] rounded-lg transition-all duration-150 ease-in-out text-center flex items-center justify-center gap-1 touch-manipulation cursor-pointer select-none"
                                :class="
                                    filterActive === 'all'
                                        ? 'bg-white text-neutral-900 shadow-xs font-bold'
                                        : 'hover:text-neutral-800 active:bg-neutral-300/50'
                                "
                                @click="toggleFilter('all')"
                            >
                                <span>Semua</span>
                                <span
                                    v-if="unreadCount > 0"
                                    class="bg-main/10 text-main text-[9px] px-1 py-0.5 rounded-full font-bold"
                                >
                                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                                </span>
                            </button>
                            <button
                                class="py-2 px-1 min-h-[40px] rounded-lg transition-all duration-150 ease-in-out text-center truncate touch-manipulation cursor-pointer select-none"
                                :class="
                                    filterActive === 'system'
                                        ? 'bg-white text-neutral-900 shadow-xs font-bold'
                                        : 'hover:text-neutral-800 active:bg-neutral-300/50'
                                "
                                @click="toggleFilter('system')"
                            >
                                Sistem
                            </button>
                            <button
                                class="py-2 px-1 min-h-[40px] rounded-lg transition-all duration-150 ease-in-out text-center truncate touch-manipulation cursor-pointer select-none"
                                :class="
                                    filterActive === 'order'
                                        ? 'bg-white text-neutral-900 shadow-xs font-bold'
                                        : 'hover:text-neutral-800 active:bg-neutral-300/50'
                                "
                                @click="toggleFilter('order')"
                            >
                                Pesanan
                            </button>
                            <button
                                class="py-2 px-1 min-h-[40px] rounded-lg transition-all duration-150 ease-in-out text-center truncate touch-manipulation cursor-pointer select-none"
                                :class="
                                    filterActive === 'inventory'
                                        ? 'bg-white text-neutral-900 shadow-xs font-bold'
                                        : 'hover:text-neutral-800 active:bg-neutral-300/50'
                                "
                                @click="toggleFilter('inventory')"
                            >
                                Stok
                            </button>
                        </div>
                    </div>

                    <!-- Mobile Notification List Body (Scrollable flex-1) -->
                    <div
                        class="flex-1 min-h-0 overflow-y-auto overscroll-y-contain floating-scroll bg-neutral-50/50 pb-[max(1rem,env(safe-area-inset-bottom,0px))]"
                    >
                        <!-- Loaded State -->
                        <ol
                            v-if="!isLoading && notifications.length > 0"
                            class="divide-y divide-neutral-100 bg-white border-b border-neutral-100"
                        >
                            <li v-for="notification in notifications" :key="notification.id">
                                <NotificationItem
                                    :notification="notification"
                                    @read="markAsRead"
                                    @delete="deleteNotification"
                                />
                            </li>
                        </ol>

                        <!-- Loading Skeletons -->
                        <ol v-if="isLoading" class="divide-y divide-neutral-100 bg-white">
                            <li v-for="i in 5" :key="'skeleton-m-' + i">
                                <NotificationItem :notification="null" />
                            </li>
                        </ol>

                        <!-- Empty State -->
                        <div
                            v-if="!isLoading && notifications.length === 0"
                            class="flex flex-col items-center justify-center text-neutral-400 select-none p-8 text-center my-auto min-h-[22rem]"
                        >
                            <div
                                class="bg-white rounded-full h-20 w-20 flex items-center justify-center mb-4 border border-neutral-200 shadow-xs"
                            >
                                <FontAwesomeIcon
                                    :icon="faBellSlash"
                                    class="text-3xl text-neutral-400"
                                />
                            </div>
                            <h3 class="text-base font-semibold text-neutral-800 mb-1">
                                Belum Ada Notifikasi
                            </h3>
                            <p class="text-xs text-neutral-500 max-w-xs">
                                Saat ini tidak ada pemberitahuan baru di kategori ini.
                            </p>
                        </div>

                        <!-- Load More Button -->
                        <div
                            v-if="!isLoading && currentPage < lastPage"
                            class="p-4 flex justify-center bg-white border-t border-neutral-100 mt-2"
                        >
                            <button
                                class="btn btn-outline-main rounded-xl text-xs w-full min-h-[44px] flex items-center justify-center font-medium touch-manipulation"
                                :disabled="isLoadingMore"
                                @click="fetchNotifications(currentPage + 1, true)"
                            >
                                <span v-if="isLoadingMore">Memuat...</span>
                                <span v-else>Muat Lebih Banyak Notifikasi</span>
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- Desktop Popover Dropdown (>= sm) -->
            <Transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-2 scale-95"
                enter-to-class="opacity-100 translate-y-0 scale-100"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0 scale-100"
                leave-to-class="opacity-0 -translate-y-2 scale-95"
            >
                <div
                    v-if="!isMobile"
                    class="absolute top-full mt-2 right-0 w-[26rem] max-h-[36rem] rounded-xl border border-neutral-100 p-4 shadow-2xl origin-top-right z-50 bg-white flex flex-col overflow-hidden"
                    @click.stop
                >
                    <div class="flex flex-col flex-1 min-h-0 w-full relative gap-2">
                        <!-- Close Button -->
                        <div class="absolute right-0 top-0">
                            <button
                                type="button"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-neutral-400 hover:text-neutral-600 hover:bg-neutral-100 active:bg-neutral-200 transition-colors touch-manipulation cursor-pointer"
                                aria-label="Tutup notifikasi"
                                @click="closeNotification"
                            >
                                <FontAwesomeIcon :icon="faClose" class="text-sm" />
                            </button>
                        </div>

                        <!-- Header Title & Mark All As Read -->
                        <div class="flex items-center justify-between pr-8 shrink-0">
                            <div class="text-base font-semibold text-neutral-800">Notifikasi</div>
                            <button
                                :disabled="unreadCount === 0"
                                class="text-xs font-medium text-main hover:text-main/80 active:opacity-60 transition disabled:opacity-40 disabled:no-underline py-1 px-1.5 rounded touch-manipulation cursor-pointer"
                                @click="markAllAsRead"
                            >
                                Tandai semua dibaca
                            </button>
                        </div>

                        <!-- Filter Tabs: Semua, Sistem, Pesanan, Stok -->
                        <div
                            class="bg-neutral-50 border border-neutral-100 rounded-xl p-1 grid grid-cols-4 gap-1 text-xs font-medium text-neutral-500 shrink-0"
                        >
                            <button
                                class="py-1.5 px-1 min-h-[30px] rounded-lg transition-all duration-150 ease-in-out text-center flex items-center justify-center gap-1 touch-manipulation cursor-pointer select-none"
                                :class="
                                    filterActive === 'all'
                                        ? 'bg-white text-neutral-800 shadow-xs border border-neutral-100 font-semibold'
                                        : 'hover:text-neutral-800 hover:bg-neutral-100/50 active:bg-neutral-200/50'
                                "
                                @click="toggleFilter('all')"
                            >
                                <span>Semua</span>
                                <span
                                    v-if="unreadCount > 0"
                                    class="bg-main/10 text-main text-[9px] px-1 py-0.5 rounded-full font-bold"
                                >
                                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                                </span>
                            </button>
                            <button
                                class="py-1.5 px-1 min-h-[30px] rounded-lg transition-all duration-150 ease-in-out text-center truncate touch-manipulation cursor-pointer select-none"
                                :class="
                                    filterActive === 'system'
                                        ? 'bg-white text-neutral-800 shadow-xs border border-neutral-100 font-semibold'
                                        : 'hover:text-neutral-800 hover:bg-neutral-100/50 active:bg-neutral-200/50'
                                "
                                @click="toggleFilter('system')"
                            >
                                Sistem
                            </button>
                            <button
                                class="py-1.5 px-1 min-h-[30px] rounded-lg transition-all duration-150 ease-in-out text-center truncate touch-manipulation cursor-pointer select-none"
                                :class="
                                    filterActive === 'order'
                                        ? 'bg-white text-neutral-800 shadow-xs border border-neutral-100 font-semibold'
                                        : 'hover:text-neutral-800 hover:bg-neutral-100/50 active:bg-neutral-200/50'
                                "
                                @click="toggleFilter('order')"
                            >
                                Pesanan
                            </button>
                            <button
                                class="py-1.5 px-1 min-h-[30px] rounded-lg transition-all duration-150 ease-in-out text-center truncate touch-manipulation cursor-pointer select-none"
                                :class="
                                    filterActive === 'inventory'
                                        ? 'bg-white text-neutral-800 shadow-xs border border-neutral-100 font-semibold'
                                        : 'hover:text-neutral-800 hover:bg-neutral-100/50 active:bg-neutral-200/50'
                                "
                                @click="toggleFilter('inventory')"
                            >
                                Stok
                            </button>
                        </div>

                        <!-- Content Area (Scrollable) -->
                        <div
                            class="bg-neutral-50 border border-neutral-100 rounded-xl overflow-hidden flex-1 min-h-0 overflow-y-auto floating-scroll"
                        >
                            <!-- Loaded State -->
                            <ol
                                v-if="!isLoading && notifications.length > 0"
                                class="divide-y divide-neutral-100"
                            >
                                <li v-for="notification in notifications" :key="notification.id">
                                    <NotificationItem
                                        :notification="notification"
                                        @read="markAsRead"
                                        @delete="deleteNotification"
                                    />
                                </li>
                            </ol>

                            <!-- Loading Skeletons -->
                            <ol v-if="isLoading" class="divide-y divide-neutral-100">
                                <li v-for="i in 4" :key="'skeleton-d-' + i">
                                    <NotificationItem :notification="null" />
                                </li>
                            </ol>

                            <!-- Empty State -->
                            <div
                                v-if="!isLoading && notifications.length === 0"
                                class="flex flex-col items-center justify-center text-neutral-400 select-none p-6 text-center my-auto min-h-[14rem]"
                            >
                                <div
                                    class="bg-white rounded-full h-14 w-14 flex items-center justify-center mb-2.5 border border-neutral-200 shadow-xs"
                                >
                                    <FontAwesomeIcon
                                        :icon="faBellSlash"
                                        class="text-xl text-neutral-400"
                                    />
                                </div>
                                <h3 class="text-sm font-semibold text-neutral-700 mb-1">
                                    Belum Ada Notifikasi
                                </h3>
                                <p class="text-xs text-neutral-500">
                                    Saat ini tidak ada pemberitahuan baru di kategori ini.
                                </p>
                            </div>

                            <!-- Load More Button -->
                            <div
                                v-if="!isLoading && currentPage < lastPage"
                                class="p-2.5 flex justify-center border-t border-neutral-100 bg-white"
                            >
                                <button
                                    class="btn btn-outline-main btn-sm rounded-lg text-xs w-full min-h-[30px] flex items-center justify-center touch-manipulation cursor-pointer"
                                    :disabled="isLoadingMore"
                                    @click="fetchNotifications(currentPage + 1, true)"
                                >
                                    <span v-if="isLoadingMore">Memuat...</span>
                                    <span v-else>Muat Lebih Banyak</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, watch, computed, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faClose, faBellSlash } from '@fortawesome/free-solid-svg-icons'
import NotificationItem from './NotificationItem.vue'

const props = defineProps({
    isOpen: Boolean,
})

const emit = defineEmits(['close', 'update-unread-count'])

const page = usePage()
const authUser = computed(() => page.props.auth?.user)

// State
const filterActive = ref('all')
const notifications = ref([])
const unreadCount = ref(0)

watch(unreadCount, val => {
    emit('update-unread-count', val)
})

const isLoading = ref(false)
const isLoadingMore = ref(false)
const currentPage = ref(1)
const lastPage = ref(1)

// Methods
const fetchNotifications = async (page = 1, append = false) => {
    if (page === 1) isLoading.value = true
    else isLoadingMore.value = true

    try {
        const response = await axios.get(route('api.internal.notifications.index'), {
            params: {
                filter: filterActive.value,
                page: page,
            },
        })

        if (append) {
            notifications.value.push(...response.data.notifications.data)
        } else {
            notifications.value = response.data.notifications.data
        }

        currentPage.value = response.data.notifications.current_page
        lastPage.value = response.data.notifications.last_page
        unreadCount.value = response.data.unread_count
    } catch (e) {
        console.error('Failed to fetch notifications', e)
    } finally {
        isLoading.value = false
        isLoadingMore.value = false
    }
}

const toggleFilter = type => {
    filterActive.value = type
    fetchNotifications(1)
}

const markAsRead = async id => {
    try {
        await axios.patch(route('api.internal.notifications.markAsRead', id))
        const index = notifications.value.findIndex(n => n.id === id)
        if (index !== -1) {
            notifications.value[index].read_at = new Date().toISOString()
        }
        unreadCount.value = Math.max(0, unreadCount.value - 1)
    } catch (e) {
        console.error('Failed to mark as read', e)
    }
}

const markAllAsRead = async () => {
    if (unreadCount.value === 0) return

    try {
        await axios.post(route('api.internal.notifications.markAllAsRead'))
        notifications.value.forEach(n => {
            if (!n.read_at) n.read_at = new Date().toISOString()
        })
        unreadCount.value = 0
    } catch (e) {
        console.error('Failed to mark all as read', e)
    }
}

const deleteNotification = async id => {
    try {
        notifications.value = notifications.value.filter(n => n.id !== id)
        await axios.delete(route('api.internal.notifications.destroy', id))
    } catch (e) {
        console.error('Failed to delete notification', e)
    }
}

const closeNotification = () => {
    emit('close')
}

const isMobile = ref(false)

const checkMobile = () => {
    if (typeof window !== 'undefined') {
        isMobile.value = window.innerWidth < 640 // sm breakpoint
    }
}

const handleKeyDown = e => {
    if (e.key === 'Escape' && props.isOpen) {
        closeNotification()
    }
}

// Watchers
watch(
    () => props.isOpen,
    val => {
        if (val) {
            fetchNotifications(1)
        }
    }
)

// Lifecycle & Real-time Echo listeners
onMounted(() => {
    checkMobile()
    window.addEventListener('resize', checkMobile)
    window.addEventListener('keydown', handleKeyDown)

    if (authUser.value?.id && window.Echo) {
        window.Echo.private(`App.Models.User.${authUser.value.id}`)
            .notification((notification) => {
                unreadCount.value++
                if (filterActive.value === 'all' || filterActive.value === notification.category) {
                    notifications.value.unshift({
                        id: notification.id,
                        data: notification,
                        created_at: new Date().toISOString(),
                        read_at: null,
                    })
                }
            })
    }
})

onUnmounted(() => {
    window.removeEventListener('resize', checkMobile)
    window.removeEventListener('keydown', handleKeyDown)

    if (authUser.value?.id && window.Echo) {
        window.Echo.leave(`App.Models.User.${authUser.value.id}`)
    }
})
</script>
