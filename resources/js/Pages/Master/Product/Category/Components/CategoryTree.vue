<template>
    <div class="category-tree space-y-2">
        <draggable
            v-model="localCategories"
            group="root"
            item-key="id"
            handle=".drag-handle"
            @change="onReorderRoot"
            class="space-y-2"
        >
            <template #item="{ element: category }">
                <div class="bg-white border border-slate-200 rounded-xl">
                    <div
                        class="p-4 py-2 flex items-center justify-between border-b border-slate-100 bg-slate-50 rounded-t-xl"
                    >
                        <div class="flex items-center gap-3">
                            <FontAwesomeIcon
                                :icon="faGripVertical"
                                class="drag-handle cursor-move text-slate-400 hover:text-slate-600"
                            />
                            <div class="font-semibold">{{ category.name }}</div>
                            <div class="badge badge-neutral-500 text-xs">
                                {{
                                    category.children
                                        ? category.children.length
                                        : 0
                                }}
                                Sub
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                class="btn btn-outline-secondary btn-sm"
                                @click="$emit('add-sub', category)"
                                title="Tambah Sub-Kategori"
                            >
                                <FontAwesomeIcon :icon="faPlus" /> Sub
                            </button>
                            <button
                                class="btn btn-outline-secondary btn-sm"
                                @click="$emit('edit', category)"
                                title="Edit"
                            >
                                <FontAwesomeIcon :icon="faPencil" />
                            </button>
                            <button
                                class="btn btn-outline-danger btn-sm"
                                @click="$emit('delete', category)"
                                title="Hapus"
                            >
                                <FontAwesomeIcon :icon="faTrash" />
                            </button>
                        </div>
                    </div>

                    <!-- Sub Categories -->
                    <div
                        class="p-2 pl-8"
                        v-if="category.children && category.children.length > 0"
                    >
                        <draggable
                            v-model="category.children"
                            group="sub"
                            item-key="id"
                            handle=".drag-handle-sub"
                            @change="onReorderSub(category)"
                            class="space-y-1"
                        >
                            <template #item="{ element: subCategory }">
                                <div
                                    class="flex items-center justify-between p-3 py-1.5 border border-slate-100 rounded-xl bg-white"
                                >
                                    <div class="flex items-center gap-3">
                                        <FontAwesomeIcon
                                            :icon="faGripVertical"
                                            class="drag-handle-sub cursor-move text-slate-400 hover:text-slate-600"
                                        />
                                        <div>{{ subCategory.name }}</div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button
                                            class="btn btn-outline-secondary btn-sm"
                                            @click="$emit('edit', subCategory)"
                                            title="Edit"
                                        >
                                            <FontAwesomeIcon :icon="faPencil" />
                                        </button>
                                        <button
                                            class="btn btn-outline-danger btn-sm"
                                            @click="
                                                $emit('delete', subCategory)
                                            "
                                            title="Hapus"
                                        >
                                            <FontAwesomeIcon :icon="faTrash" />
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </draggable>
                    </div>
                </div>
            </template>
        </draggable>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import draggable from 'vuedraggable';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faGripVertical,
    faPencil,
    faTrash,
    faPlus,
} from '@fortawesome/free-solid-svg-icons';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { useToastStore } from '@/store/toast';

const toastStore = useToastStore();

const props = defineProps({
    categories: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(['edit', 'delete', 'add-sub']);

const localCategories = ref([]);

watch(
    () => props.categories,
    (newVal) => {
        // Deep clone to avoid mutating props directly when dragging
        localCategories.value = JSON.parse(JSON.stringify(newVal));
    },
    { immediate: true, deep: true },
);

const saveReorder = () => {
    // Flatten the categories to build the payload
    const payload = [];

    localCategories.value.forEach((rootCat, rootIndex) => {
        payload.push({
            id: rootCat.id,
            parent_id: null,
            sort_order: rootIndex + 1,
        });

        if (rootCat.children) {
            rootCat.children.forEach((subCat, subIndex) => {
                payload.push({
                    id: subCat.id,
                    parent_id: rootCat.id, // Update parent_id in case it was dragged to another parent
                    sort_order: subIndex + 1,
                });
            });
        }
    });

    axios
        .post(route('master.categories.reorder'), { categories: payload })
        .then((response) => {
            toastStore.success('Urutan kategori berhasil disimpan.');
            router.reload({ only: ['categories'] });
        })
        .catch((error) => {
            console.error(error);
            toastStore.danger(error.response?.data?.message || 'Gagal menyimpan urutan');
            router.reload({ only: ['categories'] });
        });
};

const onReorderRoot = () => {
    saveReorder();
};

const onReorderSub = (parentCategory) => {
    saveReorder();
};
</script>

<style scoped>
.sortable-ghost {
    opacity: 0.5;
    background-color: #f8fafc;
}
</style>
