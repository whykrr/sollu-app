<template>
    <Container>
        <template #header>
            <div class="flex flex-row justify-between gap-2">
                <div class="flex-1 flex items-center gap-2">
                    <h2 class="text-xl font-bold">Kategori Produk</h2>
                </div>
                <div>
                    <button
                        class="btn btn-highlight-main btn-sm"
                        @click="openCreateForm"
                    >
                        <FontAwesomeIcon :icon="faPlus" />
                        Kategori
                    </button>
                    <CategoryForm
                        :show="showForm"
                        :category="selectedCategory"
                        :parent-category="selectedParent"
                        :all-categories="categories"
                        @close="closeForm"
                    />
                </div>
            </div>
        </template>

        <div class="p-0">
            <CategoryTree
                :categories="categories"
                @edit="openEditForm"
                @delete="deleteCategory"
                @add-sub="openSubForm"
            />
        </div>
    </Container>
</template>

<script setup>
import { ref } from 'vue';
import Container from '@/Components/UI/Container.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faPlus } from '@fortawesome/free-solid-svg-icons';
import CategoryTree from './Components/CategoryTree.vue';
import CategoryForm from './Components/CategoryForm.vue';
import { useModalStore } from '@/store/notification';

const props = defineProps({
    categories: Array,
});

const showForm = ref(false);
const selectedCategory = ref(null);
const selectedParent = ref(null);
const modal = useModalStore();

const openCreateForm = () => {
    selectedCategory.value = null;
    selectedParent.value = null;
    showForm.value = true;
};

const openEditForm = (category) => {
    selectedCategory.value = category;
    selectedParent.value = null;
    showForm.value = true;
};

const openSubForm = (parentCategory) => {
    selectedCategory.value = null;
    selectedParent.value = parentCategory;
    showForm.value = true;
};

const closeForm = () => {
    showForm.value = false;
    setTimeout(() => {
        selectedCategory.value = null;
        selectedParent.value = null;
    }, 200);
};

const deleteCategory = (category) => {
    modal.openModalSoftDelete(route('master.categories.destroy', category.id));
};
</script>
