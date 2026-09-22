<template>
    <div class="space-y-3 flex-1 overflow-y-auto">
        <!-- Header / Profil Section -->
        <div
            class="bg-white p-4 rounded-xl border border-slate-200 flex flex-col sm:flex-row gap-3 items-start sm:items-center"
        >
            <div
                class="w-12 h-12 bg-main/10 text-main rounded-full flex items-center justify-center text-lg font-bold shrink-0"
            >
                {{ getInitials(detail?.name) }}
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <h3 class="text-sm font-bold text-slate-800 truncate">
                        {{ detail?.name || '-' }}
                    </h3>
                    <span v-if="detail?.is_root_user" class="badge badge-warning text-[10px]">
                        Pemilik Usaha (Owner)
                    </span>
                    <span v-else-if="roleLabel" class="badge badge-info text-[10px]">
                        {{ roleLabel }}
                    </span>
                    <span v-if="detail?.deleted_at" class="badge badge-neutral-500 text-[10px]">
                        Arsip
                    </span>
                </div>
                <div class="text-slate-500 mt-1 flex flex-wrap items-center gap-3 text-xs">
                    <span class="flex items-center gap-1">
                        <FontAwesomeIcon :icon="faEnvelope" class="text-slate-400 text-xs" />
                        {{ detail?.email || '-' }}
                    </span>
                    <span v-if="detail?.phone" class="flex items-center gap-1">
                        <FontAwesomeIcon :icon="faPhone" class="text-slate-400 text-xs" />
                        {{ detail.phone }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Detail Information Grid -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 space-y-3">
            <h4 class="font-semibold text-xs text-slate-700 uppercase tracking-wider">
                Informasi Pegawai & Akses
            </h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                <div>
                    <span class="text-slate-500 block text-[11px]">Status PIN Kasir</span>
                    <span class="font-medium text-slate-800 flex items-center gap-1.5 mt-0.5">
                        <FontAwesomeIcon
                            :icon="faKey"
                            :class="detail?.has_pin ? 'text-emerald-500' : 'text-slate-300'"
                        />
                        {{ detail?.has_pin ? 'PIN Aktif Terpasang' : 'Belum Diatur' }}
                    </span>
                </div>
                <div>
                    <span class="text-slate-500 block text-[11px]">Terakhir Masuk (Login)</span>
                    <span class="font-medium text-slate-800 mt-0.5 block">
                        {{
                            detail?.last_login_at
                                ? formatDateTime(detail.last_login_at)
                                : 'Belum pernah login'
                        }}
                    </span>
                </div>
                <div>
                    <span class="text-slate-500 block text-[11px]">Tanggal Terdaftar</span>
                    <span class="font-medium text-slate-800 mt-0.5 block">
                        {{ detail?.created_at ? formatDateTime(detail.created_at) : '-' }}
                    </span>
                </div>
                <div>
                    <span class="text-slate-500 block text-[11px]">Peran Utama</span>
                    <span class="font-medium text-slate-800 mt-0.5 block">
                        {{
                            detail?.is_root_user
                                ? 'Pemilik Usaha (Full Access)'
                                : roleLabel || 'Belum diatur'
                        }}
                    </span>
                </div>
                <div class="sm:col-span-2">
                    <span class="text-slate-500 block text-[11px] mb-1"
                        >Akses Penugasan Outlet</span
                    >
                    <div v-if="detail?.is_root_user" class="text-xs text-slate-600">
                        <span class="badge badge-success text-xs"
                            >Seluruh Outlet (Akses Pemilik)</span
                        >
                    </div>
                    <div
                        v-else-if="detail?.outlets && detail.outlets.length > 0"
                        class="flex flex-wrap gap-1.5"
                    >
                        <span
                            v-for="outlet in detail.outlets"
                            :key="outlet.id"
                            class="badge badge-neutral-500 text-xs"
                        >
                            {{ outlet.name }}
                        </span>
                    </div>
                    <span v-else class="text-slate-400 italic"
                        >Belum ditugaskan ke outlet mana pun</span
                    >
                </div>
            </div>
        </div>
    </div>

    <Teleport v-if="isMounted" to="#popUpFooter">
        <div class="flex items-center justify-end w-full gap-2">
            <button type="button" class="btn btn-flat" @click="popUpStore.close()">Tutup</button>
            <button
                v-if="!detail?.deleted_at"
                type="button"
                class="btn btn-outline-main"
                @click="handleEdit"
            >
                Ubah Data
            </button>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { usePopUpStore } from '@/store/popup'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faEnvelope, faPhone, faKey } from '@fortawesome/free-solid-svg-icons'
import { formatDateTime } from '@/Composable/time'
import axios from 'axios'

const props = defineProps({
    user: {
        type: Object,
        default: () => ({}),
    },
    roles: {
        type: Array,
        default: () => [],
    },
})

const emit = defineEmits(['edit'])

const popUpStore = usePopUpStore()
const isMounted = ref(false)
const detail = ref({ ...props.user })
const isLoading = ref(true)

const roleLabel = computed(() => {
    if (detail.value?.roles && detail.value.roles.length > 0) {
        return detail.value.roles[0].label || detail.value.roles[0].name
    }
    return ''
})

onMounted(async () => {
    isMounted.value = true
    if (props.user?.id) {
        try {
            const response = await axios.get(route('employees.show', props.user.id))
            if (response?.data?.data) {
                detail.value = response.data.data
            }
        } catch (e) {
            console.error('Failed to load employee details on-demand:', e)
        } finally {
            isLoading.value = false
        }
    }
})

const getInitials = name => {
    if (!name) return '?'
    return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .substring(0, 2)
        .toUpperCase()
}

const handleEdit = () => {
    emit('edit', detail.value)
}
</script>
