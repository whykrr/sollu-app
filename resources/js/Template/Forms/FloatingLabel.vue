<template>
    <div class="flex flex-col gap-4">
        <Card title="Basic Example">
            <div class="form-floating">
                <input
                    type="text"
                    id="name"
                    placeholder="asd"
                    required
                    class="form"
                />
                <label for="name">Name</label>
            </div>
        </Card>
    </div>
</template>
<script setup>
import Card from "@/Components/UI/Card.vue";
</script>
