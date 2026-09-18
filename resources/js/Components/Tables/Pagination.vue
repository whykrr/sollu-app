<template>
    <div class="w-full">
        <!-- Desktop / Tablet Layout (sm and up) -->
        <div class="hidden sm:flex items-center justify-between gap-4 w-full">
            <!-- Left: Summary Text -->
            <div class="text-xs font-medium text-neutral-500">
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

            <!-- Center: Full Pagination Links -->
            <div v-if="currentLinks && currentLinks.length > 3">
                <div class="flex flex-wrap gap-1.5 items-center justify-center">
                    <template v-for="(link, index) in currentLinks" :key="index">
                        <!-- Disabled Link -->
                        <div
                            v-if="link.url === null"
                            class="flex items-center justify-center w-7 h-7 text-xs text-neutral-300 bg-neutral-50/50 border border-neutral-100 rounded-lg cursor-not-allowed select-none"
                        >
                            <span v-if="index === 0">
                                <FontAwesomeIcon :icon="faAngleLeft" />
                            </span>
                            <span v-else-if="index === currentLinks.length - 1">
                                <FontAwesomeIcon :icon="faAngleRight" />
                            </span>
                            <span v-else v-html="link.label"></span>
                        </div>

                        <!-- Active / Interactive Link -->
                        <Link
                            v-else
                            :href="link.url"
                            preserve-scroll
                            preserve-state
                            class="flex items-center justify-center w-7 h-7 text-xs font-semibold transition-all duration-150 border rounded-lg"
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

            <!-- Right: Per-Page Dropdown (Identical to FilterDropdown) -->
            <div ref="desktopDropdownRef" class="relative inline-block text-left shrink-0">
                <button
                    ref="desktopTriggerRef"
                    type="button"
                    class="btn btn-sm h-[30px] bg-white border border-gray-200 hover:border-gray-300 text-neutral-700 font-medium rounded-lg inline-flex items-center gap-1.5 transition cursor-pointer select-none"
                    @click="toggleDropdown"
                >
                    <span class="text-xs leading-4">{{ currentPerPage }}</span>
                    <FontAwesomeIcon
                        :icon="faChevronDown"
                        class="text-[10px] text-neutral-400 transition-transform duration-200"
                        :class="{ 'rotate-180': isOpen }"
                    />
                </button>
            </div>
        </div>

        <!-- Mobile Layout (< sm): Ultra-compact 1-row Toolbar -->
        <div class="flex sm:hidden items-center justify-between gap-2 w-full">
            <!-- Left: Per-Page Trigger & Summary -->
            <div class="flex items-center gap-2 min-w-0">
                <div ref="mobileDropdownRef" class="relative inline-block text-left shrink-0">
                    <button
                        ref="mobileTriggerRef"
                        type="button"
                        class="btn btn-sm h-[30px] bg-white border border-gray-200 hover:border-gray-300 text-neutral-700 font-medium rounded-lg inline-flex items-center gap-1.5 transition cursor-pointer select-none"
                        @click="toggleDropdown"
                    >
                        <span class="text-xs leading-4 font-semibold">{{ currentPerPage }}</span>
                        <FontAwesomeIcon
                            :icon="faChevronDown"
                            class="text-[10px] text-neutral-400 transition-transform duration-200"
                            :class="{ 'rotate-180': isOpen }"
                        />
                    </button>
                </div>

                <div class="text-xs text-neutral-500 font-medium truncate">
                    <span v-if="currentFrom !== null && currentTo !== null">
                        <span class="font-semibold text-neutral-800"
                            >{{ currentFrom }}-{{ currentTo }}</span
                        >
                        /
                    </span>
                    <span class="font-semibold text-neutral-800">{{ currentTotal }}</span>
                </div>
            </div>

            <!-- Right: Compact Prev / Page Indicator / Next -->
            <div
                v-if="currentLinks && currentLinks.length > 3"
                class="flex items-center gap-1 shrink-0"
            >
                <!-- Prev Button -->
                <Link
                    v-if="prevLinkUrl"
                    :href="prevLinkUrl"
                    preserve-scroll
                    preserve-state
                    class="flex items-center justify-center w-[30px] h-[30px] text-xs font-semibold bg-white text-neutral-700 border border-neutral-200 hover:bg-slate-50 hover:border-neutral-300 rounded-lg transition"
                    title="Halaman Sebelumnya"
                >
                    <FontAwesomeIcon :icon="faAngleLeft" />
                </Link>
                <div
                    v-else
                    class="flex items-center justify-center w-[30px] h-[30px] text-xs text-neutral-300 bg-neutral-50/50 border border-neutral-100 rounded-lg cursor-not-allowed select-none"
                >
                    <FontAwesomeIcon :icon="faAngleLeft" />
                </div>

                <!-- Page Status Indicator Pill -->
                <span
                    class="px-2 h-[30px] inline-flex items-center justify-center text-xs font-semibold text-neutral-700 bg-neutral-100 rounded-lg border border-neutral-200/60 select-none"
                >
                    {{ currentPage }} / {{ lastPage }}
                </span>

                <!-- Next Button -->
                <Link
                    v-if="nextLinkUrl"
                    :href="nextLinkUrl"
                    preserve-scroll
                    preserve-state
                    class="flex items-center justify-center w-[30px] h-[30px] text-xs font-semibold bg-white text-neutral-700 border border-neutral-200 hover:bg-slate-50 hover:border-neutral-300 rounded-lg transition"
                    title="Halaman Selanjutnya"
                >
                    <FontAwesomeIcon :icon="faAngleRight" />
                </Link>
                <div
                    v-else
                    class="flex items-center justify-center w-[30px] h-[30px] text-xs text-neutral-300 bg-neutral-50/50 border border-neutral-100 rounded-lg cursor-not-allowed select-none"
                >
                    <FontAwesomeIcon :icon="faAngleRight" />
                </div>
            </div>
        </div>

        <!-- Teleported Per-Page Dropdown Menu (Shared for Desktop & Mobile) -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-100 ease-out"
                enter-from-class="transform scale-95 opacity-0"
                enter-to-class="transform scale-100 opacity-100"
                leave-active-class="transition duration-75 ease-in"
                leave-from-class="transform scale-100 opacity-100"
                leave-to-class="transform scale-95 opacity-0"
            >
                <div
                    v-if="isOpen"
                    ref="menuRef"
                    class="fixed z-[9999] min-w-[140px] rounded-xl bg-white p-1 shadow-xl ring-1 ring-black/5 focus:outline-none border border-gray-200 text-xs space-y-0.5"
                    :style="menuStyle"
                >
                    <button
                        v-for="pp in perPageOptions"
                        :key="pp"
                        type="button"
                        class="flex w-full items-center justify-between rounded-lg px-2.5 py-1.5 text-left transition cursor-pointer"
                        :class="
                            pp === currentPerPage
                                ? 'bg-primary-50 text-primary-700 font-semibold'
                                : 'text-neutral-700 hover:bg-gray-50'
                        "
                        @click="selectPerPage(pp)"
                    >
                        <span>{{ pp }} per halaman</span>
                        <FontAwesomeIcon
                            v-if="pp === currentPerPage"
                            :icon="faCheck"
                            class="text-xs text-primary-600"
                        />
                    </button>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import {
    faAngleLeft,
    faAngleRight,
    faChevronDown,
    faCheck,
} from '@fortawesome/free-solid-svg-icons'
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

const isOpen = ref(false)
const desktopTriggerRef = ref(null)
const mobileTriggerRef = ref(null)
const menuRef = ref(null)
const menuStyle = ref({})

const perPageOptions = [20, 50, 100]

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

const prevLinkUrl = computed(() => {
    if (props.meta?.prev_page_url) return props.meta.prev_page_url
    if (props.links?.prev) return props.links.prev
    if (currentLinks.value.length > 0 && currentLinks.value[0]?.url) {
        return currentLinks.value[0].url
    }
    return null
})

const nextLinkUrl = computed(() => {
    if (props.meta?.next_page_url) return props.meta.next_page_url
    if (props.links?.next) return props.links.next
    if (currentLinks.value.length > 0 && currentLinks.value[currentLinks.value.length - 1]?.url) {
        return currentLinks.value[currentLinks.value.length - 1].url
    }
    return null
})

const currentPage = computed(() => {
    if (props.meta?.current_page) return props.meta.current_page
    const activeLink = currentLinks.value.find(l => l.active)
    if (activeLink) {
        const num = parseInt(activeLink.label, 10)
        if (!isNaN(num)) return num
    }
    return 1
})

const lastPage = computed(() => {
    if (props.meta?.last_page) return props.meta.last_page
    if (currentPerPage.value && currentTotal.value) {
        return Math.max(1, Math.ceil(currentTotal.value / currentPerPage.value))
    }
    const numericLinks = currentLinks.value.filter(l => !isNaN(parseInt(l.label, 10)))
    if (numericLinks.length > 0) {
        return parseInt(numericLinks[numericLinks.length - 1].label, 10)
    }
    return 1
})

const getActiveTrigger = () => {
    if (desktopTriggerRef.value && desktopTriggerRef.value.offsetParent !== null) {
        return desktopTriggerRef.value
    }
    return mobileTriggerRef.value
}

const updatePosition = () => {
    const trigger = getActiveTrigger()
    if (!trigger) return
    const rect = trigger.getBoundingClientRect()
    const viewportWidth = window.innerWidth
    const viewportHeight = window.innerHeight
    const menuWidth = 140
    const menuHeight = 120

    let top = rect.bottom + 6
    if (top + menuHeight > viewportHeight && rect.top > menuHeight) {
        top = Math.max(8, rect.top - menuHeight - 6)
    }

    let left = rect.left
    if (window.innerWidth >= 640) {
        left = rect.right - menuWidth
    }

    if (left < 8) left = 8
    if (left + menuWidth > viewportWidth - 8) {
        left = Math.max(8, viewportWidth - menuWidth - 8)
    }

    menuStyle.value = {
        top: `${top}px`,
        left: `${left}px`,
    }
}

const toggleDropdown = () => {
    isOpen.value = !isOpen.value
    if (isOpen.value) {
        nextTick(() => {
            updatePosition()
        })
    }
}

const selectPerPage = pp => {
    isOpen.value = false
    if (pp === currentPerPage.value) return

    router.get(
        window.location.pathname,
        {
            ...route().params,
            page: 1,
            perpage: pp,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    )
}

const handleClickOutside = event => {
    if (!isOpen.value) return
    const desktopTrigger = desktopTriggerRef.value
    const mobileTrigger = mobileTriggerRef.value
    const menu = menuRef.value

    const clickedTrigger =
        (desktopTrigger && desktopTrigger.contains(event.target)) ||
        (mobileTrigger && mobileTrigger.contains(event.target))

    if (!clickedTrigger && menu && !menu.contains(event.target)) {
        isOpen.value = false
    }
}

const handleScrollOrResize = () => {
    if (isOpen.value) {
        updatePosition()
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
    window.addEventListener('scroll', handleScrollOrResize, true)
    window.addEventListener('resize', handleScrollOrResize)
})

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside)
    window.removeEventListener('scroll', handleScrollOrResize, true)
    window.removeEventListener('resize', handleScrollOrResize)
})
</script>
