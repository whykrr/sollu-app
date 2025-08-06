<template>
    <div class="relative" ref="panelRef">
        <a
            href="#"
            class="nav-icon"
            title="Notifikasi"
            @click="toggleNotification"
        >
            <fa icon="fa-regular fa-bell" />
            <span />
        </a>
        <SidebarNotification
            :is-open="showNotification"
            @close="showNotification = false"
        />
    </div>
</template>
<script setup>
import { onBeforeMount, onMounted, ref } from "vue";
import SidebarNotification from "../SidebarNotification/SidebarNotification.vue";

const showNotification = ref(false);
const panelRef = ref(null);

const toggleNotification = () => {
    showNotification.value = !showNotification.value;
};

const handleClickOutside = (event) => {
    if (panelRef.value && !panelRef.value.contains(event.target)) {
        showNotification.value = false;
    }
};

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
});

onBeforeMount(() => {
    document.removeEventListener("click", handleClickOutside);
});
</script>
