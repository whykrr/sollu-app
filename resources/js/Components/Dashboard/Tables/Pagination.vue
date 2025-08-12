<template>
    <div class="grid grid-flow-col gap-2 justify-stretch">
        <div v-if="from && to && total" class="text-xs text-neutral-muted">
            Menampilkan
            <span class="font-semibold">{{ from }} - {{ to }}</span>
            <br />
            dari
            <span class="font-semibold">{{ total }}</span>
            hasil
        </div>
        <div
            class="flex flex-col items-end"
            :class="{ 'items-center!': perPage }"
        >
            <div class="inline-flex gap-1">
                <Link
                    v-for="(link, index) in links"
                    :id="link.id"
                    class="btn btn-sm rounded-full"
                    :class="{
                        'btn-highlight-main dark:btn-highlight-main-lighter':
                            link.url != null,
                        active: link.active,
                    }"
                    :href="link.url != null ? link.url : '#'"
                >
                    <FontAwesomeIcon
                        v-if="index === 0"
                        :icon="faAngleLeft"
                    ></FontAwesomeIcon>
                    <FontAwesomeIcon
                        v-else-if="index === links.length - 1"
                        :icon="faAngleRight"
                    ></FontAwesomeIcon>
                    <span v-else>{{ link.label }}</span>
                </Link>
            </div>
        </div>
        <div v-if="perPage" class="flex flex-col items-end">
            <div>
                <label for="pagination_per_page" class="mr-2 text-sm"
                    >Tampilkan</label
                >
                <select
                    name="perpage"
                    class="input text-sm"
                    id="pagination_per_page"
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
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    from: Number,
    to: Number,
    total: Number,
    links: Array,
    perPage: Number,
});

const perPageLabel = [10, 20, 50, 100];
</script>
