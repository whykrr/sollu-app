<template>
    <button
        v-if="data && data?.deleted_at === null"
        class="btn btn-warning btn-sm"
        @click="modal.openModalArchive(urlArchive)"
        aria-label="Hapus"
    >
        <FontAwesomeIcon :icon="faArchive" /> Arsipkan
    </button>

    <Link
        as="button"
        method="PUT"
        v-if="data && data?.deleted_at !== null"
        class="btn btn-success btn-sm"
        :href="urlRestore"
    >
        <FontAwesomeIcon :icon="faArrowsRotate" />
    </Link>

    <button
        method="DELETE"
        v-if="data && data?.deleted_at != null"
        class="btn btn-danger btn-sm"
        @click="modal.openModalDelete(urlDelete)"
    >
        <FontAwesomeIcon :icon="faTrash" /> Hapus
    </button>
</template>
<script setup>
import { useModalStore } from "@/store/Dashboard/notification";
import {
    faArchive,
    faArrowsRotate,
    faTrash,
} from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { Link } from "@inertiajs/vue3";

defineProps({
    data: Object,
    urlArchive: String,
    urlRestore: String,
    urlDelete: String,
});
const modal = useModalStore();
</script>
