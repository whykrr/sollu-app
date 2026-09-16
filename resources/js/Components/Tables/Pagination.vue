<template>
    <div class="flex flex-col sm:flex-row gap-4 items-center justify-between w-full">
        <!-- Summary Text -->
        <div class="text-xs font-medium text-neutral-500 order-2 sm:order-1">
            Menampilkan
            <span v-if="currentFrom !== null && currentTo !== null" class="text-neutral-600">
                <span class="font-semibold text-neutral-800">{{ currentFrom }}</span>
                -
                <span class="font-semibold text-neutral-800">{{ currentTo }}</span>
                dari
            </span>
            <span class="font-semibold text-neutral-800">{{ currentTotal }}</span>
            data
        </div>

        <!-- Pagination Links -->
        <div v-if="currentLinks && currentLinks.length > 3" class="order-1 sm:order-2">
            <div class="flex flex-wrap gap-1.5 items-center justify-center">
                <template v-for="(link, index) in currentLinks" :key="index">
                    <!-- Disabled Link -->
                    <div
                        v-if="link.url === null"
                        class="flex items-center justify-center w-8 h-8 text-xs text-neutral-300 bg-neutral-50/50 border border-neutral-100 rounded-lg cursor-not-allowed select-none"
                    >
                        <span v-if="index === 0">
                            <FontAwesomeIcon :icon="faAngleLeft" />
                        </span>
                        <span v-else-if="index === currentLinks.length - 1">
                            <FontAwesomeIcon :icon="faAngleRight" />
                        </span>
                        <span v-else v-html="link.label"></span>
                    </div>

                    <!-- Active/Interactive Link -->
                    <Link
                        v-else
                        :href="link.url"
                        class="flex items-center justify-center w-8 h-8 text-xs font-semibold transition-all duration-150 border rounded-lg"
                        :class="{
                            'bg-main text-white border-main': link.active,
                            'bg-white text-neutral-600 border-neutral-200 hover:bg-slate-50 hover:border-neutral-300 hover:text-neutral-800':
                                !link.active,
                        }"
                    >
                        <span v-if="index === 0">
                            <FontAwesomeIcon :icon="faAngleLeft" />
                        </span>
                        <span v-else-if="index === currentLinks.length - 1">
                            <FontAwesomeIcon :icon="faAngleRight" />
                        </span>
                        <span v-else v-html="link.label"></span>
                    </Link>
                </template>
            </div>
        </div>

        <!-- Per Page Selector -->
        <div class="flex items-center gap-2 order-3 sm:w-auto w-full sm:justify-end justify-center">
            <label
                for="pagination_per_page"
                class="text-xs font-bold uppercase tracking-wider text-neutral-400 whitespace-nowrap mb-0"
            >
                Tampilkan
            </label>
            <select
                id="pagination_per_page"
                name="perpage"
                class="!text-xs !font-semibold !rounded-lg !border-neutral-200 !py-1.5 !px-2.5 focus:!border-main focus:!ring-main hover:!border-neutral-300 transition-colors duration-150 text-neutral-600 cursor-pointer !w-20"
                @change="changePerPage"
            >
                <option
                    v-for="(pp, index) in perPageLabel"
                    :key="index"
                    :selected="pp === currentPerPage"
                    :value="pp"
                >
                    {{ pp }}
                </option>
            </select>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { faAngleLeft, faAngleRight } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
    meta: {
        type: Object,
        default: null,
    },
    from: {
        type: Number,
        default: null,
    },
    to: {
        type: Number,
        default: null,
    },
    total: {
        type: Number,
        default: null,
    },
    links: {
        type: Array,
        default: null,
    },
    perPage: {
        type: Number,
        default: null,
    },
})

const currentFrom = computed(() => {
    return props.from !== null ? props.from : (props.meta?.from ?? null)
})

const currentTo = computed(() => {
    return props.to !== null ? props.to : (props.meta?.to ?? null)
})

const currentTotal = computed(() => {
    return props.total !== null ? props.total : (props.meta?.total ?? 0)
})

const currentLinks = computed(() => {
    if (props.links && Array.isArray(props.links)) {
        return props.links
    }
    if (props.meta?.links && Array.isArray(props.meta.links)) {
        return props.meta.links
    }
    return []
})

const currentPerPage = computed(() => {
    return props.perPage !== null ? props.perPage : (props.meta?.per_page ?? 20)
})

const perPageLabel = [20, 50, 100]

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
    )
}
</script>
