<template>
    <nav aria-label="breadcrumb">
        <ol class="bg-transparent p-0 m-0 list-none text-sm">
            <li class="inline-block">
                <Link :href="route('main')"> <fa icon="fa-home"></fa></Link>
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
</template>

<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const page = usePage();
const breadcrumbs = computed(() => page.props.breadcrumbs);
</script>
