<template>
    <Container>
        <template #header>
            <ContainerHeader title="Data Kategori Produk">
                <button class="btn btn-secondary btn-sm">
                    <FontAwesomeIcon :icon="faUpload" />
                    Impor CSV
                </button>
                <button class="btn btn-highlight-main" @click="openCreateForm">
                    <FontAwesomeIcon :icon="faPlus" />
                    Tambah Baru
                </button>
            </ContainerHeader>
            <CategoryForm
                :show="showForm"
                :category="selectedCategory"
                :parent-category="selectedParent"
                :all-categories="categories"
                @close="closeForm"
            />
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
import { faPlus, faUpload } from '@fortawesome/free-solid-svg-icons';
import CategoryTree from './Components/CategoryTree.vue';
import CategoryForm from './Components/CategoryForm.vue';
import { useModalStore } from '@/store/notification';
import ContainerHeader from '@/Components/UI/Container/ContainerHeader.vue';

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
