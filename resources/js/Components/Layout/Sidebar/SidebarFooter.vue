<template>
    <div class="px-2 relative space-y-0 pb-3">
        <!-- Back to Main Menu Button (Only shown in Settings mode) -->
        <div v-if="isSetting" class="mb-2">
            <Link
                :href="route('overview')"
                class="flex items-center justify-center gap-2 w-full py-2.5 px-3 min-h-[40px] sm:min-h-[36px] text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 active:bg-slate-300 border border-slate-200/80 rounded-xl transition-all duration-200 active:scale-[0.98] group touch-manipulation"
            >
                <FontAwesomeIcon
                    :icon="faArrowLeft"
                    class="text-xs text-slate-500 group-hover:-translate-x-0.5 transition-transform"
                />
                <span>Kembali ke Menu Utama</span>
            </Link>
        </div>

        <div>
            <SidebarBillingWidget />
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faArrowLeft } from '@fortawesome/free-solid-svg-icons'
import SidebarBillingWidget from './SidebarBillingWidget.vue'

defineProps({
    isSetting: Boolean,
})
</script>
