<template>
    <div ref="dropdownRef" class="relative">
        <div class="relative">
            <a href="#" class="text-slate-700 block" @click.prevent="togglePanel">
                <div
                    class="rounded-full w-10 h-10 bg-white flex items-center justify-center border border-neutral-200 hover:bg-neutral-50 hover:border-indigo-300 transition-all duration-150 ease-in-out font-bold text-indigo-700 text-sm shadow-xs"
                >
                    {{ initials }}
                </div>
            </a>
        </div>
        <transition name="fade-down" mode="in-out">
            <div
                v-if="showPanel"
                class="absolute z-50 bg-white border border-neutral-100 rounded-xl w-72 top-[48px] right-0 shadow-2xl ring-1 ring-black/5 p-4"
            >
                <div class="flex flex-col gap-2">
                    <div class="absolute right-4 top-4">
                        <a
                            href="#"
                            class="text-neutral-400 hover:text-neutral-600 transition-colors"
                            @click.prevent="closePanel"
                        >
                            <FontAwesomeIcon :icon="faClose" />
                        </a>
                    </div>
                    <div class="flex flex-col items-center mt-2 mb-2">
                        <div
                            class="rounded-full w-20 h-20 text-2xl bg-indigo-50 flex items-center justify-center border border-indigo-100 text-indigo-700 font-bold mb-3 shadow-inner"
                        >
                            {{ initials }}
                        </div>
                        <div
                            class="text-center font-semibold text-lg text-neutral-800 leading-tight"
                        >
                            {{ auth?.name }}
                        </div>
                        <div class="text-center text-xs font-normal text-neutral-500 mt-0.5">
                            {{ auth?.email }}
                        </div>
                    </div>

                    <div
                        class="bg-neutral-50 rounded-xl overflow-hidden border border-neutral-100 mt-1"
                    >
                        <ol>
                            <li
                                v-for="(item, index) in accountLinks"
                                :key="index"
                                class="border-b border-neutral-100 last:border-0"
                            >
                                <button
                                    v-if="item.action"
                                    type="button"
                                    class="flex items-center w-full gap-3 px-4 py-2.5 hover:bg-white text-sm text-neutral-700 font-medium transition-all duration-150 ease-in-out group cursor-pointer"
                                    @click="item.action"
                                >
                                    <div
                                        class="w-5 flex justify-center text-neutral-400 group-hover:text-indigo-600 transition-colors"
                                    >
                                        <FontAwesomeIcon :icon="item.icon" />
                                    </div>
                                    {{ item.label }}
                                </button>
                                <Link
                                    v-else-if="item.method === 'delete'"
                                    :href="item.link"
                                    class="flex items-center w-full gap-3 px-4 py-2.5 hover:bg-white text-sm text-danger font-medium transition-all duration-150 ease-in-out group cursor-pointer"
                                    method="delete"
                                    as="button"
                                >
                                    <div
                                        class="w-5 flex justify-center opacity-80 group-hover:opacity-100"
                                    >
                                        <FontAwesomeIcon :icon="item.icon" />
                                    </div>
                                    {{ item.label }}
                                </Link>
                                <Link
                                    v-else
                                    :href="item.link"
                                    class="flex items-center gap-3 px-4 py-2.5 hover:bg-white text-sm text-neutral-700 font-medium transition-all duration-150 ease-in-out group"
                                    @click="showPanel = !showPanel"
                                >
                                    <div
                                        class="w-5 flex justify-center text-neutral-400 group-hover:text-indigo-600 transition-colors"
                                    >
                                        <FontAwesomeIcon :icon="item.icon" />
                                    </div>
                                    {{ item.label }}
                                </Link>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { faClose, faRightFromBracket, faUser } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { Link, usePage } from '@inertiajs/vue3'
import { computed, onBeforeMount, onMounted, ref } from 'vue'
import { usePopUpStore } from '@/store/popup'
import CockpitProfilePopUp from '@/Components/Cockpit/Auth/CockpitProfilePopUp.vue'

const auth = computed(() => usePage().props.auth)
const showPanel = ref(false)
const dropdownRef = ref(null)
const popUpStore = usePopUpStore()

const initials = computed(() => {
    const name = auth.value?.name || ''
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .substring(0, 2)
        .toUpperCase()
})

const togglePanel = () => {
    showPanel.value = !showPanel.value
}

const closePanel = () => {
    showPanel.value = false
}

const openProfilePopUp = () => {
    closePanel()
    popUpStore.open({
        title: 'Ubah Profil & Kata Sandi',
        subTitle: 'Cockpit Admin',
        size: 'md',
        component: CockpitProfilePopUp,
    })
}

const accountLinks = computed(() => [
    {
        label: 'Ubah Profil & Kata Sandi',
        icon: faUser,
        action: openProfilePopUp,
    },
    {
        label: 'Keluar',
        icon: faRightFromBracket,
        link: route().has('cockpit.logout') ? route('cockpit.logout') : '#',
        method: 'delete',
    },
])

const handleClickOutside = event => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        showPanel.value = false
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
})

onBeforeMount(() => {
    document.removeEventListener('click', handleClickOutside)
})
</script>
