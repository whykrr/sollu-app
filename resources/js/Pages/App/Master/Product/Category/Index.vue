<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Data Kategori Produk"
                description="Kelola susunan dan hierarki pengelompokan produk serta menu"
            >
                <button
                    v-if="can(enums.PermissionEnum?.CATEGORY_CREATE || 'category.create')"
                    class="btn btn-main"
                    @click="openCreateForm"
                >
                    <FontAwesomeIcon :icon="faPlus" />
                    Tambah Kategori
                </button>
            </MainPageHeader>
        </template>

        <!-- Category Tree List -->
        <div v-if="categories && categories.length > 0">
            <CategoryTree
                :categories="categories"
                @edit="openEditForm"
                @delete="deleteCategory"
                @add-sub="openSubForm"
            />
        </div>

        <!-- Empty State -->
        <div
            v-else
            class="flex flex-col items-center justify-center p-8 md:p-12 text-center bg-white rounded-xl border border-slate-200"
        >
            <div
                class="w-14 h-14 mb-3 rounded-full bg-slate-100 flex items-center justify-center text-slate-400"
            >
                <FontAwesomeIcon :icon="faFolderPlus" class="text-2xl text-slate-500" />
            </div>
            <h3 class="text-sm font-semibold text-slate-800 mb-1">Belum Ada Kategori</h3>
            <p class="text-xs text-slate-500 max-w-sm mb-4">
                Kategori memudahkan pengorganisasian katalog menu dan produk di outlet Anda. Buat
                kategori pertama Anda sekarang.
            </p>
            <button
                v-if="can(enums.PermissionEnum?.CATEGORY_CREATE || 'category.create')"
                class="btn btn-highlight-main btn-sm"
                @click="openCreateForm"
            >
                <FontAwesomeIcon :icon="faPlus" />
                Tambah Kategori
            </button>
        </div>
    </MainPage>
</template>

<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faPlus, faFolderPlus } from '@fortawesome/free-solid-svg-icons'
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import CategoryTree from './Components/CategoryTree.vue'
import CategoryForm from './Components/CategoryForm.vue'
import { useModalStore } from '@/store/notification'
import { usePopUpStore } from '@/store/popup'
import { useAuth } from '@/Composable/useAuth'
import { useEnum } from '@/Composable/useEnum'

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
})

const modal = useModalStore()
const popUpStore = usePopUpStore()
const { can } = useAuth()
const { enums } = useEnum()

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
    })
}

const openEditForm = category => {
    popUpStore.open({
        title: 'Ubah Kategori',
        size: 'md',
        component: CategoryForm,
        props: {
            category: category,
            parentCategory: null,
            allCategories: props.categories,
        },
    })
}

const openSubForm = parentCategory => {
    popUpStore.open({
        title: 'Buat Sub-Kategori',
        size: 'md',
        component: CategoryForm,
        props: {
            category: null,
            parentCategory: parentCategory,
            allCategories: props.categories,
        },
    })
}

const deleteCategory = category => {
    modal.openModalSoftDelete(route('master.categories.destroy', category.id))
}
</script>
