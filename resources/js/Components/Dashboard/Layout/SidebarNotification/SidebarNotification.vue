<template>
    <transition name="slide-fade" mode="in-out">
        <aside
            v-if="props.isOpen"
            class="fixed w-full sm:w-auto right-0 top-0 h-screen z-50 shadow-2xl bg-neutral-200 border-l border-neutral-300"
        >
            <div
                class="flex flex-col h-full p-4 w-full sm:w-[30rem] relative gap-2.5"
            >
                <span
                    class="text-xs text-main underline absolute right-2 top-4"
                >
                    <a
                        href="#"
                        title="Tutup"
                        class="text-lg mx-2 text-gray-600"
                        @click.prevent="closeNotification"
                    >
                        <FontAwesomeIcon :icon="faClose" />
                    </a>
                </span>
                <div class="font-semibold text-2xl">Notifikasi</div>
                <div
                    class="grid grid-cols-3 gap-0.5 bg-neutral-100 rounded-lg p-0.5 shadow-inner"
                >
                    <NotificationFilter
                        label="Semua"
                        :badge="10"
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
                        :badge="7"
                        :active="filterActive === 'order'"
                        @click="toggleFilter('order')"
                    />
                </div>
                <div
                    class="flex-1 overflow-y-auto floating-scroll bg-neutral-50 rounded-lg p-2 border border-neutral-100"
                >
                    <ol
                        v-if="notifications.length !== 0"
                        class="flex flex-col gap-1"
                    >
                        <li
                            v-for="(notification, index) in notifications"
                            :key="index"
                        >
                            <NotificationItem
                                :read="notification.read_at ? true : false"
                                :title="notification.data.title"
                                :message="notification.data.message"
                                :timestamp="notification.created_at"
                            />
                        </li>
                    </ol>
                    <div
                        v-if="notifications.length === 0"
                        class="inline-flex h-full w-full items-center justify-center text-base text-neutral-400 select-none"
                    >
                        Tidak ada notifikasi
                    </div>
                </div>
                <div>
                    <div class="flex flex-row justify-between items-center">
                        <div>
                            <button
                                type="button"
                                class="btn btn-highlight-danger btn-sm"
                                @click="closeNotification"
                            >
                                Tutup
                            </button>
                        </div>
                        <div>
                            <button class="btn btn-info btn-sm">
                                <FontAwesomeIcon :icon="faListCheck" />
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
import { icon } from '@fortawesome/fontawesome-svg-core';
import { Link } from '@inertiajs/vue3';
import NotificationFilter from '@/Components/Dashboard/Layout/SidebarNotification/NotificationFilter.vue';
import { ref, watch } from 'vue';
import { filter } from 'lodash';
import NotificationItem from './NotificationItem.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faClose, faListCheck } from '@fortawesome/free-solid-svg-icons';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    isOpen: Boolean,
});

const filterActive = ref('all');

const toggleFilter = (type) => {
    filterActive.value = type;
};

const notificationsDefault = Object.freeze([
    { data: { title: '' } },
    { data: { title: '' } },
    { data: { title: '' } },
]);

const notifications = ref(notificationsDefault);

watch(
    () => props.isOpen,
    (val) => {
        if (val) {
            router.reload({
                data: { filter: 'asd', page: 1 },
                only: ['notifications'],
                preserveState: true,
                preserveScroll: true,
                onSuccess: (page) => {
                    notifications.value = page.props.notifications;
                },
            });
        } else {
            notifications.value = notificationsDefault;
        }
    }
);

const emit = defineEmits(['close']);

const closeNotification = () => {
    emit('close');
};
</script>
