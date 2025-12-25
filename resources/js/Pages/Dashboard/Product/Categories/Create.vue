<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import Container from '@/Components/Dashboard/UI/Container.vue';
import CategoryForm from './Partials/CategoryForm.vue';

const props = defineProps({
    availableCategories: Array,
});

const form = useForm({
    name: '',
    parent_id: null,
});

const submit = () => {
    form.post(route('dashboard.products.categories.store'));
};
</script>

<template>
    <Head title="Tambah Kategori" />

    <Container>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Tambah Kategori Baru
            </h2>
        </template>

        <div class="bg-white rounded-lg p-6 border border-neutral-200">
            <CategoryForm
                :form="form"
                :categories="availableCategories"
                @submit="submit"
            />
        </div>
    </Container>
</template>
