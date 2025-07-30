<template>
    <transition name="slide-fade" mode="in-out">
        <aside
            v-if="props.isOpen"
            class="fixed -right-2 top-0 h-screen z-50 shadow-2xl bg-neutral-300/50 backdrop-blur-xl"
        >
            <div class="flex flex-col h-full p-2 w-100">
                <div class="flex flex-row justify-between items-center">
                    <div class="font-semibold text-2xl">Notifikasi</div>
                    <div class="text-xs text-main underline">
                        <a
                            href="#"
                            title="Tutup"
                            class="text-lg mx-2 text-gray-600"
                            @click="closeNotification"
                        >
                            <fa icon="fa-close" />
                        </a>
                    </div>
                </div>
                <div class="flex flex-wrap">
                    <Link class="btn btn-outline-neutral-200 btn-sm">
                        Semua</Link
                    >
                </div>
                <div class="flex-1">
                    <ol class="flex flex-col gap-1">
                        <li>
                            <div class="flex flex-col border-b">
                                <div class="text-xs font-semibold">
                                    Selamat Datang!
                                </div>
                                <div class="text-xs">
                                    Kami senang bisa mendampingi Anda dalam
                                    mengelola bisnis dengan lebih mudah, cepat,
                                    dan efisien.
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="flex flex-col">
                                <div class="text-sm font-semibold">
                                    Selamat Datang!
                                </div>
                                <div class="text-xs">
                                    Kami senang bisa mendampingi Anda dalam
                                    mengelola bisnis dengan lebih mudah, cepat,
                                    dan efisien.
                                </div>
                            </div>
                        </li>
                    </ol>
                </div>
                <div>
                    <div class="flex flex-row justify-between items-center">
                        <div>
                            <button
                                type="button"
                                class="btn btn-highlight-danger btn-sm"
                                @click="closeNotification"
                            >
                                Tutup
                            </button>
                        </div>
                        <div>
                            <button class="btn btn-info btn-sm">
                                <fa icon="fa-list-check" />
                                Tandai semua dibaca
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </transition>
</template>
<script setup>
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    isOpen: Boolean,
});

const emit = defineEmits(["close"]);

const closeNotification = () => {
    emit("close");
};
</script>
