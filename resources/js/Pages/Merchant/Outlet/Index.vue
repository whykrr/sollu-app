<template>
    <Container>
        <template #widgets>
            <Widgets />
        </template>

        <template #header>
            <div>
                <Filter :filters="params" :roles />
            </div>
            <div>
                <button class="btn btn-outline-main btn-sm" @click="addOutlet">
                    <FontAwesomeIcon :icon="faPlus" />
                    Outlet
                </button>
            </div>
        </template>
        <Table
            :headers="tableSetting"
            :data="outlets.data"
            :sort="params.sort ?? 'updated_at'"
            :sort-direction="params.direction ?? 'desc'"
            @row-click="goDetail"
        >
            <template #updated_at="{ row }">
                {{ formatDateTimeID(row.created_at) }}
            </template>
            <template #status="{ row }">
                <label
                    v-if="row.is_active"
                    class="badge pill text-sm badge-success"
                    >Aktif</label
                >
                <label v-else class="badge pill text-sm badge-danger"
                    >Tidak Aktif</label
                >
            </template>
        </Table>
        <template #footer>
            <Pagination
                :links="outlets.links"
                :from="outlets.from"
                :to="outlets.to"
                :total="outlets.total"
                :per-page="outlets.per_page ?? 20"
            />
        </template>
    </Container>
    <Modal
        :class="{
            show: modalTrial,
        }"
        title="Masa uji Coba Gratis"
        @close="modalTrial = false"
    >
        <div class="text-gray-600 text-sm">
            <div class="font-semibold">
                Fitur tambah outlet belum tersedia di Free Trial.
            </div>
            Langganan sekarang untuk membuka akses multi-outlet dan kelola semua
            cabang bisnis Anda dalam satu aplikasi.
        </div>
        <template #footer>
            <button class="btn btn-danger" @click="modalTrial = false">
                Tutup
            </button>
            <Link
                class="btn btn-highlight-success ml-2"
                :href="route('merchant.billing.index')"
            >
                Langganan Sekarang
            </Link>
        </template>
    </Modal>
    <Modal
        :class="{
            show: modalUpgrade,
        }"
        title="Tingkatkan Langganan"
        @close="modalUpgrade = false"
    >
        <div class="text-gray-600 text-sm">
            <div class="font-semibold">
                Paket langganan anda hanya mendukung 1 outlet.
            </div>
            Untuk menambah outlet baru, silakan upgrade ke paket yang lebih
            tinggi.
        </div>
        <template #footer>
            <button class="btn btn-success" @click="modalUpgrade = false">
                Oke
            </button>
            <Link
                class="btn btn-highlight-info ml-2"
                :href="route('merchant.billing.index')"
            >
                Ubah Langganan
            </Link>
        </template>
    </Modal>
</template>
<script setup>
import Pagination from '@/Components/Tables/Pagination.vue';
import Table from '@/Components/Tables/Table.vue';
import Container from '@/Components/UI/Container.vue';
import { formatDateTimeID } from '@/Composable/date';
import Filter from './Components/Filter.vue';
import { faPlus } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import Widgets from './Components/Widgets.vue';
import { ref } from 'vue';
import Modal from '@/Components/Notifications/Modal.vue';

const props = defineProps({
    outlets: Array,
    params: Object,
});

const modalTrial = ref(false);
const modalUpgrade = ref(false);

const tableSetting = [
    { field: 'name', label: 'Name', sortable: true },
    { field: 'address', label: 'Alamat' },
    {
        field: 'updated_at',
        label: 'Berubahan Terakhir',
        slot: 'updated_at',
        sortable: true,
    },
    { field: 'is_active', label: 'Status', slot: 'status' },
];

const auth = usePage().props.auth;

const addOutlet = () => {
    if (auth.subscription.plan.code === 'trial') {
        modalTrial.value = true;
    } else if (auth.subscription.plan.code === 'micro' && auth.outlet) {
        modalTrial.value = true;
    } else {
        router.visit(route('merchant.outlets.create'));
    }
};
</script>
