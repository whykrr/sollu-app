<template>
    <Card title="Table">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <td>Name</td>
                        <td>Class</td>
                        <td>Subject</td>
                        <td>Email</td>
                        <td>Address</td>
                        <td>Gender</td>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="i in dataTable" class="text-nowrap" :key="i.id">
                        <td class="font-bold">{{ i.name }}</td>
                        <td class="text-gray">{{ i.class }}</td>
                        <td class="text-gray">{{ i.subject }}</td>
                        <td class="font-bold">{{ i.email }}</td>
                        <td class="text-gray">{{ i.address }}</td>
                        <td class="text-gray">{{ i.gender }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </Card>
    <Card title="Table Hovered" class="mt-4">
        <div class="table-responsive">
            <table class="table table-hovered">
                <thead>
                    <tr>
                        <td>Name</td>
                        <td>Class</td>
                        <td>Subject</td>
                        <td>Email</td>
                        <td>Address</td>
                        <td>Gender</td>
                    </tr>
                </thead>
                <tbody>
                    <tr class="h-2"></tr>
                    <tr v-for="i in dataTable" class="text-nowrap" :key="i.id">
                        <td class="font-bold">{{ i.name }}</td>
                        <td class="text-gray">{{ i.class }}</td>
                        <td class="text-gray">{{ i.subject }}</td>
                        <td class="font-bold">{{ i.email }}</td>
                        <td class="text-gray">{{ i.address }}</td>
                        <td class="text-gray">{{ i.gender }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </Card>
</template>
<script setup>
import Card from "@/Components/UI/Card.vue";
const dataTable = [
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
