<template>
    <Container>
        <template #header>
            <ContainerHeader title="Transfer Stok">
                <button class="btn btn-highlight-main" @click="openForm()">
                    <FontAwesomeIcon :icon="faPlus" />
                    Buat Transfer
                </button>
            </ContainerHeader>
        </template>
        
        <Table 
            :headers="headers" 
            :data="transfers.data"
            :action="true"
        >
            <template #status="{ item }">
                <span class="badge" :class="statusColor(item.status)">
                    {{ statusLabel(item.status) }}
                </span>
            </template>
            <template #actions="{ item }">
                <div class="flex items-center gap-2">
                    <button v-if="item.status === 'pending'" class="btn btn-info btn-sm" @click="openAction(item)" title="Setujui Transfer">
                        <FontAwesomeIcon :icon="faCheck" /> Approve
                    </button>
                    <button v-if="item.status === 'in_transit'" class="btn btn-success btn-sm" @click="openAction(item)" title="Terima Transfer">
                        <FontAwesomeIcon :icon="faBoxOpen" /> Terima
                    </button>
                    <button v-if="item.status === 'pending'" class="btn btn-flat btn-sm text-danger" @click="confirmDelete(item)" title="Batalkan">
                        <FontAwesomeIcon :icon="faTimes" />
                    </button>
                </div>
            </template>
        </Table>

        <template #footer>
            <Pagination 
                :links="transfers.links" 
                :from="transfers.from" 
                :to="transfers.to" 
                :total="transfers.total" 
            />
        </template>

        <Form 
            :show="showForm" 
            :outlets="outlets"
            :items="items"
            @close="closeForm" 
        />
        
        <Receive
            :show="showAction"
            :transfer="selectedItem"
            @close="closeAction"
        />
    </Container>
</template>

<script setup>
import { ref } from 'vue';
import { faPlus, faTimes, faCheck, faBoxOpen } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import Container from '@/Components/UI/Container.vue';
import ContainerHeader from '@/Components/UI/Container/ContainerHeader.vue';
import Table from '@/Components/Tables/Table.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import Form from './Components/Form.vue';
import Receive from './Components/Receive.vue';
import { useModalStore } from '@/store/notification';

const modalStore = useModalStore();

const props = defineProps({
    transfers: {
        type: Object,
        default: () => ({ data: [], links: [] })
    },
    outlets: {
        type: Array,
        default: () => []
    },
    items: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({})
    }
});

const headers = [
    { label: 'Nomor Transfer', field: 'transfer_number', sortable: true },
    { label: 'Tanggal', field: 'created_at', slot: 'created_at', sortable: true },
    { label: 'Dari Outlet', field: 'from_outlet.name', sortable: false },
    { label: 'Ke Outlet', field: 'to_outlet.name', sortable: false },
    { label: 'Status', field: 'status', slot: 'status', sortable: false },
];

const showForm = ref(false);
const showAction = ref(false);
const selectedItem = ref(null);

const statusLabel = (status) => {
    const labels = {
        'pending': 'Menunggu Persetujuan',
        'approved': 'Disetujui',
        'in_transit': 'Dalam Perjalanan',
        'completed': 'Selesai',
        'rejected': 'Ditolak',
    };
    return labels[status] || status;
};

const statusColor = (status) => {
    const colors = {
        'pending': 'badge-warning',
        'approved': 'badge-info',
        'in_transit': 'badge-info',
        'completed': 'badge-success',
        'rejected': 'badge-danger',
    };
    return colors[status] || 'badge-gray';
};

const openForm = () => {
    showForm.value = true;
};

const closeForm = () => {
    showForm.value = false;
};

const openAction = (item) => {
    selectedItem.value = item;
    showAction.value = true;
};

const closeAction = () => {
    showAction.value = false;
    selectedItem.value = null;
};

const confirmDelete = (item) => {
    modalStore.openModalDelete(route('inventory.transfers.destroy', item.id));
};
</script>
