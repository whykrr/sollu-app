<template>
    <Container>
        <template #header>
            <ContainerHeader :title="`Detail Faktur ${invoice.receipt_number}`" />
        </template>
        <div class="p-4 space-y-4">
            <p>Total: {{ invoice.total }}</p>
            <p>Pelanggan: {{ invoice.customer?.name }}</p>
        </div>
    </Container>
</template>

<script setup>
import { defineProps } from 'vue';
import Container from '@/Components/Container.vue';
import ContainerHeader from '@/Components/ContainerHeader.vue';

const props = defineProps({
    invoice: Object
});
</script>
