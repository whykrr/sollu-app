<template>
    <transition name="fade-down" mode="in-out">
        <div
            v-if="props.isOpen"
            class="absolute z-50 bg-white border border-neutral-100 rounded-xl w-[calc(100vw-2rem)] sm:w-[26rem] top-full mt-2 right-0 shadow-xl ring-1 ring-black/5 p-4 origin-top-right flex flex-col"
            style="max-height: calc(100vh - 100px);"
        >
            <div class="flex flex-col h-full w-full relative gap-2">
                <!-- Close Button -->
                <div class="absolute right-0 top-0">
                    <a
                        href="#"
                        @click.prevent="closeNotification"
                        class="text-neutral-400 hover:text-neutral-600 transition-colors text-base"
                    >
                        <FontAwesomeIcon :icon="faClose" />
                    </a>
                </div>

                <!-- Header Title & Mark All As Read -->
                <div class="flex items-center justify-between pr-6">
                    <div class="text-lg font-medium text-neutral-800">
                        Notifikasi
                    </div>
                    <button 
                        @click="markAllAsRead"
                        :disabled="unreadCount === 0"
                        class="text-xs font-medium text-main hover:text-main/80 transition disabled:opacity-40 disabled:no-underline"
                    >
                        Tandai semua dibaca
                    </button>
                </div>
                
                <!-- Filter Tabs matching TopBarAccount style -->
                <div class="bg-neutral-50 border border-neutral-100 rounded-xl p-1 flex gap-1 text-xs font-medium text-neutral-500">
                    <button
                        class="flex-1 py-1.5 px-2 rounded-lg transition-all duration-150 ease-in-out text-center flex items-center justify-center gap-1.5"
                        :class="filterActive === 'all' ? 'bg-white text-neutral-800 shadow-sm border border-neutral-100 font-semibold' : 'hover:text-neutral-800 hover:bg-neutral-100/50'"
                        @click="toggleFilter('all')"
                    >
                        <span>Semua</span>
                        <span v-if="unreadCount > 0" class="bg-main/10 text-main text-[10px] px-1.5 py-0.5 rounded-full font-bold">
                            {{ unreadCount }}
                        </span>
                    </button>
                    <button
                        class="flex-1 py-1.5 px-2 rounded-lg transition-all duration-150 ease-in-out text-center"
                        :class="filterActive === 'system' ? 'bg-white text-neutral-800 shadow-sm border border-neutral-100 font-semibold' : 'hover:text-neutral-800 hover:bg-neutral-100/50'"
                        @click="toggleFilter('system')"
                    >
                        Sistem
                    </button>
                    <button
                        class="flex-1 py-1.5 px-2 rounded-lg transition-all duration-150 ease-in-out text-center"
                        :class="filterActive === 'order' ? 'bg-white text-neutral-800 shadow-sm border border-neutral-100 font-semibold' : 'hover:text-neutral-800 hover:bg-neutral-100/50'"
                        @click="toggleFilter('order')"
                    >
                        Pesanan
                    </button>
                </div>
                
                <!-- Content Area -->
                <div class="bg-neutral-50 border border-neutral-100 rounded-xl overflow-hidden flex-1 overflow-y-auto floating-scroll">
                    <!-- Loaded State -->
                    <ol
                        v-if="!isLoading && notifications.length > 0"
                        class="divide-y divide-neutral-100"
                    >
                        <li
                            v-for="notification in notifications"
                            :key="notification.id"
                        >
                            <NotificationItem
                                :notification="notification"
                                @read="markAsRead"
                                @delete="deleteNotification"
                            />
                        </li>
                    </ol>

                    <!-- Loading Skeletons -->
                    <ol
                        v-if="isLoading"
                        class="divide-y divide-neutral-100"
                    >
                        <li v-for="i in 4" :key="'skeleton-' + i">
                            <NotificationItem :notification="null" />
                        </li>
                    </ol>

                    <!-- Empty State -->
                    <div
                        v-if="!isLoading && notifications.length === 0"
                        class="flex flex-col items-center justify-center text-neutral-400 select-none p-8 text-center my-auto min-h-[16rem]"
                    >
                        <div class="bg-white rounded-full h-16 w-16 flex items-center justify-center mb-3 border border-neutral-200 shadow-sm">
                            <FontAwesomeIcon :icon="faBellSlash" class="text-2xl text-neutral-400" />
                        </div>
                        <h3 class="text-base font-medium text-neutral-700 mb-1">Belum Ada Notifikasi</h3>
                        <p class="text-xs text-neutral-500">Saat ini tidak ada pemberitahuan baru untuk Anda.</p>
                    </div>

                    <!-- Load More Button -->
                    <div v-if="!isLoading && currentPage < lastPage" class="p-3 flex justify-center border-t border-neutral-100 bg-white">
                        <button 
                            class="btn btn-outline-main btn-sm rounded-lg text-xs" 
                            @click="fetchNotifications(currentPage + 1, true)" 
                            :disabled="isLoadingMore"
                        >
                            <span v-if="isLoadingMore">Memuat...</span>
                            <span v-else>Muat Lebih Banyak</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faClose, faListCheck, faBellSlash } from '@fortawesome/free-solid-svg-icons';
import NotificationItem from './NotificationItem.vue';

const props = defineProps({
    isOpen: Boolean,
});

const emit = defineEmits(['close', 'update-unread-count']);

// State
const filterActive = ref('all');
const notifications = ref([]);
const unreadCount = ref(0);

watch(unreadCount, (val) => {
    emit('update-unread-count', val);
});
const isLoading = ref(false);
const isLoadingMore = ref(false);
const currentPage = ref(1);
const lastPage = ref(1);

// Methods
const fetchNotifications = async (page = 1, append = false) => {
    if (page === 1) isLoading.value = true;
    else isLoadingMore.value = true;
    
    try {
        const response = await axios.get(route('api.internal.notifications.index'), {
            params: {
                filter: filterActive.value,
                page: page
            }
        });
        
        if (append) {
            notifications.value.push(...response.data.notifications.data);
        } else {
            notifications.value = response.data.notifications.data;
        }
        
        currentPage.value = response.data.notifications.current_page;
        lastPage.value = response.data.notifications.last_page;
        unreadCount.value = response.data.unread_count;
        
    } catch (e) {
        console.error('Failed to fetch notifications', e);
    } finally {
        isLoading.value = false;
        isLoadingMore.value = false;
    }
};

const toggleFilter = (type) => {
    filterActive.value = type;
    fetchNotifications(1);
};

const markAsRead = async (id) => {
    try {
        await axios.patch(route('api.internal.notifications.markAsRead', id));
        const index = notifications.value.findIndex(n => n.id === id);
        if (index !== -1) {
            notifications.value[index].read_at = new Date().toISOString();
        }
        unreadCount.value = Math.max(0, unreadCount.value - 1);
    } catch (e) { 
        console.error('Failed to mark as read', e);
    }
};

const markAllAsRead = async () => {
    if (unreadCount.value === 0) return;
    
    try {
        await axios.post(route('api.internal.notifications.markAllAsRead'));
        notifications.value.forEach(n => {
            if (!n.read_at) n.read_at = new Date().toISOString();
        });
        unreadCount.value = 0;
    } catch (e) { 
        console.error('Failed to mark all as read', e);
    }
};

const deleteNotification = async (id) => {
    try {
        // Optimistic UI update
        notifications.value = notifications.value.filter(n => n.id !== id);
        await axios.delete(route('api.internal.notifications.destroy', id));
        
        // Re-fetch to keep pagination in sync if needed, but not strictly necessary
        // fetchNotifications(1);
    } catch (e) { 
        console.error('Failed to delete notification', e);
        // Rollback could be implemented here
    }
};

const closeNotification = () => {
    emit('close');
};

// Watchers
watch(
    () => props.isOpen,
    (val) => {
        if (val) {
            fetchNotifications(1);
        } else {
            // Optional: reset state when closed
            // filterActive.value = 'all';
            // notifications.value = [];
        }
    }
);
</script>
