<template>
    <nav aria-label="breadcrumb" class="hidden lg:block">
        <ol class="bg-transparent p-0 m-0 list-none text-sm">
            <li class="inline-block">
                <Link :href="route('dashboard.overview')">
                    <FontAwesomeIcon :icon="faHome"></FontAwesomeIcon
                ></Link>
            </li>
            <li
                v-for="(crumb, index) in breadcrumbs"
                :key="index"
                class="inline-block before:content-['/'] before:px-2"
            >
                <Link :href="crumb.url" v-if="crumb.url">
                    {{ crumb.label }}
                </Link>
                <span v-else>{{ crumb.label }}</span>
            </li>
        </ol>
    </nav>
    <div class="text-xl font-medium">
        {{ lastCrumbLabel }}
    </div>
</template>

<script setup>
import { faHome } from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const breadcrumbs = computed(() => usePage().props.app.breadcrumbs);
const lastCrumbLabel = computed(() => {
    const items = breadcrumbs.value;
    return items.length > 0 ? items[items.length - 1].label : "";
});
</script>
