<template>
    <div
        class="bg-white rounded-xl border border-slate-200 p-4 sm:p-5 flex flex-col justify-between transition-all duration-150 hover:border-slate-300 hover:bg-slate-50/50 cursor-pointer"
        @click="$emit('click', outlet)"
    >
        <div>
            <!-- Header Kartu -->
            <div class="flex items-start justify-between gap-2 mb-3">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div
                        class="size-9 rounded-lg flex items-center justify-center shrink-0"
                        :class="
                            outlet.is_main_outlet
                                ? 'bg-amber-100 text-amber-600'
                                : 'bg-main/10 text-main'
                        "
                    >
                        <FontAwesomeIcon :icon="faStore" />
                    </div>
                    <div class="min-w-0">
                        <h4
                            class="font-semibold text-slate-800 text-sm leading-snug truncate"
                            :title="outlet.name"
                        >
                            {{ outlet.name }}
                        </h4>
                        <span
                            v-if="outlet.is_main_outlet"
                            class="text-xs font-medium text-amber-600 flex items-center gap-1"
                        >
                            <FontAwesomeIcon :icon="faStar" class="text-[10px]" />
                            <span>Outlet Utama</span>
                        </span>
                        <span v-else class="text-xs text-slate-500">Cabang</span>
                    </div>
                </div>
                <span
                    v-if="outlet.is_active"
                    class="badge badge-success text-[11px] font-semibold shrink-0 inline-flex items-center gap-1"
                >
                    <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Aktif
                </span>
                <span
                    v-else
                    class="badge badge-danger text-[11px] font-semibold shrink-0 inline-flex items-center gap-1"
                >
                    <span class="size-1.5 rounded-full bg-rose-500"></span>
                    Nonaktif
                </span>
            </div>

            <!-- Metadata Info -->
            <div
                class="space-y-1.5 text-xs text-slate-600 py-2 border-t border-b border-slate-100 my-2.5"
            >
                <div class="flex justify-between gap-2">
                    <span class="text-slate-400 shrink-0">Alamat:</span>
                    <span
                        class="text-slate-700 text-right line-clamp-2"
                        :title="outlet.address || '-'"
                    >
                        {{ outlet.address || '-' }}
                    </span>
                </div>
                <div class="flex justify-between gap-2">
                    <span class="text-slate-400 shrink-0">Telepon:</span>
                    <span class="text-slate-700 font-mono">
                        {{ outlet.phone || '-' }}
                    </span>
                </div>
                <div class="flex justify-between gap-2">
                    <span class="text-slate-400 shrink-0">Email:</span>
                    <span
                        class="text-slate-700 truncate max-w-[180px]"
                        :title="outlet.email || '-'"
                    >
                        {{ outlet.email || '-' }}
                    </span>
                </div>
                <div class="flex justify-between gap-2">
                    <span class="text-slate-400 shrink-0">Dibuat:</span>
                    <span class="text-slate-700">
                        {{ formatDateTimeSimple(outlet.created_at) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Footer Tombol Aksi -->
        <div class="flex items-center justify-between gap-2 pt-2" @click.stop>
            <!-- Left: Main Outlet Status / Action -->
            <span
                v-if="outlet.is_main_outlet"
                class="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200/80 px-2.5 py-1.5 rounded-lg"
            >
                <FontAwesomeIcon :icon="faStar" class="text-[10px]" />
                <span>Outlet Utama</span>
            </span>
            <button
                v-else
                type="button"
                class="btn btn-outline-main btn-xs rounded-lg px-2.5 py-1.5 flex items-center gap-1.5 transition-colors cursor-pointer"
                :disabled="!outlet.is_active"
                :title="
                    !outlet.is_active
                        ? 'Aktifkan outlet terlebih dahulu untuk menjadikannya outlet utama'
                        : 'Jadikan Outlet Utama'
                "
                @click.stop="$emit('set-main', outlet)"
            >
                <FontAwesomeIcon :icon="faStar" class="text-[10px]" />
                <span>Jadikan Utama</span>
            </button>

            <!-- Right: Edit & Status actions -->
            <div class="flex items-center gap-1">
                <button
                    type="button"
                    class="btn btn-highlight-main btn-xs rounded-lg cursor-pointer"
                    title="Ubah Data Outlet"
                    @click.stop="$emit('edit', outlet)"
                >
                    <FontAwesomeIcon :icon="faPencil" />
                </button>

                <template v-if="!outlet.is_main_outlet">
                    <button
                        v-if="outlet.is_active"
                        type="button"
                        class="btn btn-highlight-danger btn-xs rounded-lg cursor-pointer"
                        title="Nonaktifkan Outlet"
                        @click.stop="$emit('disable', outlet.id)"
                    >
                        <FontAwesomeIcon :icon="faToggleOff" />
                    </button>
                    <button
                        v-else
                        type="button"
                        class="btn btn-highlight-success btn-xs rounded-lg cursor-pointer"
                        title="Aktifkan Outlet"
                        @click.stop="$emit('enable', outlet.id)"
                    >
                        <FontAwesomeIcon :icon="faToggleOn" />
                    </button>
                </template>
            </div>
        </div>
    </div>
</template>

<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faPencil,
    faStar,
    faStore,
    faToggleOff,
    faToggleOn,
} from '@fortawesome/free-solid-svg-icons'
import { formatDateTimeSimple } from '@/Composable/date'

defineProps({
    outlet: {
        type: Object,
        required: true,
    },
})

defineEmits(['click', 'edit', 'set-main', 'disable', 'enable'])
</script>
