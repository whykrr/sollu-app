<template>
    <Container>
        <template #header>
            <ContainerHeader title="Buat Faktur Baru" />
        </template>
        <div class="p-4 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <!-- Scaffold form fields using frontend rules -->
                    <p>Pilih Pelanggan & Jatuh Tempo</p>
                </div>
            </div>
            
            <div class="mt-4">
                <p>Daftar Item</p>
            </div>
        </div>
        <template #footer>
            <div class="flex justify-end gap-2">
                <Link :href="route('sales.invoices.index')" class="btn btn-outline-main">Batal</Link>
                <button class="btn btn-main" @click="form.post(route('sales.invoices.store'))">Simpan</button>
            </div>
        </template>
    </Container>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import Container from '@/Components/Container.vue';
import ContainerHeader from '@/Components/ContainerHeader.vue';

const form = useForm({
    customer_id: '',
    due_date: '',
    items: [],
    subtotal: 0,
    tax_amount: 0,
    discount_amount: 0,
    total: 0
});
</script>
