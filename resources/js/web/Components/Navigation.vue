<template>
    <nav class="w-full drop-shadow-md z-10 sticky top-0">
        <div
            class="lg:hidden min-h-[75px] absolute left-0 top-0 px-2 md:px-8 flex items-center"
            @click="toggleNav"
        >
            <fa icon="fa-bars" class="text-2xl text-web-main"></fa>
        </div>
        <div
            class="min-h-[75px] flex flex-row px-2 md:px-8 xl:px-16 bg-white items-center md:gap-2 xl:gap-4"
        >
            <div class="hidden lg:block">
                <img
                    :src="'/storage/' + settings.icon"
                    alt="Logo"
                    class="w-[60px] h-[60px]"
                />
            </div>
            <div
                class="md:text-lg xl:text-2xl font-semibold text-web-main text-center grow lg:grow-0"
            >
                SMAN 2 TEGINENENG
            </div>
            <div class="grow hidden lg:block"></div>
            <div
                class="grid grid-flow-row lg:grid-flow-col gap-4 md:gap-6 xl:gap-8 font-semibold text-sm text-gray-700 absolute lg:static bg-white w-full lg:w-auto top-0 p-4 lg:p-0 transition-all ease-in"
                :class="{ 'left-0': navOpen, '-left-[100%]': !navOpen }"
            >
                <div class="block lg:hidden">
                    <button type="button" @click="toggleNav" class="text-xl">
                        <fa icon="fa-close"></fa>
                    </button>
                </div>
                <div>
                    <Link
                        :href="route('home')"
                        @click="toggleNav"
                        class="hover:text-web-main py-1 hover:"
                        :class="{
                            'text-web-main border-b-2 border-web-main':
                                navActive == 'home',
                        }"
                        >Beranda
                    </Link>
                </div>
                <div>
                    <div class="group relative">
                        <Link
                            href="#"
                            class="hover:text-web-main group-hover:text-web-main"
                            :class="{
                                'text-web-main border-b-2 border-web-main':
                                    navActive == 'profile',
                            }"
                        >
                            Profil
                        </Link>
                        <fa
                            class="ml-2 group-hover:rotate-180 transition-transform group-hover:text-web-main"
                            icon="fa-chevron-down"
                        />
                        <div
                            class="flex flex-col h-0 overflow-hidden group-hover:h-auto invisible group-hover:visible md:absolute min-w-52 bg-white md:drop-shadow-lg md:rounded md:border border-gray-200"
                        >
                            <Link
                                :href="route('profile.index')"
                                @click="toggleNav"
                                class="hover:bg-gray-200 px-4 py-2"
                            >
                                Profil Sekolah
                            </Link>
                            <Link
                                :href="route('profile.visimisi')"
                                @click="toggleNav"
                                class="hover:bg-gray-200 px-4 py-2"
                            >
                                Visi dan Misi
                            </Link>
                            <Link
                                :href="route('profile.teachers')"
                                @click="toggleNav"
                                class="hover:bg-gray-200 px-4 py-2"
                            >
                                Jajaran Guru dan Karyawan
                            </Link>
                        </div>
                    </div>
                </div>
                <div>
                    <Link
                        :href="route('news.index')"
                        @click="toggleNav"
                        class="hover:text-web-main"
                        :class="{
                            'text-web-main border-b-2 border-web-main':
                                navActive == 'news',
                        }"
                    >
                        Berita
                    </Link>
                </div>
                <div>
                    <Link
                        :href="route('announcements.index')"
                        @click="toggleNav"
                        class="hover:text-web-main"
                        :class="{
                            'text-web-main border-b-2 border-web-main':
                                navActive == 'announcements',
                        }"
                    >
                        Pengumuman
                    </Link>
                </div>
                <div>
                    <Link
                        :href="route('galleries.index')"
                        @click="toggleNav"
                        class="hover:text-web-main"
                        :class="{
                            'text-web-main border-b-2 border-web-main':
                                navActive == 'galleries',
                        }"
                    >
                        Galeri
                    </Link>
                </div>
                <div>
                    <Link
                        :href="route('contact.index')"
                        @click="toggleNav"
                        class="hover:text-web-main"
                        :class="{
                            'text-web-main border-b-2 border-web-main':
                                navActive == 'contact',
                        }"
                    >
                        Kontak Sekolah
                    </Link>
                </div>
                <div>
                    <a
                        :href="others['tautan-spmb']"
                        target="_blank"
                        class="border border-web-main text-web-main rounded px-3 py-1 hover:text-white hover:bg-web-main"
                        >SPMB</a
                    >
                </div>
            </div>
        </div>
    </nav>
</template>
<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const page = usePage();
const settings = page.props.settings;
const others = page.props.others;
const navActive = computed(() => page.props.navActive);
const navOpen = ref(false);

const toggleNav = () => {
    navOpen.value = !navOpen.value;
};
</script>
