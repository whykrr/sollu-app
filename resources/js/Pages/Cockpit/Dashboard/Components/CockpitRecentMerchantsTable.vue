<template>
    <div
        class="flex flex-col justify-between gap-2 p-3 bg-white rounded-lg border border-neutral-200/80 shadow-xs"
    >
        <div class="flex items-center justify-between">
            <div>
                <h3
                    class="text-sm sm:text-base font-bold text-neutral-800 flex items-center gap-1.5"
                >
                    <FontAwesomeIcon :icon="faStore" class="text-main text-xs" />
                    Merchant Baru Mendaftar
                </h3>
                <p class="text-xs text-neutral-500">Bisnis yang baru bergabung di platform</p>
            </div>
            <Link
                :href="route('cockpit.merchants.index')"
                class="text-xs text-main hover:underline font-medium flex items-center gap-1"
            >
                Lihat Semua
                <FontAwesomeIcon :icon="faArrowRight" class="text-[10px]" />
            </Link>
        </div>

        <div v-if="recentMerchants.length === 0" class="py-8 text-center text-neutral-400 text-xs">
            Belum ada merchant baru yang terdaftar.
        </div>

        <div v-else class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-neutral-100 text-neutral-400 font-semibold">
                        <th class="pb-2">Nama Bisnis & Pemilik</th>
                        <th class="pb-2">Paket / Status</th>
                        <th class="pb-2">Cabang</th>
                        <th class="pb-2">Terdaftar</th>
                        <th class="pb-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-50">
                    <tr
                        v-for="item in recentMerchants"
                        :key="item.id"
                        class="hover:bg-neutral-50/80 transition-colors"
                    >
                        <td class="py-2">
                            <div class="font-bold text-neutral-800 flex items-center gap-1">
                                <span>{{ item.name }}</span>
                                <span
                                    v-if="item.business_type !== '-'"
                                    class="px-1 py-0.2 text-[9px] rounded font-semibold bg-neutral-100 text-neutral-600 border border-neutral-200"
                                >
                                    {{ item.business_type }}
                                </span>
                            </div>
                            <div class="text-[11px] text-neutral-500">{{ item.owner_name }}</div>
                        </td>
                        <td class="py-2">
                            <span
                                class="px-1.5 py-0.5 text-[10px] rounded-full font-semibold"
                                :class="
                                    item.is_trial
                                        ? 'bg-amber-100 text-amber-800'
                                        : 'bg-indigo-100 text-indigo-700'
                                "
                            >
                                {{ item.plan_name }}
                            </span>
                        </td>
                        <td class="py-2 text-neutral-600">{{ item.outlets_count }} Cabang</td>
                        <td class="py-2 text-neutral-500">
                            {{ item.created_at_formatted }}
                        </td>
                        <td class="py-2 text-right">
                            <button
                                type="button"
                                class="btn btn-outline-main btn-xs text-[10px] px-2 py-0.5"
                                @click="openMerchantDetail(item.id)"
                            >
                                <FontAwesomeIcon :icon="faEye" class="mr-1" />
                                Detail
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faStore, faArrowRight, faEye } from '@fortawesome/free-solid-svg-icons'
import { usePopUpStore } from '@/store/popup'
import BusinessDetailPopUp from '@/Pages/Cockpit/Business/Components/BusinessDetailPopUp.vue'

defineProps({
    recentMerchants: {
        type: Array,
        default: () => [],
    },
})

const popUpStore = usePopUpStore()

const openMerchantDetail = businessId => {
    popUpStore.open({
        title: 'Detail Bisnis Merchant',
        size: '2xl',
        component: BusinessDetailPopUp,
        props: { businessId },
    })
}
</script>
