import { computed } from 'vue'
import {
    faChartPie,
    faStore,
    faTags,
    faFileInvoice,
    faUsersGear,
    faLayerGroup,
    faBriefcase,
    faBuildingColumns,
    faBinoculars,
} from '@fortawesome/free-solid-svg-icons'

export const getCockpitSidebars = () => [
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
    {
        route: 'cockpit.uoms.index',
        icon: faTags,
        label: 'Satuan Global (UOM)',
        permissions: '',
        activeRoute: 'cockpit.uoms',
    },
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
        route: 'telescope',
        href: '/telescope',
        icon: faBinoculars,
        label: 'Pemantauan Telescope',
        permissions: '',
        activeRoute: 'telescope',
        external: true,
    },
]

export function useCockpitSidebar() {
    const cockpitSidebars = computed(() => getCockpitSidebars())

    return {
        cockpitSidebars,
        getCockpitSidebars,
    }
}

export const cockpitSidebars = getCockpitSidebars()
