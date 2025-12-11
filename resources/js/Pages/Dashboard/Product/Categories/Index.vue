<template>
    <Head title="Manajemen Kategori" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Manajemen Kategori
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <div class="flex space-x-4">
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Cari..."
                                    class="form-input rounded-md shadow-sm"
                                />
                                <select
                                    v-model="status"
                                    class="form-select rounded-md shadow-sm"
                                >
                                    <option value="">Semua Status</option>
                                    <option value="active">Aktif</option>
                                    <option value="inactive">
                                        Tidak Aktif
                                    </option>
                                </select>
                            </div>
                            <Link
                                :href="route('products.categories.create')"
                                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25 transition"
                            >
                                Tambah Kategori
                            </Link>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            Nama
                                        </th>
                                        <th
                                            scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            Status
                                        </th>
                                        <th
                                            scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            Tipe
                                        </th>
                                        <th
                                            scope="col"
                                            class="relative px-6 py-3"
                                        >
                                            <span class="sr-only">Aksi</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="bg-white divide-y divide-gray-200"
                                >
                                    <template
                                        v-for="category in categories.data"
                                        :key="category.id"
                                    >
                                        <CategoryRow
                                            :category="category"
                                            :level="0"
                                        />
                                    </template>
                                    <tr v-if="!categories.data.length">
                                        <td
                                            colspan="4"
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center"
                                        >
                                            Tidak ada data.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <Pagination class="mt-6" :links="categories.links" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import throttle from 'lodash/throttle';

// Assuming you have a layout component
import CategoryRow from './Partials/CategoryRow.vue';
import Pagination from '@/Components/Dashboard/Tables/Pagination.vue';

const props = defineProps({
    categories: Object,
    filters: Object,
});

const search = ref(props.filters.search);
const status = ref(props.filters.status);

watch(
    [search, status],
    throttle(function ([searchVal, statusVal]) {
        router.get(
            route('products.categories.index'),
            {
                search: searchVal,
                status: statusVal,
            },
            { preserveState: true, replace: true }
        );
    }, 300)
);
</script>
