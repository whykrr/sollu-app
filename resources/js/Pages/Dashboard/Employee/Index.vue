<template>
    <Container>
        <ContainerHeader>
            <template #left>
                <Filter :filters="filters" />
            </template>
            <template #right>
                <Link
                    :href="route('dashboard.employees.create')"
                    class="btn btn-main text-sm"
                >
                    <fa icon="fa-plus" />
                    Tambah Karyawan
                </Link>
            </template>
        </ContainerHeader>
        <ContainerBody>
            <Table :data="data" />
        </ContainerBody>
        <ContainerFooter>
            <Pagination
                :links="users.links"
                :from="users.from"
                :to="users.to"
                :total="users.to"
            />
        </ContainerFooter>
    </Container>
</template>

<script setup>
import CardTransparent from "@/Components/Dashboard/Cards/CardTransparent.vue";
import Pagination from "@/Components/Dashboard/Tables/Pagination.vue";
import Table from "@/Pages/Dashboard/Employee/Components/Table.vue";
import Filter from "@/Pages/Dashboard/Employee/Components/Filter.vue";
import { Link } from "@inertiajs/vue3";
import Container from "@/Components/Dashboard/Container/Container.vue";
import ContainerBody from "@/Components/Dashboard/Container/ContainerBody.vue";
import ContainerHeader from "@/Components/Dashboard/Container/ContainerHeader.vue";
import ContainerFooter from "@/Components/Dashboard/Container/ContainerFooter.vue";

defineProps({
    users: Object,
    filters: Object,
});

const data = [
    {
        id: "9f876592-e601-4901-bcd7-ae6681df3c78",
        merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
        name: "Wahyu Kristiawan",
        email: "sollu.mart@email.com",
        phone: "082132538886",
        email_verified_at: "2025-08-01 15:58:54",
        photo: null,
        is_root_user: true,
        created_at: "01/08/2025 15.58",
        updated_at: "01/08/2025 15.58",
        deleted_at: null,
        roles: [
            {
                label: "Owner",
                pivot: {
                    model_type: "App\\Models\\User",
                    model_id: "9f876592-e601-4901-bcd7-ae6681df3c78",
                    role_id: 1,
                },
            },
        ],
        outlets: [],
    },
    {
        id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
        merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
        name: "Manager Outlet",
        email: "manager.sollu.mart@email.com",
        phone: "0821325388869",
        email_verified_at: "2025-08-01 15:58:55",
        photo: null,
        is_root_user: false,
        created_at: "01/08/2025 15.58",
        updated_at: "01/08/2025 15.58",
        deleted_at: null,
        roles: [
            {
                label: "Manager",
                pivot: {
                    model_type: "App\\Models\\User",
                    model_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    role_id: 2,
                },
            },
        ],
        outlets: [
            {
                id: "9f876591-86fb-4571-8854-d4203c5915c4",
                merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
                slug: "sollu-mart-pusat",
                name: "Sollu Mart Pusat",
                address: "",
                latitude: null,
                longitude: null,
                status: "active",
                expired_at: "2025-07-15",
                is_main_outlet: true,
                created_at: "2025-08-01T08:58:54.000000Z",
                updated_at: "2025-08-01T08:58:54.000000Z",
                pivot: {
                    user_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    outlet_id: "9f876591-86fb-4571-8854-d4203c5915c4",
                },
            },
        ],
    },
    {
        id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
        merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
        name: "Manager Outlet",
        email: "manager.sollu.mart@email.com",
        phone: "0821325388869",
        email_verified_at: "2025-08-01 15:58:55",
        photo: null,
        is_root_user: false,
        created_at: "01/08/2025 15.58",
        updated_at: "01/08/2025 15.58",
        deleted_at: null,
        roles: [
            {
                label: "Manager",
                pivot: {
                    model_type: "App\\Models\\User",
                    model_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    role_id: 2,
                },
            },
        ],
        outlets: [
            {
                id: "9f876591-86fb-4571-8854-d4203c5915c4",
                merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
                slug: "sollu-mart-pusat",
                name: "Sollu Mart Pusat",
                address: "",
                latitude: null,
                longitude: null,
                status: "active",
                expired_at: "2025-07-15",
                is_main_outlet: true,
                created_at: "2025-08-01T08:58:54.000000Z",
                updated_at: "2025-08-01T08:58:54.000000Z",
                pivot: {
                    user_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    outlet_id: "9f876591-86fb-4571-8854-d4203c5915c4",
                },
            },
        ],
    },
    {
        id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
        merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
        name: "Manager Outlet",
        email: "manager.sollu.mart@email.com",
        phone: "0821325388869",
        email_verified_at: "2025-08-01 15:58:55",
        photo: null,
        is_root_user: false,
        created_at: "01/08/2025 15.58",
        updated_at: "01/08/2025 15.58",
        deleted_at: null,
        roles: [
            {
                label: "Manager",
                pivot: {
                    model_type: "App\\Models\\User",
                    model_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    role_id: 2,
                },
            },
        ],
        outlets: [
            {
                id: "9f876591-86fb-4571-8854-d4203c5915c4",
                merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
                slug: "sollu-mart-pusat",
                name: "Sollu Mart Pusat",
                address: "",
                latitude: null,
                longitude: null,
                status: "active",
                expired_at: "2025-07-15",
                is_main_outlet: true,
                created_at: "2025-08-01T08:58:54.000000Z",
                updated_at: "2025-08-01T08:58:54.000000Z",
                pivot: {
                    user_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    outlet_id: "9f876591-86fb-4571-8854-d4203c5915c4",
                },
            },
        ],
    },
    {
        id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
        merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
        name: "Manager Outlet",
        email: "manager.sollu.mart@email.com",
        phone: "0821325388869",
        email_verified_at: "2025-08-01 15:58:55",
        photo: null,
        is_root_user: false,
        created_at: "01/08/2025 15.58",
        updated_at: "01/08/2025 15.58",
        deleted_at: null,
        roles: [
            {
                label: "Manager",
                pivot: {
                    model_type: "App\\Models\\User",
                    model_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    role_id: 2,
                },
            },
        ],
        outlets: [
            {
                id: "9f876591-86fb-4571-8854-d4203c5915c4",
                merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
                slug: "sollu-mart-pusat",
                name: "Sollu Mart Pusat",
                address: "",
                latitude: null,
                longitude: null,
                status: "active",
                expired_at: "2025-07-15",
                is_main_outlet: true,
                created_at: "2025-08-01T08:58:54.000000Z",
                updated_at: "2025-08-01T08:58:54.000000Z",
                pivot: {
                    user_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    outlet_id: "9f876591-86fb-4571-8854-d4203c5915c4",
                },
            },
        ],
    },
    {
        id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
        merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
        name: "Manager Outlet",
        email: "manager.sollu.mart@email.com",
        phone: "0821325388869",
        email_verified_at: "2025-08-01 15:58:55",
        photo: null,
        is_root_user: false,
        created_at: "01/08/2025 15.58",
        updated_at: "01/08/2025 15.58",
        deleted_at: null,
        roles: [
            {
                label: "Manager",
                pivot: {
                    model_type: "App\\Models\\User",
                    model_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    role_id: 2,
                },
            },
        ],
        outlets: [
            {
                id: "9f876591-86fb-4571-8854-d4203c5915c4",
                merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
                slug: "sollu-mart-pusat",
                name: "Sollu Mart Pusat",
                address: "",
                latitude: null,
                longitude: null,
                status: "active",
                expired_at: "2025-07-15",
                is_main_outlet: true,
                created_at: "2025-08-01T08:58:54.000000Z",
                updated_at: "2025-08-01T08:58:54.000000Z",
                pivot: {
                    user_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    outlet_id: "9f876591-86fb-4571-8854-d4203c5915c4",
                },
            },
        ],
    },
    {
        id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
        merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
        name: "Manager Outlet",
        email: "manager.sollu.mart@email.com",
        phone: "0821325388869",
        email_verified_at: "2025-08-01 15:58:55",
        photo: null,
        is_root_user: false,
        created_at: "01/08/2025 15.58",
        updated_at: "01/08/2025 15.58",
        deleted_at: null,
        roles: [
            {
                label: "Manager",
                pivot: {
                    model_type: "App\\Models\\User",
                    model_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    role_id: 2,
                },
            },
        ],
        outlets: [
            {
                id: "9f876591-86fb-4571-8854-d4203c5915c4",
                merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
                slug: "sollu-mart-pusat",
                name: "Sollu Mart Pusat",
                address: "",
                latitude: null,
                longitude: null,
                status: "active",
                expired_at: "2025-07-15",
                is_main_outlet: true,
                created_at: "2025-08-01T08:58:54.000000Z",
                updated_at: "2025-08-01T08:58:54.000000Z",
                pivot: {
                    user_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    outlet_id: "9f876591-86fb-4571-8854-d4203c5915c4",
                },
            },
        ],
    },
    {
        id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
        merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
        name: "Manager Outlet",
        email: "manager.sollu.mart@email.com",
        phone: "0821325388869",
        email_verified_at: "2025-08-01 15:58:55",
        photo: null,
        is_root_user: false,
        created_at: "01/08/2025 15.58",
        updated_at: "01/08/2025 15.58",
        deleted_at: null,
        roles: [
            {
                label: "Manager",
                pivot: {
                    model_type: "App\\Models\\User",
                    model_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    role_id: 2,
                },
            },
        ],
        outlets: [
            {
                id: "9f876591-86fb-4571-8854-d4203c5915c4",
                merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
                slug: "sollu-mart-pusat",
                name: "Sollu Mart Pusat",
                address: "",
                latitude: null,
                longitude: null,
                status: "active",
                expired_at: "2025-07-15",
                is_main_outlet: true,
                created_at: "2025-08-01T08:58:54.000000Z",
                updated_at: "2025-08-01T08:58:54.000000Z",
                pivot: {
                    user_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    outlet_id: "9f876591-86fb-4571-8854-d4203c5915c4",
                },
            },
        ],
    },
    {
        id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
        merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
        name: "Manager Outlet",
        email: "manager.sollu.mart@email.com",
        phone: "0821325388869",
        email_verified_at: "2025-08-01 15:58:55",
        photo: null,
        is_root_user: false,
        created_at: "01/08/2025 15.58",
        updated_at: "01/08/2025 15.58",
        deleted_at: null,
        roles: [
            {
                label: "Manager",
                pivot: {
                    model_type: "App\\Models\\User",
                    model_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    role_id: 2,
                },
            },
        ],
        outlets: [
            {
                id: "9f876591-86fb-4571-8854-d4203c5915c4",
                merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
                slug: "sollu-mart-pusat",
                name: "Sollu Mart Pusat",
                address: "",
                latitude: null,
                longitude: null,
                status: "active",
                expired_at: "2025-07-15",
                is_main_outlet: true,
                created_at: "2025-08-01T08:58:54.000000Z",
                updated_at: "2025-08-01T08:58:54.000000Z",
                pivot: {
                    user_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    outlet_id: "9f876591-86fb-4571-8854-d4203c5915c4",
                },
            },
        ],
    },
    {
        id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
        merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
        name: "Manager Outlet",
        email: "manager.sollu.mart@email.com",
        phone: "0821325388869",
        email_verified_at: "2025-08-01 15:58:55",
        photo: null,
        is_root_user: false,
        created_at: "01/08/2025 15.58",
        updated_at: "01/08/2025 15.58",
        deleted_at: null,
        roles: [
            {
                label: "Manager",
                pivot: {
                    model_type: "App\\Models\\User",
                    model_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    role_id: 2,
                },
            },
        ],
        outlets: [
            {
                id: "9f876591-86fb-4571-8854-d4203c5915c4",
                merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
                slug: "sollu-mart-pusat",
                name: "Sollu Mart Pusat",
                address: "",
                latitude: null,
                longitude: null,
                status: "active",
                expired_at: "2025-07-15",
                is_main_outlet: true,
                created_at: "2025-08-01T08:58:54.000000Z",
                updated_at: "2025-08-01T08:58:54.000000Z",
                pivot: {
                    user_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    outlet_id: "9f876591-86fb-4571-8854-d4203c5915c4",
                },
            },
        ],
    },
    {
        id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
        merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
        name: "Manager Outlet",
        email: "manager.sollu.mart@email.com",
        phone: "0821325388869",
        email_verified_at: "2025-08-01 15:58:55",
        photo: null,
        is_root_user: false,
        created_at: "01/08/2025 15.58",
        updated_at: "01/08/2025 15.58",
        deleted_at: null,
        roles: [
            {
                label: "Manager",
                pivot: {
                    model_type: "App\\Models\\User",
                    model_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    role_id: 2,
                },
            },
        ],
        outlets: [
            {
                id: "9f876591-86fb-4571-8854-d4203c5915c4",
                merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
                slug: "sollu-mart-pusat",
                name: "Sollu Mart Pusat",
                address: "",
                latitude: null,
                longitude: null,
                status: "active",
                expired_at: "2025-07-15",
                is_main_outlet: true,
                created_at: "2025-08-01T08:58:54.000000Z",
                updated_at: "2025-08-01T08:58:54.000000Z",
                pivot: {
                    user_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    outlet_id: "9f876591-86fb-4571-8854-d4203c5915c4",
                },
            },
        ],
    },
    {
        id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
        merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
        name: "Manager Outlet",
        email: "manager.sollu.mart@email.com",
        phone: "0821325388869",
        email_verified_at: "2025-08-01 15:58:55",
        photo: null,
        is_root_user: false,
        created_at: "01/08/2025 15.58",
        updated_at: "01/08/2025 15.58",
        deleted_at: null,
        roles: [
            {
                label: "Manager",
                pivot: {
                    model_type: "App\\Models\\User",
                    model_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    role_id: 2,
                },
            },
        ],
        outlets: [
            {
                id: "9f876591-86fb-4571-8854-d4203c5915c4",
                merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
                slug: "sollu-mart-pusat",
                name: "Sollu Mart Pusat",
                address: "",
                latitude: null,
                longitude: null,
                status: "active",
                expired_at: "2025-07-15",
                is_main_outlet: true,
                created_at: "2025-08-01T08:58:54.000000Z",
                updated_at: "2025-08-01T08:58:54.000000Z",
                pivot: {
                    user_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    outlet_id: "9f876591-86fb-4571-8854-d4203c5915c4",
                },
            },
        ],
    },
    {
        id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
        merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
        name: "Manager Outlet",
        email: "manager.sollu.mart@email.com",
        phone: "0821325388869",
        email_verified_at: "2025-08-01 15:58:55",
        photo: null,
        is_root_user: false,
        created_at: "01/08/2025 15.58",
        updated_at: "01/08/2025 15.58",
        deleted_at: null,
        roles: [
            {
                label: "Manager",
                pivot: {
                    model_type: "App\\Models\\User",
                    model_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    role_id: 2,
                },
            },
        ],
        outlets: [
            {
                id: "9f876591-86fb-4571-8854-d4203c5915c4",
                merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
                slug: "sollu-mart-pusat",
                name: "Sollu Mart Pusat",
                address: "",
                latitude: null,
                longitude: null,
                status: "active",
                expired_at: "2025-07-15",
                is_main_outlet: true,
                created_at: "2025-08-01T08:58:54.000000Z",
                updated_at: "2025-08-01T08:58:54.000000Z",
                pivot: {
                    user_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    outlet_id: "9f876591-86fb-4571-8854-d4203c5915c4",
                },
            },
        ],
    },
    {
        id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
        merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
        name: "Manager Outlet",
        email: "manager.sollu.mart@email.com",
        phone: "0821325388869",
        email_verified_at: "2025-08-01 15:58:55",
        photo: null,
        is_root_user: false,
        created_at: "01/08/2025 15.58",
        updated_at: "01/08/2025 15.58",
        deleted_at: null,
        roles: [
            {
                label: "Manager",
                pivot: {
                    model_type: "App\\Models\\User",
                    model_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    role_id: 2,
                },
            },
        ],
        outlets: [
            {
                id: "9f876591-86fb-4571-8854-d4203c5915c4",
                merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
                slug: "sollu-mart-pusat",
                name: "Sollu Mart Pusat",
                address: "",
                latitude: null,
                longitude: null,
                status: "active",
                expired_at: "2025-07-15",
                is_main_outlet: true,
                created_at: "2025-08-01T08:58:54.000000Z",
                updated_at: "2025-08-01T08:58:54.000000Z",
                pivot: {
                    user_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    outlet_id: "9f876591-86fb-4571-8854-d4203c5915c4",
                },
            },
        ],
    },
    {
        id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
        merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
        name: "Manager Outlet",
        email: "manager.sollu.mart@email.com",
        phone: "0821325388869",
        email_verified_at: "2025-08-01 15:58:55",
        photo: null,
        is_root_user: false,
        created_at: "01/08/2025 15.58",
        updated_at: "01/08/2025 15.58",
        deleted_at: null,
        roles: [
            {
                label: "Manager",
                pivot: {
                    model_type: "App\\Models\\User",
                    model_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    role_id: 2,
                },
            },
        ],
        outlets: [
            {
                id: "9f876591-86fb-4571-8854-d4203c5915c4",
                merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
                slug: "sollu-mart-pusat",
                name: "Sollu Mart Pusat",
                address: "",
                latitude: null,
                longitude: null,
                status: "active",
                expired_at: "2025-07-15",
                is_main_outlet: true,
                created_at: "2025-08-01T08:58:54.000000Z",
                updated_at: "2025-08-01T08:58:54.000000Z",
                pivot: {
                    user_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    outlet_id: "9f876591-86fb-4571-8854-d4203c5915c4",
                },
            },
        ],
    },
    {
        id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
        merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
        name: "Manager Outlet",
        email: "manager.sollu.mart@email.com",
        phone: "0821325388869",
        email_verified_at: "2025-08-01 15:58:55",
        photo: null,
        is_root_user: false,
        created_at: "01/08/2025 15.58",
        updated_at: "01/08/2025 15.58",
        deleted_at: null,
        roles: [
            {
                label: "Manager",
                pivot: {
                    model_type: "App\\Models\\User",
                    model_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    role_id: 2,
                },
            },
        ],
        outlets: [
            {
                id: "9f876591-86fb-4571-8854-d4203c5915c4",
                merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
                slug: "sollu-mart-pusat",
                name: "Sollu Mart Pusat",
                address: "",
                latitude: null,
                longitude: null,
                status: "active",
                expired_at: "2025-07-15",
                is_main_outlet: true,
                created_at: "2025-08-01T08:58:54.000000Z",
                updated_at: "2025-08-01T08:58:54.000000Z",
                pivot: {
                    user_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    outlet_id: "9f876591-86fb-4571-8854-d4203c5915c4",
                },
            },
        ],
    },
    {
        id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
        merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
        name: "Manager Outlet",
        email: "manager.sollu.mart@email.com",
        phone: "0821325388869",
        email_verified_at: "2025-08-01 15:58:55",
        photo: null,
        is_root_user: false,
        created_at: "01/08/2025 15.58",
        updated_at: "01/08/2025 15.58",
        deleted_at: null,
        roles: [
            {
                label: "Manager",
                pivot: {
                    model_type: "App\\Models\\User",
                    model_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    role_id: 2,
                },
            },
        ],
        outlets: [
            {
                id: "9f876591-86fb-4571-8854-d4203c5915c4",
                merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
                slug: "sollu-mart-pusat",
                name: "Sollu Mart Pusat",
                address: "",
                latitude: null,
                longitude: null,
                status: "active",
                expired_at: "2025-07-15",
                is_main_outlet: true,
                created_at: "2025-08-01T08:58:54.000000Z",
                updated_at: "2025-08-01T08:58:54.000000Z",
                pivot: {
                    user_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    outlet_id: "9f876591-86fb-4571-8854-d4203c5915c4",
                },
            },
        ],
    },
    {
        id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
        merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
        name: "Manager Outlet",
        email: "manager.sollu.mart@email.com",
        phone: "0821325388869",
        email_verified_at: "2025-08-01 15:58:55",
        photo: null,
        is_root_user: false,
        created_at: "01/08/2025 15.58",
        updated_at: "01/08/2025 15.58",
        deleted_at: null,
        roles: [
            {
                label: "Manager",
                pivot: {
                    model_type: "App\\Models\\User",
                    model_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    role_id: 2,
                },
            },
        ],
        outlets: [
            {
                id: "9f876591-86fb-4571-8854-d4203c5915c4",
                merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
                slug: "sollu-mart-pusat",
                name: "Sollu Mart Pusat",
                address: "",
                latitude: null,
                longitude: null,
                status: "active",
                expired_at: "2025-07-15",
                is_main_outlet: true,
                created_at: "2025-08-01T08:58:54.000000Z",
                updated_at: "2025-08-01T08:58:54.000000Z",
                pivot: {
                    user_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    outlet_id: "9f876591-86fb-4571-8854-d4203c5915c4",
                },
            },
        ],
    },
    {
        id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
        merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
        name: "Manager Outlet",
        email: "manager.sollu.mart@email.com",
        phone: "0821325388869",
        email_verified_at: "2025-08-01 15:58:55",
        photo: null,
        is_root_user: false,
        created_at: "01/08/2025 15.58",
        updated_at: "01/08/2025 15.58",
        deleted_at: null,
        roles: [
            {
                label: "Manager",
                pivot: {
                    model_type: "App\\Models\\User",
                    model_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    role_id: 2,
                },
            },
        ],
        outlets: [
            {
                id: "9f876591-86fb-4571-8854-d4203c5915c4",
                merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
                slug: "sollu-mart-pusat",
                name: "Sollu Mart Pusat",
                address: "",
                latitude: null,
                longitude: null,
                status: "active",
                expired_at: "2025-07-15",
                is_main_outlet: true,
                created_at: "2025-08-01T08:58:54.000000Z",
                updated_at: "2025-08-01T08:58:54.000000Z",
                pivot: {
                    user_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    outlet_id: "9f876591-86fb-4571-8854-d4203c5915c4",
                },
            },
        ],
    },
    {
        id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
        merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
        name: "Manager Outlet",
        email: "manager.sollu.mart@email.com",
        phone: "0821325388869",
        email_verified_at: "2025-08-01 15:58:55",
        photo: null,
        is_root_user: false,
        created_at: "01/08/2025 15.58",
        updated_at: "01/08/2025 15.58",
        deleted_at: null,
        roles: [
            {
                label: "Manager",
                pivot: {
                    model_type: "App\\Models\\User",
                    model_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    role_id: 2,
                },
            },
        ],
        outlets: [
            {
                id: "9f876591-86fb-4571-8854-d4203c5915c4",
                merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
                slug: "sollu-mart-pusat",
                name: "Sollu Mart Pusat",
                address: "",
                latitude: null,
                longitude: null,
                status: "active",
                expired_at: "2025-07-15",
                is_main_outlet: true,
                created_at: "2025-08-01T08:58:54.000000Z",
                updated_at: "2025-08-01T08:58:54.000000Z",
                pivot: {
                    user_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    outlet_id: "9f876591-86fb-4571-8854-d4203c5915c4",
                },
            },
        ],
    },
    {
        id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
        merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
        name: "Manager Outlet",
        email: "manager.sollu.mart@email.com",
        phone: "0821325388869",
        email_verified_at: "2025-08-01 15:58:55",
        photo: null,
        is_root_user: false,
        created_at: "01/08/2025 15.58",
        updated_at: "01/08/2025 15.58",
        deleted_at: null,
        roles: [
            {
                label: "Manager",
                pivot: {
                    model_type: "App\\Models\\User",
                    model_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    role_id: 2,
                },
            },
        ],
        outlets: [
            {
                id: "9f876591-86fb-4571-8854-d4203c5915c4",
                merchant_id: "9f876591-7dbc-46d0-b8dc-6ffc945b3540",
                slug: "sollu-mart-pusat",
                name: "Sollu Mart Pusat",
                address: "",
                latitude: null,
                longitude: null,
                status: "active",
                expired_at: "2025-07-15",
                is_main_outlet: true,
                created_at: "2025-08-01T08:58:54.000000Z",
                updated_at: "2025-08-01T08:58:54.000000Z",
                pivot: {
                    user_id: "9f876594-1a6f-4813-9d8a-4e1384b18ba5",
                    outlet_id: "9f876591-86fb-4571-8854-d4203c5915c4",
                },
            },
        ],
    },
];
</script>
