<template>
    <nav aria-label="breadcrumb" class="hidden lg:block">
        <ol class="flex items-center bg-transparent p-0 m-0 list-none text-base font-medium text-neutral-500 gap-2">
            <li class="flex items-center">
                <Link :href="route('overview')" class="hover:text-main transition-colors">
                    <FontAwesomeIcon :icon="faHome" />
                </Link>
            </li>
            <li
                v-for="(crumb, index) in breadcrumbs"
                :key="index"
                class="flex items-center gap-2"
            >
                <FontAwesomeIcon :icon="faChevronRight" class="text-xs text-neutral-400" />
                <Link
                    v-if="crumb.url && index !== breadcrumbs.length - 1"
                    :href="crumb.url"
                    class="hover:text-main transition-colors"
                >
                    {{ crumb.label }}
                </Link>
                <span
                    v-else
                    class="text-neutral-800 font-semibold"
                >
                    {{ crumb.label }}
                </span>
            </li>
        </ol>
    </nav>
</template>

<script setup>
import { faHome, faChevronRight } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const breadcrumbs = computed(() => usePage().props.app.breadcrumbs || []);
</script>
