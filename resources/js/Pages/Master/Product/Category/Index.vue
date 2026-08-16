<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Data Kategori Produk">
                <button class="btn btn-highlight-main" @click="openCreateForm">
                    <FontAwesomeIcon :icon="faPlus" />
                    Tambah Baru
                </button>
            </MainPageHeader>
        </template>

        <div class="p-0">
            <CategoryTree
                :categories="categories"
                @edit="openEditForm"
                @delete="deleteCategory"
                @add-sub="openSubForm"
            />
        </div>
    </MainPage>
</template>

<script setup>
import MainPage from '@/Components/UI/MainPage.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faPlus } from '@fortawesome/free-solid-svg-icons';
import CategoryTree from './Components/CategoryTree.vue';
import CategoryForm from './Components/CategoryForm.vue';
import { useModalStore } from '@/store/notification';
import { usePopUpStore } from '@/store/popup';
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue';

const props = defineProps({
    categories: Array,
});

const modal = useModalStore();
const popUpStore = usePopUpStore();

const openCreateForm = () => {
    popUpStore.open({
        title: 'Buat Kategori Baru',
        size: 'md',
        component: CategoryForm,
        props: {
            category: null,
            parentCategory: null,
            allCategories: props.categories,
        },
    });
};

const openEditForm = (category) => {
    popUpStore.open({
        title: 'Ubah Kategori',
        size: 'md',
        component: CategoryForm,
        props: {
            category: category,
            parentCategory: null,
            allCategories: props.categories,
        },
    });
};

const openSubForm = (parentCategory) => {
    popUpStore.open({
        title: 'Buat Kategori Baru',
        size: 'md',
        component: CategoryForm,
        props: {
            category: null,
            parentCategory: parentCategory,
            allCategories: props.categories,
        },
    });
};

const deleteCategory = (category) => {
    modal.openModalSoftDelete(route('master.categories.destroy', category.id));
};
</script>
