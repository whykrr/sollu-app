<template>
    <div class="table-responsive">
        <table class="table table-hovered">
            <thead class="text-left">
                <tr>
                    <td>Name</td>
                    <td>Class</td>
                    <td>Status</td>
                    <td>Status 2</td>
                    <td>Subject</td>
                    <td>Email</td>
                    <td>Address</td>
                    <td>Gender</td>
                    <td>Action</td>
                </tr>
            </thead>
            <tbody>
                <tr class="h-2"></tr>
                <tr
                    v-for="i in data"
                    @click="router.get(route('dashboard.admin.base.tables'))"
                >
                    <td class="font-bold">{{ i.name }}</td>
                    <td class="text-gray">{{ i.class }}</td>
                    <td>
                        <span
                            v-if="i.isActive"
                            class="badge pill badge-success"
                        >
                            Active
                        </span>
                        <span v-else class="badge pill badge-danger"
                            >Active</span
                        >
                    </td>
                    <td>
                        <span
                            v-if="i.isActive"
                            class="badge-doted badge-success"
                            >Active</span
                        >
                        <span v-else class="badge-doted badge-danger"
                            >Not Active</span
                        >
                    </td>
                    <td class="text-gray">{{ i.subject }}</td>
                    <td class="font-bold">{{ i.email }}</td>
                    <td class="text-gray">{{ i.address }}</td>

                    <td class="text-gray">{{ i.gender }}</td>
                    <td>
                        <button class="btn btn-danger btn-sm">
                            <fa icon="fa-trash" />
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { router } from "@inertiajs/vue3";

const data = [
    {
        id: 1,
        name: "Morris Johnson",
        class: "Class 9",
        subject: "English",
        email: "example@site.com",
        address: "Samantha Street 27, District John",
        gender: "Male",
        isActive: true,
    },
    {
        id: 2,
        name: "Morris Johnson",
        class: "Class 2",
        subject: "Korea",
        email: "example@site.com",
        address: "Samantha Street 27, District John",
        gender: "Male",
        isActive: true,
    },
    {
        id: 3,
        name: "Morris Johnson",
        class: "Class 5",
        subject: "Thailand",
        email: "example@site.com",
        address: "Samantha Street 27, District John",
        gender: "Male",
        isActive: true,
    },
    {
        id: 4,
        name: "Morris Johnson",
        class: "Class 7",
        subject: "Japan",
        email: "example@site.com",
        address: "Samantha Street 27, District John",
        gender: "Male",
        isActive: true,
    },
    {
        id: 5,
        name: "Morris Johnson",
        class: "Class 10",
        subject: "Zimbabwe",
        email: "example@site.com",
        address: "Samantha Street 27, District John",
        gender: "Male",
        isActive: false,
    },
];
</script>
