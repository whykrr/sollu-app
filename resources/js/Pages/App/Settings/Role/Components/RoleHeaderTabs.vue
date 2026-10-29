<template>
    <div class="flex items-center gap-1 border-b border-slate-200 mb-4 overflow-x-auto">
        <Link
            :href="route('settings.roles.index')"
            class="px-4 py-2.5 text-xs font-semibold border-b-2 transition-colors flex items-center gap-2 cursor-pointer whitespace-nowrap"
            :class="[
                activeTab === 'roles'
                    ? 'border-main text-main font-bold'
                    : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
            ]"
        >
            <FontAwesomeIcon :icon="faUserShield" />
            <span>Peran & Hak Akses</span>
        </Link>

        <Link
            v-can="$enums.PermissionEnum?.SETTING_AUDIT"
            :href="route('settings.activity-logs.index')"
            class="px-4 py-2.5 text-xs font-semibold border-b-2 transition-colors flex items-center gap-2 cursor-pointer whitespace-nowrap"
            :class="[
                activeTab === 'activity-logs'
                    ? 'border-main text-main font-bold'
                    : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
            ]"
        >
            <FontAwesomeIcon :icon="faHistory" />
            <span>Log Aktivitas</span>
        </Link>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faHistory, faUserShield } from '@fortawesome/free-solid-svg-icons'

defineProps({
    activeTab: {
        type: String,
        required: true,
    },
})
</script>
