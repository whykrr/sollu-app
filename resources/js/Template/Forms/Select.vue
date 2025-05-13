<template>
    <div class="flex flex-col gap-4">
        <Card title="Select Default">
            <label for="select_default"> Select Default </label>
            <select name="select" id="select_default" class="form">
                <option value="">Open this to select menu</option>
                <option>One</option>
                <option>Two</option>
                <option>Three</option>
            </select>
        </Card>
        <Card title="Select Sizing">
            <div class="flex flex-col gap-2">
                <select name="select" id="select_default" class="form-sm">
                    <option value="">Open this to select menu</option>
                    <option>One</option>
                    <option>Two</option>
                    <option>Three</option>
                </select>
                <select name="select" id="select_default" class="form">
                    <option value="">Open this to select menu</option>
                    <option>One</option>
                    <option>Two</option>
                    <option>Three</option>
                </select>

                <select name="select" id="select_default" class="form-lg">
                    <option value="">Open this to select menu</option>
                    <option>One</option>
                    <option>Two</option>
                    <option>Three</option>
                </select>
            </div>
        </Card>
        <Card title="Select Disabled">
            <label for="select_default">Select Disabled</label>
            <select name="select" id="select_default" class="form" disabled>
                <option value="">Open this to select menu</option>
                <option>One</option>
                <option>Two</option>
                <option>Three</option>
            </select>
        </Card>
    </div>
</template>
<script setup>
import Card from "@/Components/UI/Card.vue";
</script>
