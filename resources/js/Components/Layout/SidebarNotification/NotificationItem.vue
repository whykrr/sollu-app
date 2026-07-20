<template>
    <div
        class="relative bg-white px-3 py-2 hover:bg-neutral-300/30 rounded-lg group transition"
        :class="{ 'animate-pulse bg-neutral-300': isSkeleton }"
    >
        <span
            v-if="!isSkeleton && !notification?.read_at"
            class="absolute w-2.5 h-2.5 bg-danger top-3 left-3 rounded-full border-2 border-white shadow-sm"
        />
        <div class="flex flex-row gap-3">
            <div>
                <div
                    class="flex items-center justify-center rounded-full h-10 w-10 text-white shadow-sm"
                    :class="[iconConfig.bgClass, { 'bg-neutral-400 text-neutral-300': isSkeleton }]"
                >
                    <FontAwesomeIcon v-if="!isSkeleton" :icon="iconConfig.icon" class="m-auto text-lg" />
                </div>
            </div>
            <div v-if="!isSkeleton" class="space-y-1 flex-1">
                <div class="text-sm">
                    <div class="font-semibold text-gray-800 leading-tight">
                        {{ notification.data.title || 'Pemberitahuan' }}
                    </div>
                    <div class="text-gray-600 mt-1 leading-snug">
                        {{ notification.data.message }}
                    </div>
                    <div class="text-xs text-neutral-400 mt-1">
                        {{ formatDateTime(notification.created_at) }}
                    </div>
                </div>
                <div v-if="notification.data.action_url" class="pt-2">
                    <a 
                        v-if="!isExpired"
                        :href="notification.data.action_url" 
                        class="btn btn-outline-main btn-sm rounded-lg"
                    >
                        {{ notification.data.action_text || 'Lihat Detail' }}
                    </a>
                    <button 
                        v-else
                        disabled
                        class="btn btn-flat text-neutral-400 bg-neutral-100 btn-sm rounded-lg cursor-not-allowed"
                    >
                        Link Kedaluwarsa
                    </button>
                </div>
            </div>
            <div v-else class="space-y-2 w-full flex-1 pt-1">
                <div class="placeholder h-3 bg-neutral-400 rounded w-3/4" />
                <div class="placeholder h-2 bg-neutral-400 rounded w-full" />
                <div class="placeholder h-2 bg-neutral-400 rounded w-1/4" />
            </div>

            <div v-if="!isSkeleton" class="flex flex-col gap-2 items-end opacity-0 group-hover:opacity-100 transition-opacity">
                <button 
                    v-if="!notification.read_at"
                    type="button"
                    class="text-neutral-400 hover:text-main bg-white p-1 rounded hover:bg-neutral-100" 
                    title="Tandai dibaca"
                    @click="$emit('read', notification.id)"
                >
                    <FontAwesomeIcon :icon="faCheckDouble" class="text-sm" />
                </button>
                <button 
                    type="button"
                    class="text-neutral-400 hover:text-danger bg-white p-1 rounded hover:bg-neutral-100" 
                    title="Hapus notifikasi"
                    @click="$emit('delete', notification.id)"
                >
                    <FontAwesomeIcon :icon="faTrash" class="text-sm" />
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { formatDateTime } from '@/Composable/time';
import { 
    faShop, faTrash, faCheckDouble, 
    faCircleInfo, faCircleCheck, faTriangleExclamation 
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const props = defineProps({
    notification: {
        type: Object,
        default: null,
    }
});

defineEmits(['read', 'delete']);

const isSkeleton = computed(() => !props.notification || !props.notification.id);

const isExpired = computed(() => {
    if (isSkeleton.value) return false;
    const expiresAt = props.notification.data.expires_at;
    if (!expiresAt) return false;
    
    return new Date(expiresAt) < new Date();
});

const iconConfig = computed(() => {
    if (isSkeleton.value) return { bgClass: '', icon: faShop };

    const type = props.notification.data.type || 'info';
    
    switch(type) {
        case 'success':
            return { bgClass: 'bg-success', icon: faCircleCheck };
        case 'warning':
            return { bgClass: 'bg-warning', icon: faTriangleExclamation };
        case 'danger':
        case 'error':
            return { bgClass: 'bg-danger', icon: faTriangleExclamation };
        case 'info':
        default:
            return { bgClass: 'bg-info', icon: faCircleInfo };
    }
});
</script>
