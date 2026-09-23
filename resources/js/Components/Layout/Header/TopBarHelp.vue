<template>
    <TopBarDropdown title="Pusat Bantuan & Panduan" width-class="w-80 sm:w-88" align="right">
        <template #trigger="{ toggle }">
            <a
                href="#"
                class="relative flex items-center justify-center w-9 h-9 sm:w-10 sm:h-10 rounded-full text-slate-700 hover:bg-neutral-100 transition-all duration-200 active:scale-95 cursor-pointer"
                title="Pusat Bantuan"
                @click.prevent="toggle"
            >
                <FontAwesomeIcon :icon="faCircleQuestion" class="text-[1.1rem] sm:text-[1.15rem]" />
            </a>
        </template>

        <template #default="{ close }">
            <div class="space-y-2">
                <!-- Info Banner -->
                <div
                    class="bg-slate-50 border border-slate-200/80 rounded-xl p-3 flex items-start gap-3"
                >
                    <div
                        class="w-8 h-8 rounded-lg bg-main/10 text-main flex items-center justify-center shrink-0 mt-0.5"
                    >
                        <FontAwesomeIcon :icon="faLifeRing" class="text-sm" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-semibold text-slate-800">
                            Butuh bantuan tokomu?
                        </div>
                        <div class="text-[11px] text-slate-500 leading-relaxed mt-0.5">
                            Pelajari panduan fitur atau hubungi tim bantuan Sollu jika ada kendala.
                        </div>
                    </div>
                </div>

                <!-- Navigation Action Links -->
                <div class="bg-neutral-50 rounded-xl overflow-hidden border border-neutral-100">
                    <ol>
                        <li class="border-b border-neutral-100 last:border-0">
                            <a
                                :href="helpCenterUrl"
                                :target="isExternalLink ? '_blank' : undefined"
                                :rel="isExternalLink ? 'noopener noreferrer' : undefined"
                                class="flex items-center gap-3 px-3.5 py-2.5 hover:bg-white text-sm text-neutral-700 font-medium transition-all duration-150 ease-in-out group"
                                @click="close"
                            >
                                <div
                                    class="w-5 flex justify-center text-neutral-400 group-hover:text-main transition-colors shrink-0"
                                >
                                    <FontAwesomeIcon :icon="faBookOpen" />
                                </div>
                                <div class="grow min-w-0">
                                    <div class="text-xs font-semibold text-slate-800">
                                        Dokumentasi & Panduan
                                    </div>
                                    <div class="text-[11px] text-slate-500 truncate">
                                        Petunjuk penggunaan modul & fitur
                                    </div>
                                </div>
                                <FontAwesomeIcon
                                    v-if="isExternalLink"
                                    :icon="faArrowUpRightFromSquare"
                                    class="text-[11px] text-neutral-400 group-hover:text-neutral-600 transition-colors"
                                />
                            </a>
                        </li>

                        <li class="border-b border-neutral-100 last:border-0">
                            <a
                                :href="supportWaUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex items-center gap-3 px-3.5 py-2.5 hover:bg-white text-sm text-neutral-700 font-medium transition-all duration-150 ease-in-out group"
                                @click="close"
                            >
                                <div
                                    class="w-5 flex justify-center text-emerald-600 group-hover:text-emerald-700 transition-colors shrink-0"
                                >
                                    <FontAwesomeIcon :icon="faWhatsapp" class="text-base" />
                                </div>
                                <div class="grow min-w-0">
                                    <div class="text-xs font-semibold text-slate-800">
                                        WhatsApp Dukungan CS
                                    </div>
                                    <div class="text-[11px] text-slate-500 truncate">
                                        Konsultasi langsung via chat WhatsApp
                                    </div>
                                </div>
                                <FontAwesomeIcon
                                    :icon="faArrowUpRightFromSquare"
                                    class="text-[11px] text-neutral-400 group-hover:text-neutral-600 transition-colors"
                                />
                            </a>
                        </li>
                    </ol>
                </div>

                <!-- Footer System Info -->
                <div class="pt-1 px-1 flex items-center justify-between text-[10px] text-slate-400">
                    <span>Sollu App v1.0</span>
                    <span class="inline-flex items-center gap-1 text-emerald-600 font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Sistem Normal
                    </span>
                </div>
            </div>
        </template>
    </TopBarDropdown>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faCircleQuestion,
    faLifeRing,
    faBookOpen,
    faArrowUpRightFromSquare,
} from '@fortawesome/free-solid-svg-icons'
import { faWhatsapp } from '@fortawesome/free-brands-svg-icons'
import TopBarDropdown from '@/Components/Layout/Header/TopBarDropdown.vue'

const page = usePage()

const helpCenterUrl = computed(() => {
    const url = page.props.app?.help_center_url
    return url && url !== '#' ? url : 'https://help.sollu.id'
})

const isExternalLink = computed(() => {
    return Boolean(helpCenterUrl.value && helpCenterUrl.value !== '#')
})

const supportWaUrl = computed(() => {
    const waNumber = page.props.app?.whatsapp_support_number
    if (waNumber) {
        return `https://wa.me/${waNumber}?text=Halo%20Tim%20Sollu,%20saya%20butuh%20bantuan`
    }
    if (helpCenterUrl.value?.includes('wa.me')) {
        return helpCenterUrl.value
    }
    return 'https://wa.me/628123456789'
})
</script>
