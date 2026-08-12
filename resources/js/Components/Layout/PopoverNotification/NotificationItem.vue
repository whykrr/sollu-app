<template>
    <div
        class="relative px-3.5 py-3 hover:bg-neutral-100/60 transition-all duration-150 ease-in-out group flex gap-3 text-left bg-white"
        :class="{ 'animate-pulse bg-neutral-100': isSkeleton }"
    >
        <div class="flex-shrink-0">
            <div
                class="rounded-full w-9 h-9 flex items-center justify-center text-sm shadow-sm"
                :class="[iconConfig.bgClass, { 'bg-neutral-200 text-neutral-400': isSkeleton }]"
            >
                <FontAwesomeIcon v-if="!isSkeleton" :icon="iconConfig.icon" class="m-auto text-base" />
            </div>
        </div>
        <div v-if="!isSkeleton" class="space-y-1 flex-1 min-w-0">
            <div class="text-xs">
                <div class="font-medium text-neutral-800 leading-snug flex items-center gap-1.5">
                    <span v-if="!notification?.read_at" class="w-2 h-2 rounded-full bg-main flex-shrink-0" />
                    <span class="truncate">{{ notification.data.title || 'Pemberitahuan' }}</span>
                </div>
                <div class="text-neutral-500 mt-1 leading-normal text-[13px] break-words">
                    {{ notification.data.message }}
                </div>
                <div class="text-[11px] text-neutral-400 mt-1.5 font-normal">
                    {{ formatDateTime(notification.created_at) }}
                </div>
            </div>
            <div v-if="notification.data.action_url" class="pt-1.5">
                <a 
                    v-if="!isExpired"
                    :href="notification.data.action_url" 
                    class="btn btn-outline-main btn-xs rounded-lg font-medium text-xs px-2.5 py-1 inline-block"
                >
                    {{ notification.data.action_text || 'Lihat Detail' }}
                </a>
                <button 
                    v-else
                    disabled
                    class="text-xs text-neutral-400 bg-neutral-100 px-2 py-0.5 rounded cursor-not-allowed"
                >
                    Link Kedaluwarsa
                </button>
            </div>
        </div>
        <div v-else class="space-y-2 w-full flex-1 pt-1">
            <div class="placeholder h-3 bg-neutral-200 rounded w-3/4" />
            <div class="placeholder h-2.5 bg-neutral-200 rounded w-full" />
            <div class="placeholder h-2 bg-neutral-200 rounded w-1/4" />
        </div>

        <div v-if="!isSkeleton" class="flex flex-col gap-1.5 items-end opacity-0 group-hover:opacity-100 transition-opacity">
            <button 
                v-if="!notification.read_at"
                type="button"
                class="text-neutral-400 hover:text-main p-1 rounded-md hover:bg-neutral-200/60 transition-colors" 
                title="Tandai dibaca"
                @click="$emit('read', notification.id)"
            >
                <FontAwesomeIcon :icon="faCheckDouble" class="text-xs" />
            </button>
            <button 
                type="button"
                class="text-neutral-400 hover:text-danger p-1 rounded-md hover:bg-neutral-200/60 transition-colors" 
                title="Hapus notifikasi"
                @click="$emit('delete', notification.id)"
            >
                <FontAwesomeIcon :icon="faTrash" class="text-xs" />
            </button>
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
            return { bgClass: 'bg-emerald-50 text-emerald-600 border border-emerald-100', icon: faCircleCheck };
        case 'warning':
            return { bgClass: 'bg-amber-50 text-amber-600 border border-amber-100', icon: faTriangleExclamation };
        case 'danger':
        case 'error':
            return { bgClass: 'bg-rose-50 text-rose-600 border border-rose-100', icon: faTriangleExclamation };
        case 'info':
        default:
            return { bgClass: 'bg-sky-50 text-sky-600 border border-sky-100', icon: faCircleInfo };
    }
});
</script>
