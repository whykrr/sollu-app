<template>
    <div class="flex flex-col gap-4">
        <Card title="Base Card">
            Lorem ipsum dolor sit, amet consectetur adipisicing elit. Culpa,
            accusamus amet. Unde culpa ad, expedita rem necessitatibus atque! Ex
            pariatur natus laboriosam sequi quisquam eos commodi modi enim ut
            dicta?
        </Card>
        <Card title="Card with Buttons">
            <template #buttons>
                <button class="btn btn-main btn-sm">
                    <fa icon="fa-plus" />
                    Create
                </button>
                <button class="btn btn-warning btn-sm">
                    <fa icon="fa-file" />
                    Draft
                </button>
                <button class="btn btn-danger btn-sm">
                    <fa icon="fa-trash" />
                    Trash
                </button>
            </template>
            <p>
                Lorem ipsum dolor sit, amet consectetur adipisicing elit. Culpa,
                accusamus amet. Unde culpa ad, expedita rem necessitatibus
                atque! Ex pariatur natus laboriosam sequi quisquam eos commodi
                modi enim ut dicta?
            </p>
        </Card>
        <Card title="Card with Outline" class="card-outline">
            <p>
                Lorem ipsum dolor sit, amet consectetur adipisicing elit. Culpa,
                accusamus amet. Unde culpa ad, expedita rem necessitatibus
                atque! Ex pariatur natus laboriosam sequi quisquam eos commodi
                modi enim ut dicta?
            </p>
        </Card>
        <Card title="Card Styles">
            <div class="grid grid-cols-4 gap-2">
                <Card
                    title="Card Background Main Color"
                    class="bg-main text-white"
                >
                    Lorem ipsum dolor sit amet consectetur adipisicing elit.
                    Enim et, quo rerum optio nihil corrupti repellendus totam
                    ullam, consectetur ipsam explicabo a tempore impedit iure
                    voluptate unde quas iste ratione!
                </Card>
                <Card
                    title="Card Background Secondary Color"
                    class="bg-secondary text-white"
                >
                    Lorem ipsum dolor sit amet consectetur adipisicing elit.
                    Enim et, quo rerum optio nihil corrupti repellendus totam
                    ullam, consectetur ipsam explicabo a tempore impedit iure
                    voluptate unde quas iste ratione!
                </Card>
                <Card title="Card Background Teal" class="bg-teal text-white">
                    Lorem ipsum dolor sit amet consectetur adipisicing elit.
                    Enim et, quo rerum optio nihil corrupti repellendus totam
                    ullam, consectetur ipsam explicabo a tempore impedit iure
                    voluptate unde quas iste ratione!
                </Card>
                <Card
                    title="Card Background Danger"
                    class="bg-danger text-neutral"
                >
                    Lorem ipsum dolor sit amet consectetur adipisicing elit.
                    Enim et, quo rerum optio nihil corrupti repellendus totam
                    ullam, consectetur ipsam explicabo a tempore impedit iure
                    voluptate unde quas iste ratione!
                </Card>
            </div>
        </Card>

        <Card title="Card Images">
            <div class="grid grid-cols-4 gap-2">
                <Card
                    title="Card Image"
                    class="card-outline"
                    image="https://via.placeholder.com/400x200"
                >
                    Lorem ipsum dolor sit amet consectetur adipisicing elit.
                </Card>
            </div>
        </Card>
    </div>
</template>

<script setup>
import Card from "@/Components/UI/Card.vue";
</script>
