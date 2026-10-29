<template>
    <div class="flex items-center gap-1 border-b border-slate-200 mb-4 overflow-x-auto">
        <Link
            :href="route('settings.outlets.index')"
            class="px-4 py-2.5 text-xs font-semibold border-b-2 transition-colors flex items-center gap-2 cursor-pointer whitespace-nowrap"
            :class="[
                activeTab === 'outlets'
                    ? 'border-main text-main font-bold'
                    : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
            ]"
        >
            <FontAwesomeIcon :icon="faStore" />
            <span>Daftar Cabang</span>
        </Link>

        <Link
            :href="route('settings.operational.index')"
            class="px-4 py-2.5 text-xs font-semibold border-b-2 transition-colors flex items-center gap-2 cursor-pointer whitespace-nowrap"
            :class="[
                activeTab === 'operational'
                    ? 'border-main text-main font-bold'
                    : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
            ]"
        >
            <FontAwesomeIcon :icon="faClock" />
            <span>Jam Operasional</span>
        </Link>

        <Link
            v-can="$enums.PermissionEnum?.SETTING_DEVICE"
            :href="route('settings.devices.index')"
            class="px-4 py-2.5 text-xs font-semibold border-b-2 transition-colors flex items-center gap-2 cursor-pointer whitespace-nowrap"
            :class="[
                activeTab === 'devices'
                    ? 'border-main text-main font-bold'
                    : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
            ]"
        >
            <FontAwesomeIcon :icon="faCashRegister" />
            <span>Perangkat Kasir (POS)</span>
        </Link>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCashRegister, faClock, faStore } from '@fortawesome/free-solid-svg-icons'

defineProps({
    activeTab: {
        type: String,
        required: true,
    },
})
</script>
