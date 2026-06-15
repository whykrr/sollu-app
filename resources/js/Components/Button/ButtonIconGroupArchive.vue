<template>
    <button
        v-if="data && data?.deleted_at === null"
        class="btn btn-highlight-slate-500 btn-sm"
        title="Arsipkan"
        @click="modal.openModalSoftDelete(urlDelete)"
    >
        <FontAwesomeIcon :icon="faBoxArchive" />
    </button>

    <Link
        v-if="data && data?.deleted_at !== null"
        as="button"
        method="PUT"
        class="btn btn-highlight-success btn-sm"
        title="Kembalikan"
        :href="urlRestore"
    >
        <FontAwesomeIcon :icon="faRotateLeft" />
    </Link>

    <button
        v-if="data && data?.deleted_at != null"
        method="DELETE"
        class="btn btn-highlight-danger btn-sm"
        title="Hapus Permanen"
        @click="modal.openModalDelete(urlDestroy)"
    >
        <FontAwesomeIcon :icon="faTrash" />
    </button>
</template>
<script setup>
import { useModalStore } from '@/store/notification';
import {
    faBoxArchive,
    faRotateLeft,
    faTrash,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link } from '@inertiajs/vue3';

defineProps({
    data: Object,
    urlDelete: String,
    urlRestore: String,
    urlDestroy: String,
});
const modal = useModalStore();
</script>
