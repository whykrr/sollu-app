<template>
    <div class="-mx-4">
        <Tab :pages="tabPages" :vertical="false" />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import Tab from '@/Components/UI/Tab.vue';

// Tabs Components
import GeneralTab from '../Tabs/GeneralTab.vue';
import SettingsTab from '../Tabs/SettingsTab.vue';
import DevicesTab from '../Tabs/DevicesTab.vue';
import OperationalHoursTab from '../Tabs/OperationalHoursTab.vue';
import AuditLogsTab from '../Tabs/AuditLogsTab.vue';

// Icons
import {
    faBuilding,
    faCog,
    faMobileAlt,
    faClock,
    faListUl,
} from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    outlet: Object,
});

const tabPages = computed(() => [
    {
        label: 'General',
        icon: faBuilding,
        page: GeneralTab,
        props: { outlet: props.outlet },
    },
    {
        label: 'Settings',
        icon: faCog,
        page: SettingsTab,
        props: { outlet: props.outlet },
    },
    {
        label: 'Devices',
        icon: faMobileAlt,
        page: DevicesTab,
        props: { outlet: props.outlet },
    },
    {
        label: 'Operational Hours',
        icon: faClock,
        page: OperationalHoursTab,
        props: { outlet: props.outlet },
    },
    {
        label: 'Audit Logs',
        icon: faListUl,
        page: AuditLogsTab,
        props: { outlet: props.outlet },
    },
]);
</script>
