<template>
    <button
        v-if="data && data?.deleted_at === null"
        class="btn btn-danger btn-sm"
        aria-label="Hapus"
        @click="modal.openModalSoftDelete(urlArchive)"
    >
        <FontAwesomeIcon :icon="faTrash" /> Hapus
    </button>

    <Link
        v-if="data && data?.deleted_at !== null"
        as="button"
        method="PUT"
        class="btn btn-success btn-sm"
        :href="urlRestore"
    >
        <FontAwesomeIcon :icon="faArrowsRotate" /> Pulihkan
    </Link>

    <button
        v-if="data && data?.deleted_at != null"
        method="DELETE"
        class="btn btn-danger btn-sm"
        @click="modal.openModalDelete(urlDelete)"
    >
        <FontAwesomeIcon :icon="faTrash" /> Hapus Permanen
    </button>
</template>
<script setup>
import { useModalStore } from '@/store/notification';
import { faArrowsRotate, faTrash } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link } from '@inertiajs/vue3';

defineProps({
    data: Object,
    urlArchive: String,
    urlRestore: String,
    urlDelete: String,
});
const modal = useModalStore();
</script>
