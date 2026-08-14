<template>
    <div :class="{ tab: !vertical, 'tab-vertical': vertical }">
        <ul role="tablist">
            <li
                v-for="(page, key) in pages"
                :key="key"
                role="presentation"
                :class="{ active: key === activeTab }"
                @click="toggleTab(key)"
            >
                <button class="tab-toggle" type="button" role="tab">
                    <FontAwesomeIcon v-if="page.icon" :icon="page.icon" />
                    {{ page.label }}
                    <span
                        v-if="page.badge"
                        class="badge badge-main text-xs p-1!"
                        >{{ page.badge }}</span
                    >
                </button>
                <span class="separator" />
            </li>
        </ul>
        <div class="tab-content">
            <div
                v-for="(page, key) in pages"
                :key="key"
                class="tab-pane"
                :class="{ active: key === activeTab }"
                role="tabpanel"
            >
                <component
                    :is="page.page"
                    v-bind="page.props || {}"
                    v-if="key === activeTab"
                />
            </div>
            <!-- <slot></slot> -->
        </div>
    </div>
</template>
<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { ref } from 'vue';

const activeTab = ref(0);

const toggleTab = (key) => {
    activeTab.value = key;
};

defineProps({
    pages: Array,
    vertical: Boolean,
});
</script>
