<template>
    <PopUpPage
        :title="title"
        :sub-title="subTitle"
        :class="{ show: show }"
        size="lg"
        @close="closeDetail"
    >
        <div class="-mx-4">
            <Tab :pages="tabPages" :vertical="false" />
        </div>
    </PopUpPage>
</template>

<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import PopUpPage from '@/Components/UI/PopUpPage.vue';
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

const emit = defineEmits(['close']);

const props = defineProps({
    show: Boolean,
    outlet: Object,
});

const title = computed(() => 'Detail Outlet');
const subTitle = computed(() => (props.outlet ? '#' + props.outlet.slug : ''));

const closeDetail = () => {
    emit('close');
    router.get(
        route('settings.outlets.index'),
        {},
        {
            only: ['outlet'],
            preserveState: true,
            preserveScroll: true,
        },
    );
};

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
