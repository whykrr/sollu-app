<template>
  <MainPage>
    <template #header>
      <MainPageHeader title="Data Bahan Baku">
        <button
          class="btn btn-flat btn-sm"
          @click="exportCsv"
        >
          <FontAwesomeIcon :icon="faDownload" />
          Ekspor CSV
        </button>
        <button
          class="btn btn-flat btn-sm"
          @click="showImportModal = true"
        >
          <FontAwesomeIcon :icon="faUpload" />
          Impor CSV
        </button>
        <button
          class="btn btn-highlight-main"
          @click="openForm()"
        >
          <FontAwesomeIcon :icon="faPlus" />
          Tambah Baru
        </button>
      </MainPageHeader>
      <RawMaterialFilter :filters="filters" />
    </template>

    <Table
      :headers="headers"
      :data="rawMaterials.data"
      :sort="filters.sort"
      :sort-direction="filters.direction"
      :action="true"
    >
      <template #uom="{ item }">
        {{ item.uom?.name || '-' }}
      </template>
      <template #track_inventory="{ item }">
        <span
          class="badge"
          :class="
            item.track_inventory ? 'badge-success' : 'badge-danger'
          "
        >
          {{ item.track_inventory ? 'Ya' : 'Tidak' }}
        </span>
      </template>
      <template #is_active="{ item }">
        <span
          class="badge"
          :class="item.is_active ? 'badge-success' : 'badge-danger'"
        >
          {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
        </span>
      </template>
      <template #actions="{ item }">
        <div class="flex items-center gap-2">
          <button
            class="btn btn-flat btn-sm"
            @click="openForm(item)"
          >
            <FontAwesomeIcon :icon="faPencil" />
          </button>
          <button
            class="btn btn-flat btn-sm text-danger"
            @click="confirmDelete(item)"
          >
            <FontAwesomeIcon :icon="faTrash" />
          </button>
        </div>
      </template>
    </Table>

    <template #footer>
      <Pagination
        :links="rawMaterials.links"
        :from="rawMaterials.from"
        :to="rawMaterials.to"
        :total="rawMaterials.total"
      />
    </template>

    <ImportCsvModal
      :show="showImportModal"
      module-name="Bahan Baku"
      :template-url="route('inventory.raw-materials.importTemplate')"
      :import-url="route('inventory.raw-materials.import')"
      @close="showImportModal = false"
    />
  </MainPage>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import {
    faPlus,
    faUpload,
    faPencil,
    faTrash,
    faDownload,
} from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import Table from '@/Components/Tables/Table.vue'
import Pagination from '@/Components/Tables/Pagination.vue'
import RawMaterialFilter from './Components/RawMaterialFilter.vue'
import RawMaterialForm from './Components/Form.vue'
import ImportCsvModal from '@/Components/Modals/ImportCsvModal.vue'
import { useModalStore } from '@/store/notification'
import { usePopUpStore } from '@/store/popup'

const modalStore = useModalStore()
const popUpStore = usePopUpStore()

const props = defineProps({
    rawMaterials: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    uoms: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
})

const headers = [
    { label: 'Nama', field: 'name', sortable: true },
    { label: 'SKU', field: 'sku', sortable: true },
    { label: 'Barcode', field: 'barcode', sortable: true },
    { label: 'Satuan', field: 'uom', slot: 'uom', sortable: false },
    {
        label: 'Lacak Stok',
        field: 'track_inventory',
        slot: 'track_inventory',
        sortable: false,
    },
    { label: 'Status', field: 'is_active', slot: 'is_active', sortable: false },
]

const showImportModal = ref(false)

const exportCsv = () => {
    router.get(
        route('inventory.raw-materials.export', props.filters),
        {},
        {
            preserveScroll: true,
            preserveState: true,
        }
    )
}

const openForm = (item = null) => {
    popUpStore.open({
        title: item ? 'Edit Bahan Baku' : 'Tambah Bahan Baku Baru',
        subTitle: item ? item.sku || item.name : null,
        size: 'md',
        component: RawMaterialForm,
        props: {
            rawMaterial: item,
            uoms: props.uoms,
        },
    })
}

const confirmDelete = (item) => {
    modalStore.openModalDelete(
        route('inventory.raw-materials.destroy', item.id)
    )
}
</script>
