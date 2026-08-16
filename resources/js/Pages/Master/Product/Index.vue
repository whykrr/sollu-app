<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Data Produk">
                <button class="btn btn-flat btn-sm" @click="exportCsv">
                    <FontAwesomeIcon :icon="faDownload" />
                    Ekspor Data
                </button>
                <button
                    class="btn btn-flat btn-sm"
                    @click="showImportModal = true"
                >
                    <FontAwesomeIcon :icon="faUpload" />
                    Impor Data
                </button>
                <button class="btn btn-highlight-main" @click="openCreate">
                    <FontAwesomeIcon :icon="faPlus" />
                    Tambah Baru
                </button>
            </MainPageHeader>
            <ProductFilter :filters="filters" :categories="categories" />
        </template>
        <Table :headers="headers" :data="products.data" :action="true">
            <template #image="{ row }">
                <img
                    v-if="row.cover_image_url"
                    :src="row.cover_image_url"
                    class="w-10 h-10 object-cover rounded-lg border border-slate-200"
                    alt="Product thumbnail"
                />
                <div
                    v-else
                    class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center text-slate-400 border border-slate-200"
                >
                    <FontAwesomeIcon :icon="faImage" class="text-sm" />
                </div>
            </template>
            <template #code="{ row }">
                {{ row.code || '-' }}
            </template>
            <template #type="{ row }">
                <span class="capitalize">{{ row.product_type }}</span>
            </template>
            <template #category="{ row }">
                {{ row.category?.name || '-' }}
            </template>
            <template #base_price="{ row }">
                {{ getBasePrice(row) }}
            </template>
            <template #status="{ row }">
                <span v-if="row.is_show" class="badge badge-success"
                    >Aktif</span
                >
                <span v-else class="badge badge-neutral-500">Non-Aktif</span>
            </template>
            <template #actions="{ row }">
                <div class="flex items-center gap-1 justify-end">
                    <button
                        class="btn btn-flat btn-sm"
                        title="Ubah Produk"
                        @click="openEdit(row)"
                    >
                        <FontAwesomeIcon :icon="faPencil" />
                    </button>
                    <button
                        class="btn btn-flat btn-sm text-danger"
                        title="Hapus"
                        @click="archiveProduct(row.id)"
                    >
                        <FontAwesomeIcon :icon="faTrash" />
                    </button>
                </div>
            </template>
        </Table>

        <template #footer>
            <Pagination
                :links="products.links"
                :from="products.from"
                :to="products.to"
                :total="products.total"
                :per-page="products.per_page ?? 20"
            />
        </template>

        <ImportCsvModal
            :show="showImportModal"
            module-name="Produk"
            :template-url="route('master.products.importTemplate')"
            :import-url="route('master.products.import')"
            @close="showImportModal = false"
        />
    </MainPage>
</template>

<script setup>
import { ref, watch, provide, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import MainPage from '@/Components/UI/MainPage.vue';
import Table from '@/Components/Tables/Table.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faPlus,
    faPencil,
    faTrash,
    faImage,
    faUpload,
    faDownload,
} from '@fortawesome/free-solid-svg-icons';
import { debounce } from 'lodash';
import ProductFilter from './Components/ProductFilter.vue';
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue';
import { usePopUpStore } from '@/store/popup';
import CreateEditWrapper from './CreateEditWrapper.vue';
import ImportCsvModal from '@/Components/Modals/ImportCsvModal.vue';
import { useModalStore } from '@/store/notification.js';

const popUpStore = usePopUpStore();
const modalStore = useModalStore();
const page = usePage();

const props = defineProps({
    products: Object,
    filters: Object,
    categories: Array,
});

// Provide master data for popup components
provide(
    'categories',
    computed(
        () =>
            page.props.rawCategories ||
            page.props.categories ||
            props.categories ||
            [],
    ),
);
provide(
    'outlets',
    computed(() => page.props.outlets || []),
);
provide(
    'modifierGroups',
    computed(() => page.props.modifierGroups || []),
);
provide(
    'inventoryItems',
    computed(() => page.props.inventoryItems || []),
);
provide(
    'products',
    computed(() => page.props.baseProducts || []),
);
provide(
    'uoms',
    computed(() => page.props.uoms || []),
);

const headers = [
    { label: 'Foto', field: 'image', slot: 'image', sortable: false },
    { label: 'Kode', field: 'code', slot: 'code', sortable: true },
    { label: 'Nama Produk', field: 'name', sortable: true },
    { label: 'Tipe', field: 'product_type', slot: 'type', sortable: true },
    { label: 'Kategori', field: 'category', slot: 'category', sortable: false },
    {
        label: 'Harga Dasar',
        field: 'base_price',
        slot: 'base_price',
        sortable: false,
    },
    { label: 'Status', field: 'is_show', slot: 'status', sortable: false },
];

const search = ref('');
const showImportModal = ref(false);

watch(
    search,
    debounce((newVal) => {
        router.get(
            route('master.products.index'),
            { ...route().params, search: newVal, page: 1 },
            { preserveState: true, preserveScroll: true },
        );
    }, 500),
);

const getBasePrice = (product) => {
    const price = product.prices?.find((p) => !p.outlet_id);
    return price
        ? new Intl.NumberFormat('id-ID', {
              style: 'currency',
              currency: 'IDR',
          }).format(price.amount)
        : '-';
};

const archiveProduct = (id) => {
    modalStore.confirm({
        title: 'Konfirmasi Pengarsipan',
        type: 'danger',
        message: 'Apakah Anda yakin ingin mengarsipkan produk ini?',
        confirmText: 'Ya, Arsipkan',
        cancelText: 'Batal',
        onConfirm: () => {
            router.delete(route('master.products.destroy', id));
        },
    });
};

const exportCsv = () => {
    router.get(
        route('master.products.export', props.filters),
        {},
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
};

// Wizard configurations for Popup
const openCreate = () => {
    popUpStore.open({
        title: 'Tambah Produk',
        size: 'xl',
        component: CreateEditWrapper,
        props: {
            initialStep: 0,
            editMode: false,
            targetStepId: 'basic',
            categories:
                page.props.rawCategories ||
                page.props.categories ||
                props.categories ||
                [],
            outlets: page.props.outlets || [],
            uoms: page.props.uoms || [],
        },
    });
};

const openEdit = (row, targetStepId = 'basic') => {
    const stepIndexMap = { basic: 0, inventory: 1, pricing: 2 };
    popUpStore.open({
        title: `${row.name}`,
        subTitle: `#${row.code}`,
        size: 'xl',
        component: CreateEditWrapper,
        props: {
            initialStep: stepIndexMap[targetStepId] ?? 0,
            editMode: true,
            targetStepId,
            product: row,
            categories:
                page.props.rawCategories ||
                page.props.categories ||
                props.categories ||
                [],
            outlets: page.props.outlets || [],
            uoms: page.props.uoms || [],
        },
    });
};
</script>
