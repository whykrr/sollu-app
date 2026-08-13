<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Daftar Promo">
                <button class="btn btn-highlight-main" @click="openCreate">
                    <FontAwesomeIcon :icon="faPlus" />
                    Buat Promo
                </button>
            </MainPageHeader>
            <PromoFilter :filters="filters" />
        </template>
        <Table :headers="headers" :data="promos.data" :action="true">
            <template #target_type="{ row }">
                <span class="badge badge-neutral">{{
                    row.target_type === 'product' ? 'Per Produk' : 'Per Bill'
                }}</span>
            </template>
            <template #promo_value="{ row }">
                <div v-if="row.promo_type === 'percentage'">
                    {{ row.discount_value }}%
                    <span
                        v-if="row.max_discount"
                        class="text-xs text-slate-500 block"
                    >
                        (Max {{ formatCurrency(row.max_discount) }})
                    </span>
                </div>
                <div v-else>
                    {{ formatCurrency(row.discount_value) }}
                </div>
            </template>
            <template #period="{ row }">
                <div class="text-sm">
                    {{ formatDate(row.start_date) }} -
                    {{ formatDate(row.end_date) }}
                </div>
                <div
                    v-if="row.start_time && row.end_time"
                    class="text-xs text-slate-500"
                >
                    {{ formatTime(row.start_time) }} -
                    {{ formatTime(row.end_time) }}
                </div>
            </template>
            <template #status="{ row }">
                <span :class="getStatusBadge(row)">
                    {{ getStatusLabel(row) }}
                </span>
            </template>
            <template #actions="{ row }">
                <div class="flex items-center gap-2 justify-end">
                    <button
                        class="btn btn-flat btn-sm"
                        title="Detail Promo"
                        @click="openDetail(row)"
                    >
                        <FontAwesomeIcon :icon="faEye" />
                    </button>
                    <div
                        class="relative"
                        v-if="row.status !== 'expired' && !isExpired(row)"
                    >
                        <button
                            class="btn btn-flat btn-sm"
                            title="Opsi"
                            @click.stop="toggleDropdown(row.id)"
                        >
                            <FontAwesomeIcon :icon="faEllipsisVertical" />
                        </button>
                        <div
                            v-if="activeDropdownId === row.id"
                            class="absolute right-0 top-8 bg-white border border-slate-200 shadow-lg rounded-lg py-1 z-10 w-48"
                        >
                            <button
                                v-if="row.status === 'draft'"
                                class="block w-full text-left px-4 py-2 text-sm hover:bg-slate-50"
                                @click="
                                    openEdit(row);
                                    activeDropdownId = null;
                                "
                            >
                                Ubah
                            </button>
                            <button
                                v-if="
                                    row.status === 'draft' ||
                                    row.status === 'inactive'
                                "
                                class="block w-full text-left px-4 py-2 text-sm hover:bg-slate-50 text-success"
                                @click="
                                    publishPromo(row.id);
                                    activeDropdownId = null;
                                "
                            >
                                Publish
                            </button>
                            <button
                                v-if="row.status === 'active'"
                                class="block w-full text-left px-4 py-2 text-sm hover:bg-slate-50 text-warning"
                                @click="
                                    unpublishPromo(row.id);
                                    activeDropdownId = null;
                                "
                            >
                                Nonaktifkan
                            </button>
                            <button
                                v-if="row.status === 'draft'"
                                class="block w-full text-left px-4 py-2 text-sm hover:bg-slate-50 text-danger"
                                @click="
                                    deletePromo(row.id);
                                    activeDropdownId = null;
                                "
                            >
                                Hapus
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </Table>

        <template #footer>
            <Pagination
                :links="promos.links"
                :from="promos.from"
                :to="promos.to"
                :total="promos.total"
                :per-page="promos.per_page ?? 20"
            />
        </template>
    </MainPage>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import MainPage from '@/Components/UI/MainPage.vue';
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue';
import Table from '@/Components/Tables/Table.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faPlus,
    faEye,
    faEllipsisVertical,
} from '@fortawesome/free-solid-svg-icons';
import PromoFilter from './Components/PromoFilter.vue';
import PromoForm from './Components/PromoForm.vue';
import PromoDetail from './Components/PromoDetail.vue';
import { usePopUpStore } from '@/store/popup';
import { useModalStore } from '@/store/notification.js';

const popUpStore = usePopUpStore();
const modal = useModalStore();

const props = defineProps({
    promos: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const headers = [
    { label: 'Nama Promo', field: 'name', sortable: true },
    {
        label: 'Target',
        field: 'target_type',
        slot: 'target_type',
        sortable: false,
    },
    {
        label: 'Tipe & Nilai',
        field: 'discount_value',
        slot: 'promo_value',
        sortable: false,
    },
    { label: 'Periode', field: 'start_date', slot: 'period', sortable: true },
    { label: 'Status', field: 'status', slot: 'status', sortable: false },
];

const activeDropdownId = ref(null);

const toggleDropdown = (id) => {
    activeDropdownId.value = activeDropdownId.value === id ? null : id;
};

const closeDropdown = () => {
    activeDropdownId.value = null;
};

onMounted(() => {
    window.addEventListener('click', closeDropdown);
});

onUnmounted(() => {
    window.removeEventListener('click', closeDropdown);
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value || 0);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

const formatTime = (timeString) => {
    if (!timeString) return '';
    return timeString.substring(0, 5);
};

const isExpired = (promo) => {
    if (!promo.end_date) return false;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const endDate = new Date(promo.end_date);
    endDate.setHours(0, 0, 0, 0);
    return endDate < today;
};

const getComputedStatus = (promo) => {
    if (promo.status === 'active' && isExpired(promo)) {
        return 'expired';
    }
    return promo.status;
};

const getStatusBadge = (promo) => {
    const status = getComputedStatus(promo);
    switch (status) {
        case 'active':
            return 'badge badge-success';
        case 'inactive':
            return 'badge badge-warning';
        case 'expired':
            return 'badge badge-danger';
        case 'draft':
        default:
            return 'badge badge-neutral';
    }
};

const getStatusLabel = (promo) => {
    const status = getComputedStatus(promo);
    switch (status) {
        case 'active':
            return 'Aktif';
        case 'inactive':
            return 'Nonaktif';
        case 'expired':
            return 'Kedaluwarsa';
        case 'draft':
            return 'Draf';
        default:
            return status;
    }
};

const openCreate = () => {
    popUpStore.open({
        title: 'Buat Promo Baru',
        component: PromoForm,
        size: 'lg',
        props: {
            promo: null,
        },
    });
};

const openEdit = (promo) => {
    popUpStore.open({
        title: 'Ubah Promo',
        component: PromoForm,
        size: 'lg',
        props: {
            promo,
        },
    });
};

const openDetail = (promo) => {
    popUpStore.open({
        title: 'Detail Promo',
        component: PromoDetail,
        size: 'lg',
        props: {
            promo,
            computedStatus: getComputedStatus(promo),
            isExpired: isExpired(promo),
        },
    });
};

const publishPromo = (id) => {
    modal.open({
        title: 'Konfirmasi Publish Promo',
        message:
            'Apakah Anda yakin ingin mempublikasikan promo ini? Setelah dipublikasikan, promo akan langsung aktif dan berlaku sesuai jadwal yang ditentukan.',
        confirmButtonText: 'Ya, Publikasikan',
        cancelButtonText: 'Batal',
        onConfirm: () => {
            router.post(
                route('promotions.publish', id),
                {},
                {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => popUpStore.close(),
                },
            );
        },
    });
};

const unpublishPromo = (id) => {
    modal.open({
        title: 'Konfirmasi Nonaktifkan Promo',
        type: 'warning',
        message:
            'Apakah Anda yakin ingin menonaktifkan promo ini? Setelah dinonaktifkan, promo tidak akan berlaku lagi.',
        confirmButtonText: 'Ya',
        cancelButtonText: 'Batal',
        onConfirm: () => {
            router.post(
                route('promotions.unpublish', id),
                {},
                {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => popUpStore.close(),
                },
            );
        },
    });
};

const deletePromo = (id) => {
    modal.open({
        title: 'Konfirmasi Hapus Promo',
        type: 'danger',
        message:
            'Apakah Anda yakin ingin menghapus promo draf ini? Tindakan ini tidak dapat dibatalkan.',
        confirmButtonText: 'Ya',
        cancelButtonText: 'Batal',
        onConfirm: () => {
            router.delete(route('promotions.destroy', id), {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => popUpStore.close(),
            });
        },
    });
};
</script>
