<template>
    <div class="flex flex-col gap-4">
        <Card title="Basic Example">
            <label for="email_basic">Example Email</label>
            <input type="email" class="form mb-2" id="email_basic" />
            <label for="textarea_basic">Example Textarea</label>
            <textarea
                name="example"
                class="form"
                id="textarea_basic"
            ></textarea>
        </Card>

        <Card title="Form Size">
            <label for="example_small">Example Small</label>
            <input type="text" class="form-sm mb-2" id="example_small" />

            <label for="example_regular">Example Regular</label>
            <input type="text" class="form mb-2" id="example_regular" />

            <label for="example_large">Example Large</label>
            <input type="text" class="form-lg mb-2" id="example_large" />
        </Card>
        <Card title="Form Disabled">
            <label for="example_disabled">Form Disabled</label>
            <input
                type="text"
                class="form mb-2"
                id="example_disabled"
                disabled
                placeholder="Example..."
            />
        </Card>
        <Card title="Form Readonly">
            <label for="example_disabled">Form Readonly</label>
            <input
                type="text"
                class="form mb-2"
                id="example_disabled"
                readonly
                placeholder="Example..."
            />
        </Card>
        <Card title="Form Input File">
            <label for="example_file">Input File Small</label>
            <input
                type="file"
                class="form-sm mb-2"
                id="example_file"
                readonly
                placeholder="Example..."
            />
            <label for="example_file2">Input File</label>
            <input
                type="file"
                class="form mb-2"
                id="example_file2"
                readonly
                placeholder="Example..."
            />
            <label for="example_file3">Input File Large</label>
            <input
                type="file"
                class="form-lg"
                id="example_file3"
                readonly
                placeholder="Example..."
            />
        </Card>
    </div>
</template>
<script setup>
import Card from "@/Components/UI/Card.vue";
</script>
