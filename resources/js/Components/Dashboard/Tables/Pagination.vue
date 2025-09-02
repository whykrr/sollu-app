<template>
    <div class="grid grid-cols-3 gap-2 justify-stretch">
        <div class="text-sm text-neutral-400 my-auto">
            <span class="font-medium">{{ from }} - {{ to }}</span>
            dari
            <span class="font-medium">{{ total }}</span>
            hasil
        </div>
        <div
            class="inline-flex items-center mx-auto"
            :class="{ 'items-center!': perPage }"
        >
            <div
                class="flex flex-row gap-1 bg-white p-1 py-1 border border-gray-300 rounded text-sm"
            >
                <Link
                    v-for="(link, index) in links"
                    :id="link.id"
                    class="text-sm text-gray-600 rounded-full min-h-full h-6 w-6 flex"
                    :class="{
                        '': link.url == null,
                        'bg-main/30 text-main font-medium': link.active,
                        'hover:bg-main/30 hover:text-main':
                            index !== 0 && index !== links.length - 1,
                    }"
                    :href="link.url ?? '#'"
                >
                    <FontAwesomeIcon
                        v-if="index === 0"
                        :icon="faAngleLeft"
                        class="m-auto"
                    ></FontAwesomeIcon>
                    <FontAwesomeIcon
                        v-else-if="index === links.length - 1"
                        :icon="faAngleRight"
                        class="m-auto"
                    ></FontAwesomeIcon>
                    <span v-else class="m-auto">{{ link.label }}</span>
                </Link>
            </div>
        </div>
        <div class="flex flex-row items-center justify-end">
            <div>
                <label
                    for="pagination_per_page"
                    class="mr-2 text-sm text-neutral-400"
                    >Tampilkan</label
                >
            </div>
            <div>
                <select
                    name="perpage"
                    class="form sm w-16"
                    id="pagination_per_page"
                    @change="changePerPage"
                >
                    <option
                        v-for="pp in perPageLabel"
                        :selected="pp === perPage"
                    >
                        {{ pp }}
                    </option>
                </select>
            </div>
        </div>
    </div>
</template>

<script setup>
import { faAngleLeft, faAngleRight } from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { Link, router } from "@inertiajs/vue3";

const props = defineProps({
    from: Number,
    to: Number,
    total: Number,
    links: Array,
    perPage: Number,
});

const perPageLabel = [20, 50, 100];

function changePerPage(event) {
    router.get(
        window.location.pathname,
        {
            ...route().params,
            page: 1,
            perpage: event.target.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
}
</script>
