<template>
    <button class="btn btn-highlight-warning btn-sm" @click.prevent="goBack">
        <FontAwesomeIcon :icon="faArrowLeft" /> Batal
    </button>
</template>
<script setup>
import { faArrowLeft } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { router } from '@inertiajs/vue3';

const goBack = () => {
    if (document.referrer) {
        router.back();
    } else {
        router.visit(route('overview'));
    }
};
</script>
