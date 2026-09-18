<template>
    <div class="category-tree space-y-2">
        <draggable
            v-model="localCategories"
            group="root"
            item-key="id"
            handle=".drag-handle"
            class="space-y-2"
            :disabled="!can(enums.PermissionEnum?.CATEGORY_UPDATE || 'category.update')"
            @change="onReorderRoot"
        >
            <template #item="{ element: category }">
                <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                    <div
                        class="px-4 py-2.5 flex items-center justify-between border-b border-slate-100 bg-slate-50"
                    >
                        <div class="flex items-center gap-2.5">
                            <FontAwesomeIcon
                                v-if="
                                    can(enums.PermissionEnum?.CATEGORY_UPDATE || 'category.update')
                                "
                                :icon="faGripVertical"
                                class="drag-handle cursor-move text-slate-400 hover:text-slate-600 text-xs"
                            />
                            <div class="font-semibold text-xs text-slate-800">
                                {{ category.name }}
                            </div>
                            <span class="badge badge-gray text-[10px] py-0.5 px-2">
                                {{ category.children ? category.children.length : 0 }} Sub
                            </span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button
                                v-if="
                                    can(enums.PermissionEnum?.CATEGORY_CREATE || 'category.create')
                                "
                                class="btn btn-flat btn-sm"
                                title="Tambah Sub-Kategori"
                                @click="$emit('add-sub', category)"
                            >
                                <FontAwesomeIcon :icon="faPlus" />
                                <span>Sub</span>
                            </button>
                            <button
                                v-if="
                                    can(enums.PermissionEnum?.CATEGORY_UPDATE || 'category.update')
                                "
                                class="btn btn-flat btn-sm"
                                title="Ubah Kategori"
                                @click="$emit('edit', category)"
                            >
                                <FontAwesomeIcon :icon="faPencil" />
                            </button>
                            <button
                                v-if="
                                    can(enums.PermissionEnum?.CATEGORY_DELETE || 'category.delete')
                                "
                                class="btn btn-flat btn-sm text-red-600 hover:bg-red-50 hover:border-red-200"
                                title="Hapus Kategori"
                                @click="$emit('delete', category)"
                            >
                                <FontAwesomeIcon :icon="faTrash" />
                            </button>
                        </div>
                    </div>

                    <!-- Sub Categories -->
                    <div
                        v-if="category.children && category.children.length > 0"
                        class="p-2 pl-6 space-y-1.5"
                    >
                        <draggable
                            v-model="category.children"
                            group="sub"
                            item-key="id"
                            handle=".drag-handle-sub"
                            class="space-y-1.5"
                            :disabled="
                                !can(enums.PermissionEnum?.CATEGORY_UPDATE || 'category.update')
                            "
                            @change="onReorderSub(category)"
                        >
                            <template #item="{ element: subCategory }">
                                <div
                                    class="flex items-center justify-between px-3 py-1.5 border border-slate-100 rounded-lg bg-white hover:bg-slate-50 transition-colors"
                                >
                                    <div class="flex items-center gap-2.5">
                                        <FontAwesomeIcon
                                            v-if="
                                                can(
                                                    enums.PermissionEnum?.CATEGORY_UPDATE ||
                                                        'category.update'
                                                )
                                            "
                                            :icon="faGripVertical"
                                            class="drag-handle-sub cursor-move text-slate-400 hover:text-slate-600 text-xs"
                                        />
                                        <div class="text-xs text-slate-700">
                                            {{ subCategory.name }}
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <button
                                            v-if="
                                                can(
                                                    enums.PermissionEnum?.CATEGORY_UPDATE ||
                                                        'category.update'
                                                )
                                            "
                                            class="btn btn-flat btn-xs"
                                            title="Ubah Sub-Kategori"
                                            @click="$emit('edit', subCategory)"
                                        >
                                            <FontAwesomeIcon :icon="faPencil" />
                                        </button>
                                        <button
                                            v-if="
                                                can(
                                                    enums.PermissionEnum?.CATEGORY_DELETE ||
                                                        'category.delete'
                                                )
                                            "
                                            class="btn btn-flat btn-xs text-red-600 hover:bg-red-50 hover:border-red-200"
                                            title="Hapus Sub-Kategori"
                                            @click="$emit('delete', subCategory)"
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
import { ref, watch } from 'vue'
import draggable from 'vuedraggable'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faGripVertical, faPencil, faTrash, faPlus } from '@fortawesome/free-solid-svg-icons'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import { useToastStore } from '@/store/toast'
import { useAuth } from '@/Composable/useAuth'
import { useEnum } from '@/Composable/useEnum'

const props = defineProps({
    categories: {
        type: Array,
        required: true,
    },
})

defineEmits(['edit', 'delete', 'add-sub'])

const toastStore = useToastStore()
const { can } = useAuth()
const { enums } = useEnum()

const localCategories = ref([])

watch(
    () => props.categories,
    newVal => {
        localCategories.value = JSON.parse(JSON.stringify(newVal || []))
    },
    { immediate: true, deep: true }
)

const saveReorder = () => {
    const payload = []

    localCategories.value.forEach((rootCat, rootIndex) => {
        payload.push({
            id: rootCat.id,
            parent_id: null,
            sort_order: rootIndex + 1,
        })

        if (rootCat.children) {
            rootCat.children.forEach((subCat, subIndex) => {
                payload.push({
                    id: subCat.id,
                    parent_id: rootCat.id,
                    sort_order: subIndex + 1,
                })
            })
        }
    })

    axios
        .post(route('master.categories.reorder'), { categories: payload })
        .then(response => {
            toastStore.success(response.data?.message || 'Urutan kategori berhasil disimpan.')
            router.reload({ only: ['categories'] })
        })
        .catch(error => {
            toastStore.danger(error.response?.data?.message || 'Gagal menyimpan urutan kategori.')
            router.reload({ only: ['categories'] })
        })
}

const onReorderRoot = () => {
    saveReorder()
}

const onReorderSub = () => {
    saveReorder()
}
</script>

<style scoped>
.sortable-ghost {
    opacity: 0.5;
    background-color: #f8fafc;
}
</style>
