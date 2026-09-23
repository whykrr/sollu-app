<template>
    <div
        class="bg-white rounded-xl border border-slate-200 p-4 sm:p-5 flex flex-col justify-between transition-all duration-150 hover:border-slate-300 hover:bg-slate-50/50 cursor-pointer"
        @click="$emit('click', device)"
    >
        <div>
            <!-- Header Kartu -->
            <div class="flex items-start justify-between gap-2 mb-3">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div
                        class="size-9 rounded-lg bg-main/10 text-main flex items-center justify-center shrink-0"
                    >
                        <FontAwesomeIcon :icon="getDeviceIcon(device.device_type)" />
                    </div>
                    <div class="min-w-0">
                        <h4
                            class="font-semibold text-slate-800 text-sm leading-snug truncate"
                            :title="device.device_name"
                        >
                            {{ device.device_name }}
                        </h4>
                        <span class="text-xs text-slate-500 block truncate">
                            {{ formatDeviceType(device.device_type) }}
                        </span>
                    </div>
                </div>
                <span
                    v-if="device.is_active"
                    class="badge badge-success text-[11px] font-semibold shrink-0"
                >
                    Aktif
                </span>
                <span v-else class="badge badge-danger text-[11px] font-semibold shrink-0">
                    Nonaktif
                </span>
            </div>

            <!-- Outlet Badge (jika multi-outlet) -->
            <div v-if="showOutlet && device.outlet" class="mb-2.5">
                <span
                    class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-600 bg-slate-100 px-2 py-0.5 rounded-md border border-slate-200"
                >
                    <FontAwesomeIcon :icon="faStore" class="text-[10px] text-slate-400" />
                    <span>{{ device.outlet.name }}</span>
                </span>
            </div>

            <!-- Metadata Info -->
            <div
                class="space-y-1.5 text-xs text-slate-600 py-2 border-t border-b border-slate-100 my-2.5"
            >
                <div class="flex justify-between items-center">
                    <span class="text-slate-400">Nomor Seri / ID:</span>
                    <span class="font-mono text-slate-700 truncate max-w-[55%] text-right">
                        {{ device.serial_number || '-' }}
                    </span>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-slate-400">Versi & Platform:</span>
                    <span class="text-slate-700 truncate max-w-[55%] text-right">
                        <template v-if="device.platform_type || device.app_version">
                            <span v-if="device.platform_type" class="capitalize">{{
                                device.platform_type
                            }}</span>
                            <span v-if="device.platform_type && device.app_version"> • </span>
                            <span v-if="device.app_version">v{{ device.app_version }}</span>
                        </template>
                        <template v-else>-</template>
                    </span>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-slate-400">Status Sinkronisasi:</span>
                    <span
                        v-if="device.tokens_count > 0"
                        class="text-emerald-600 font-medium inline-flex items-center gap-1"
                    >
                        <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Terhubung
                    </span>
                    <span v-else class="text-slate-400">Belum Terhubung</span>
                </div>
            </div>
        </div>

        <!-- Footer Tombol Aksi -->
        <div class="flex items-center justify-between gap-2 pt-2" @click.stop>
            <button
                type="button"
                class="btn btn-outline-main btn-xs rounded-lg px-2.5 py-1.5 flex items-center gap-1.5 cursor-pointer"
                title="Generate kode OTP untuk menghubungkan aplikasi POS"
                @click.stop="$emit('generate-otp', device.id)"
            >
                <FontAwesomeIcon :icon="faKey" />
                <span>Pairing OTP</span>
            </button>

            <div class="flex items-center gap-1">
                <button
                    v-if="device.tokens_count > 0"
                    type="button"
                    class="btn btn-highlight-warning btn-xs rounded-lg cursor-pointer"
                    title="Putuskan koneksi (Unpair)"
                    @click.stop="$emit('unpair', device.id)"
                >
                    <FontAwesomeIcon :icon="faUnlink" />
                </button>
                <button
                    type="button"
                    class="btn btn-highlight-main btn-xs rounded-lg cursor-pointer"
                    title="Ubah Data Perangkat"
                    @click.stop="$emit('edit', device)"
                >
                    <FontAwesomeIcon :icon="faPencil" />
                </button>
                <button
                    type="button"
                    class="btn btn-highlight-danger btn-xs rounded-lg cursor-pointer"
                    title="Hapus Perangkat"
                    @click.stop="$emit('delete', device.id)"
                >
                    <FontAwesomeIcon :icon="faTrash" />
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faDesktop,
    faKey,
    faPencil,
    faStore,
    faTabletAlt,
    faTrash,
    faUnlink,
    faUtensils,
    faCashRegister,
} from '@fortawesome/free-solid-svg-icons'

defineProps({
    device: {
        type: Object,
        required: true,
    },
    showOutlet: {
        type: Boolean,
        default: false,
    },
})

defineEmits(['click', 'edit', 'generate-otp', 'unpair', 'delete'])

const getDeviceIcon = type => {
    switch (type) {
        case 'pos_terminal':
        case 'pos':
            return faDesktop
        case 'pos_mobile':
            return faTabletAlt
        case 'kitchen_display':
        case 'kds':
            return faUtensils
        case 'kiosk':
            return faCashRegister
        default:
            return faDesktop
    }
}

const formatDeviceType = type => {
    switch (type) {
        case 'pos_terminal':
        case 'pos':
            return 'POS Terminal (Desktop)'
        case 'pos_mobile':
            return 'POS Mobile (Tablet/HP)'
        case 'kitchen_display':
        case 'kds':
            return 'Kitchen Device'
        case 'kiosk':
            return 'Kiosk / Self-Service'
        default:
            return type || '-'
    }
}
</script>
