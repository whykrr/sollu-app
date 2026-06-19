import {
    faChartPie,
    faStore,
    faMoneyCheckDollar,
    faTags,
    faFileInvoice,
    faUsersGear
} from '@fortawesome/free-solid-svg-icons'

export const cockpitSidebars = [
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
        label: 'Merchant Management',
        permissions: '',
        activeRoute: 'cockpit.merchants',
    },
    {
        route: 'cockpit.subscriptions.index',
        icon: faMoneyCheckDollar,
        label: 'Subscription & Billing',
        permissions: '',
        activeRoute: 'cockpit.subscriptions',
    },
    {
        route: 'cockpit.uoms.index',
        icon: faTags,
        label: 'Global UOM',
        permissions: '',
        activeRoute: 'cockpit.uoms',
    },
    {
        route: 'cockpit.payments.index',
        icon: faFileInvoice,
        label: 'Payment Validation',
        permissions: '',
        activeRoute: 'cockpit.payments',
    },
    {
        route: 'cockpit.config.index',
        icon: faUsersGear,
        label: 'Platform Config',
        permissions: '',
        activeRoute: 'cockpit.config',
    }
]
