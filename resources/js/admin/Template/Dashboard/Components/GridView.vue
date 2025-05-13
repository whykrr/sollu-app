<template>
    <div class="grid grid-cols-2 gap-4">
        <div
            class="flex flex-col border-2 border-light rounded-md"
            v-for="i in data"
            :id="i.id"
        >
            <div class="text-center p-4">Image</div>
            <div class="flex flex-row px-2 pb-4 gap-2 justify-between">
                <div>
                    <span class="text-lg font-bold">{{ i.name }}</span>
                    <div class="text-xs text-muted">
                        <div>{{ i.class }}</div>
                        <div>{{ i.class }}</div>
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                    <button class="btn btn-info text-sm">
                        <fa icon="fa-search" />
                        Details
                    </button>
                    <button class="btn btn-danger text-sm">
                        <fa icon="fa-trash" />
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
const data = [
    {
        id: 1,
        name: "Morris Johnson",
        class: "Class 9",
        subject: "English",
        email: "example@site.com",
        address: "Samantha Street 27, District John",
        gender: "Male",
    },
    {
        id: 2,
        name: "Morris Johnson",
        class: "Class 2",
        subject: "Korea",
        email: "example@site.com",
        address: "Samantha Street 27, District John",
        gender: "Male",
    },
    {
        id: 3,
        name: "Morris Johnson",
        class: "Class 5",
        subject: "Thailand",
        email: "example@site.com",
        address: "Samantha Street 27, District John",
        gender: "Male",
    },
    {
        id: 4,
        name: "Morris Johnson",
        class: "Class 7",
        subject: "Japan",
        email: "example@site.com",
        address: "Samantha Street 27, District John",
        gender: "Male",
    },
    {
        id: 5,
        name: "Morris Johnson",
        class: "Class 10",
        subject: "Zimbabwe",
        email: "example@site.com",
        address: "Samantha Street 27, District John",
        gender: "Male",
    },
];
</script>
