<template>
    <transition name="slide-fade" mode="in-out">
        <aside
            v-if="props.isOpen"
            class="fixed w-full sm:w-auto right-0 top-0 h-screen z-50 shadow-2xl bg-gray-200 border-l border-gray-200"
        >
            <div class="flex flex-col h-full p-4 w-full sm:w-[30rem] relative gap-2.5">
                <span class="text-xs text-main underline absolute right-2 top-4">
                    <a
                        href="#"
                        title="Tutup"
                        class="text-lg mx-2 text-gray-600 hover:text-gray-900 transition"
                        @click.prevent="closeNotification"
                    >
                        <FontAwesomeIcon :icon="faClose" />
                    </a>
                </span>
                
                <div class="font-semibold text-2xl flex items-center gap-2">
                    Notifikasi
                    <span v-if="unreadCount > 0" class="badge badge-danger rounded-full text-xs animate-pulse">
                        {{ unreadCount }} Baru
                    </span>
                </div>
                
                <div class="grid grid-cols-3 gap-0.5 bg-gray-300 rounded-lg p-0.5 shadow-inner">
                    <NotificationFilter
                        label="Semua"
                        :badge="0"
                        :active="filterActive === 'all'"
                        @click="toggleFilter('all')"
                    />
                    <NotificationFilter
                        label="Sistem"
                        :badge="0"
                        :active="filterActive === 'system'"
                        @click="toggleFilter('system')"
                    />
                    <NotificationFilter
                        label="Pesanan"
                        :badge="0"
                        :active="filterActive === 'order'"
                        @click="toggleFilter('order')"
                    />
                </div>
                
                <div class="flex-1 overflow-y-auto floating-scroll bg-neutral-50 rounded-lg p-2 border border-neutral-100">
                    <!-- Loaded State -->
                    <ol
                        v-if="!isLoading && notifications.length > 0"
                        class="flex flex-col gap-1"
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
                        class="flex flex-col gap-1"
                    >
                        <li v-for="i in 5" :key="'skeleton-' + i">
                            <NotificationItem :notification="null" />
                        </li>
                    </ol>

                    <!-- Empty State -->
                    <div
                        v-if="!isLoading && notifications.length === 0"
                        class="flex flex-col h-full w-full items-center justify-center text-neutral-400 select-none p-8 text-center"
                    >
                        <div class="bg-neutral-100/50 rounded-full h-24 w-24 flex items-center justify-center mb-4 border border-neutral-100">
                            <FontAwesomeIcon :icon="faBellSlash" class="text-4xl text-neutral-300" />
                        </div>
                        <h3 class="text-lg font-semibold text-neutral-500 mb-1">Belum Ada Notifikasi</h3>
                        <p class="text-sm text-neutral-400">Saat ini tidak ada pemberitahuan baru untuk Anda.</p>
                    </div>

                    <!-- Load More Button -->
                    <div v-if="!isLoading && currentPage < lastPage" class="mt-4 flex justify-center pb-4">
                        <button 
                            class="btn btn-outline-main btn-sm rounded-full" 
                            @click="fetchNotifications(currentPage + 1, true)" 
                            :disabled="isLoadingMore"
                        >
                            <span v-if="isLoadingMore">Memuat...</span>
                            <span v-else>Muat Lebih Banyak</span>
                        </button>
                    </div>
                </div>
                
                <div class="pt-2">
                    <div class="flex flex-row justify-between items-center">
                        <div>
                            <button
                                type="button"
                                class="btn btn-flat text-danger btn-sm"
                                @click="closeNotification"
                            >
                                Tutup
                            </button>
                        </div>
                        <div>
                            <button 
                                class="btn btn-info btn-sm"
                                @click="markAllAsRead"
                                :disabled="unreadCount === 0"
                            >
                                <FontAwesomeIcon :icon="faListCheck" class="mr-1" />
                                Tandai semua dibaca
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </transition>
</template>

<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faClose, faListCheck, faBellSlash } from '@fortawesome/free-solid-svg-icons';
import NotificationFilter from '@/Components/Layout/SidebarNotification/NotificationFilter.vue';
import NotificationItem from './NotificationItem.vue';

const props = defineProps({
    isOpen: Boolean,
});

const emit = defineEmits(['close']);

// State
const filterActive = ref('all');
const notifications = ref([]);
const unreadCount = ref(0);
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
