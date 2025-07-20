<template>
    <div class="table-responsive">
        <table class="table">
            <thead class="text-left">
                <tr>
                    <td>No</td>
                    <td>Tautan</td>
                    <td>Kunjungan</td>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(data, i) in data">
                    <td>{{ i + 1 }}</td>
                    <td>{{ data.url }}</td>
                    <td>{{ data.visits }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { router } from "@inertiajs/vue3";

defineProps({
    data: Array,
});
</script>
