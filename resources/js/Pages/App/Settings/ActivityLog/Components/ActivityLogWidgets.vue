<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
        <!-- Widget 1: Total Aktivitas -->
        <Widget
            :icon="faHistory"
            title="Total Aktivitas"
        >
            <span>{{ Number(stats.total_activities || 0).toLocaleString('id-ID') }}</span>
        </Widget>

        <!-- Widget 2: Tindakan Sensitif -->
        <Widget
            :icon="faShieldHalved"
            title="Tindakan Sensitif"
        >
            <span class="text-danger">{{ Number(stats.sensitive_actions_count || 0).toLocaleString('id-ID') }}</span>
        </Widget>
    </div>
</template>

<script setup>
import { faHistory, faShieldHalved } from '@fortawesome/free-solid-svg-icons'
import Widget from '@/Components/Widgets/Widget.vue'

defineProps({
    stats: {
        type: Object,
        default: () => ({
            total_activities: 0,
            sensitive_actions_count: 0,
        }),
    },
})
</script>
