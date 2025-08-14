<template>
    <div :class="{ tab: !vertical, 'tab-vertical': vertical }">
        <ul role="tablist">
            <li
                v-for="(page, key) in pages"
                role="presentation"
                :class="{ active: key === activeTab }"
                @click="toggleTab(key)"
            >
                <button class="tab-toggle" type="button" role="tab">
                    <FontAwesomeIcon :icon="page.icon" />
                    {{ page.label }}
                    <span class="badge badge-main text-xs p-1!">20</span>
                </button>
                <span class="separator"></span>
            </li>
        </ul>
        <div class="tab-content">
            <div
                v-for="(page, key) in pages"
                class="tab-pane"
                :class="{ active: key === activeTab }"
                role="tabpanel"
            >
                <component v-if="key === activeTab" :is="page.page" />
            </div>
            <!-- <slot></slot> -->
        </div>
    </div>
</template>
<script setup>
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { ref } from "vue";

const activeTab = ref(0);

const toggleTab = (key) => {
    activeTab.value = key;
};

defineProps({
    pages: Array,
    vertical: Boolean,
});
</script>
