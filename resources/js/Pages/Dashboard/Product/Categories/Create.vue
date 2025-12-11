<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layout/Dashboard/AppLayout.vue';
import CategoryForm from './Partials/CategoryForm.vue';

const props = defineProps({
    availableCategories: Array,
});

const form = useForm({
    name: '',
    parent_id: null,
});

const submit = () => {
    form.post(route('products.categories.store'));
};
</script>

<template>
    <Head title="Tambah Kategori" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Tambah Kategori Baru
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <CategoryForm
                            :form="form"
                            :categories="availableCategories"
                            @submit="submit"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
