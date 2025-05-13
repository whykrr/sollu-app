<template>
    <div class="grid grid-flow-col gap-2 justify-stretch">
        <div v-if="from && to && total" class="text-xs text-neutral-muted">
            {{ $t("table.showing") }}
            <span class="font-semibold">{{ from }} - {{ to }}</span>
            <br />
            {{ $t("table.of") }}
            <span class="font-semibold">{{ total }}</span>
            {{ $t("table.result") }}
        </div>
        <div
            class="flex flex-col items-end"
            :class="{ '!items-center': perPage }"
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
                    <fa v-if="index === 0" icon="fa-angle-left"></fa>
                    <fa
                        v-else-if="index === links.length - 1"
                        icon="fa-angle-right"
                    ></fa>
                    <span v-else>{{ link.label }}</span>
                </Link>
            </div>
        </div>
        <div v-if="perPage" class="flex flex-col items-end">
            <div>
                <label for="pagination_per_page" class="mr-2 text-sm"
                    >Show</label
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
