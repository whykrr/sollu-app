<template>
    <TopBarDropdown
        title="Informasi Usaha"
        width-class="sm:w-96"
        align="right"
        @open="onDropdownOpen"
    >
        <template #trigger="{ toggle }">
            <a
                href="#"
                class="flex flex-row items-center justify-center gap-2 h-9 w-9 sm:w-auto sm:h-10 p-0 sm:pl-1 sm:pr-3 bg-white hover:bg-neutral-50 rounded-full border border-neutral-200 transition-all duration-150 ease-in-out active:scale-95 cursor-pointer"
                title="Informasi Usaha"
                @click.prevent="toggle"
            >
                <div
                    class="rounded-full w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center bg-main/10 text-main text-xs sm:text-sm shrink-0"
                >
                    <FontAwesomeIcon :icon="faShop" />
                </div>
                <span class="text-sm font-medium text-neutral-800 hidden lg:inline">{{
                    auth.business.name
                }}</span>
                <span class="text-sm font-medium text-neutral-800 hidden sm:inline lg:hidden">{{
                    initials
                }}</span>
            </a>
        </template>

        <template #default="{ close }">
            <div
                class="bg-neutral-50 border border-neutral-100 rounded-xl overflow-hidden p-3 space-y-3"
            >
                <div class="flex flex-row gap-3 items-center">
                    <div>
                        <div
                            class="w-16 h-16 aspect-square bg-white border border-neutral-200 rounded-lg overflow-hidden p-1"
                        >
                            <div
                                v-if="!auth.business?.logo_url && !auth.business?.logo"
                                class="flex w-full h-full items-center justify-center bg-secondary/5 rounded"
                            >
                                <FontAwesomeIcon :icon="faShop" class="text-secondary text-xl" />
                            </div>
                            <img
                                v-else
                                :src="auth.business?.logo_url"
                                alt="Logo"
                                class="w-full h-full object-contain rounded"
                            />
                        </div>
                    </div>

                    <div class="text-lg font-medium text-neutral-800 leading-tight">
                        {{ auth.business.name }}
                    </div>
                </div>
                <div class="h-px bg-neutral-200 w-full" />
                <div v-if="!businessInfo" class="grid grid-flow-row gap-2 animate-pulse">
                    <div class="placeholder w-[50%] mb-0 h-4" />
                    <div class="placeholder w-[75%] mb-0 h-4" />
                    <div class="placeholder w-[75%] mb-0 h-4" />
                    <div class="placeholder w-[75%] mb-0 h-4" />
                </div>
                <div v-else class="grid grid-flow-row gap-2 text-sm text-neutral-600">
                    <div class="flex flex-row justify-between items-center">
                        <div class="font-medium text-neutral-500">Jenis Usaha</div>
                        <div class="font-medium text-neutral-800">
                            {{ businessInfo.business_type }}
                        </div>
                    </div>
                    <div class="flex flex-row justify-between items-center">
                        <div class="font-medium text-neutral-500">Langganan</div>
                        <div class="font-medium text-neutral-800">
                            {{ businessInfo.plan_name }}
                        </div>
                    </div>
                    <div class="flex flex-row justify-between items-center">
                        <div class="font-medium text-neutral-500">Aktif Sampai</div>
                        <div class="font-medium text-neutral-800">
                            <template v-if="businessInfo.expired_at">
                                {{ formatDateID(businessInfo.expired_at) }}
                            </template>
                            <template v-else> Selamanya </template>
                        </div>
                    </div>
                    <div class="flex flex-row justify-between items-center">
                        <div class="font-medium text-neutral-500">Jumlah Outlet</div>
                        <div class="font-medium text-neutral-800">
                            {{ businessInfo.outlet_count }}
                            Outlet
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-neutral-50 border border-neutral-100 rounded-xl overflow-hidden mt-1">
                <ol>
                    <li
                        v-for="(item, index) in businessLinks"
                        :key="index"
                        class="border-b border-neutral-100 last:border-0"
                    >
                        <Link
                            :href="item.link"
                            class="flex items-center gap-3 px-4 py-2.5 hover:bg-white text-sm text-neutral-700 font-medium transition-all duration-150 ease-in-out group"
                            :method="item.method"
                            @click="close"
                        >
                            <div
                                class="w-6 flex justify-center text-neutral-400 group-hover:text-main transition-colors"
                            >
                                <FontAwesomeIcon :icon="item.icon" />
                            </div>
                            {{ item.label }}
                        </Link>
                    </li>
                </ol>
            </div>
        </template>
    </TopBarDropdown>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCog, faCreditCard, faShop } from '@fortawesome/free-solid-svg-icons'
import { formatDateID } from '@/Composable/date'
import TopBarDropdown from '@/Components/Layout/Header/TopBarDropdown.vue'

const businessInfo = ref(null)
const page = usePage()
const auth = computed(() => page.props.auth)

const initials = computed(() => {
    const name = page.props.auth?.business?.name || ''
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .substring(0, 2)
        .toUpperCase()
})

const businessLinks = [
    {
        label: 'Langganan & Tagihan',
        icon: faCreditCard,
        link: route('settings.billing.index'),
        method: 'get',
    },
    {
        label: 'Pengaturan Usaha',
        icon: faCog,
        link: route('settings.business.detail'),
        method: 'get',
    },
]

const fetchBusinessInfo = async () => {
    try {
        const response = await axios.get(route('api.internal.business-info'))
        businessInfo.value = response.data
    } catch (error) {
        console.error('Failed to fetch business info:', error)
    }
}

const onDropdownOpen = () => {
    businessInfo.value = null
    fetchBusinessInfo()
}
</script>
