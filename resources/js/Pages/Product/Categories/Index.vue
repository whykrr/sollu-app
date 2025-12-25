<template>
    <Head title="Manajemen Kategori" />

    <Container>
        <template #header>
            <div>
                <Filter :filters="filters" />
            </div>
            <div>
                <Link
                    :href="route('products.categories.create')"
                    class="btn btn-highlight-main btn-sm"
                >
                    <FontAwesomeIcon :icon="faPlus" />
                    Kategori
                </Link>
            </div>
        </template>

        <div>
            <table class="table table-hovered min-w-full">
                <thead class="">
                    <tr
                        class="text-neutral-700 select-none sticky top-0 left-0 z-auto overflow-hidden"
                    >
                        <th
                            scope="col"
                            class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"
                        >
                            Nama
                        </th>
                        <th
                            scope="col"
                            class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"
                        >
                            Status
                        </th>
                        <th
                            scope="col"
                            class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"
                        >
                            Tipe
                        </th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">Aksi</span>
                        </th>
                        <th width="1%" class="sticky top-0 z-10" />
                    </tr>
                </thead>
                <tbody class="bg-white">
                    <template
                        v-for="category in categories.data"
                        :key="category.id"
                    >
                        <CategoryRow :category="category" :level="0" />
                    </template>
                    <tr v-if="!categories.data.length">
                        <td
                            colspan="5"
                            class="px-6 py-8 whitespace-nowrap text-sm text-gray-500 text-center"
                        >
                            Tidak ada data.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <template #footer>
            <Pagination class="mt-6" :links="categories.links" />
        </template>
    </Container>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Container from '@/Components/UI/Container.vue';
import CategoryRow from './Partials/CategoryRow.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import Filter from '@/Pages/Product/Categories/Components/Filter.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faPlus } from '@fortawesome/free-solid-svg-icons';

defineProps({
    categories: Object,
    filters: Object,
});
</script>
