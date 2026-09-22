import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import {
    faChartPie,
    faStore,
    faFileInvoice,
    faUsersGear,
    faLayerGroup,
    faBriefcase,
    faBuildingColumns,
    faBinoculars,
    faServer,
    faHeartPulse,
} from '@fortawesome/free-solid-svg-icons'

export const getCockpitSidebars = () => {
    const page = usePage()
    const features = page?.props?.features || {}

    const items = [
        {
            route: 'cockpit.dashboard',
            icon: faChartPie,
            label: 'Dashboard',
            permissions: '',
            activeRoute: 'cockpit.dashboard',
        },
        {
            route: 'cockpit.merchants.index',
            icon: faStore,
            label: 'Manajemen Merchant',
            permissions: '',
            activeRoute: 'cockpit.merchants',
        },
        {
            route: 'cockpit.invoices.index',
            icon: faFileInvoice,
            label: 'Invoice Langganan',
            permissions: '',
            activeRoute: 'cockpit.invoices',
        },
        {
            route: 'cockpit.subscription-plans.index',
            icon: faLayerGroup,
            label: 'Paket Langganan',
            permissions: '',
            activeRoute: 'cockpit.subscription-plans',
        },
        {
            route: 'cockpit.business-types.index',
            icon: faBriefcase,
            label: 'Jenis Bisnis',
            permissions: '',
            activeRoute: 'cockpit.business-types',
        },
        {
            route: 'cockpit.payment-methods.index',
            icon: faBuildingColumns,
            label: 'Rekening Manual',
            permissions: '',
            activeRoute: 'cockpit.payment-methods',
        },
        // {
        //     route: 'cockpit.uoms.index',
        //     icon: faTags,
        //     label: 'Satuan Global (UOM)',
        //     permissions: '',
        //     activeRoute: 'cockpit.uoms',
        // },
        {
            route: 'cockpit.config.index',
            icon: faUsersGear,
            label: 'Konfigurasi Platform',
            permissions: '',
            activeRoute: 'cockpit.config',
        },
        {
            route: '',
            label: 'PEMANTAUAN & ALAT',
            separator: true,
        },
        {
            route: 'horizon',
            href: '/horizon',
            icon: faServer,
            label: 'Antrean (Horizon)',
            permissions: '',
            activeRoute: 'horizon',
            external: true,
        },
        {
            route: 'pulse',
            href: '/pulse',
            icon: faHeartPulse,
            label: 'Observabilitas (Pulse)',
            permissions: '',
            activeRoute: 'pulse',
            external: true,
        },
    ]

    if (features.has_telescope) {
        items.push({
            route: 'telescope',
            href: '/telescope',
            icon: faBinoculars,
            label: 'Pemantauan (Telescope)',
            permissions: '',
            activeRoute: 'telescope',
            external: true,
        })
    }

    return items
}

export function useCockpitSidebar() {
    const cockpitSidebars = computed(() => getCockpitSidebars())

    return {
        cockpitSidebars,
        getCockpitSidebars,
    }
}

