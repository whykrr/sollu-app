<template>
    <div
        class="bg-white p-2.5 rounded-xl border border-neutral-200/70 flex flex-wrap items-center gap-2"
    >
        <!-- Search Input -->
        <FilterSearch
            :model-value="search"
            placeholder="Cari nama atau kode paket..."
            class="w-full sm:w-60"
            @update:model-value="$emit('update:search', $event)"
        />

        <!-- Status Filter Segmented -->
        <div class="flex items-center gap-1 bg-neutral-100 p-1 rounded-lg text-xs font-medium">
            <button
                type="button"
                class="px-2.5 py-1 rounded-md transition-colors cursor-pointer"
                :class="
                    status === 'all'
                        ? 'bg-white shadow-xs text-neutral-800 font-bold'
                        : 'text-neutral-500 hover:text-neutral-800'
                "
                @click="$emit('update:status', 'all')"
            >
                Semua
            </button>
            <button
                type="button"
                class="px-2.5 py-1 rounded-md transition-colors cursor-pointer"
                :class="
                    status === 'active'
                        ? 'bg-white shadow-xs text-success font-bold'
                        : 'text-neutral-500 hover:text-neutral-800'
                "
                @click="$emit('update:status', 'active')"
            >
                Aktif ({{ activeCount }})
            </button>
            <button
                type="button"
                class="px-2.5 py-1 rounded-md transition-colors cursor-pointer"
                :class="
                    status === 'inactive'
                        ? 'bg-white shadow-xs text-danger font-bold'
                        : 'text-neutral-500 hover:text-neutral-800'
                "
                @click="$emit('update:status', 'inactive')"
            >
                Nonaktif ({{ inactiveCount }})
            </button>
        </div>

        <!-- Visibility Filter Segmented -->
        <div class="flex items-center gap-1 bg-neutral-100 p-1 rounded-lg text-xs font-medium">
            <button
                type="button"
                class="px-2.5 py-1 rounded-md transition-colors cursor-pointer"
                :class="
                    visibility === 'all'
                        ? 'bg-white shadow-xs text-neutral-800 font-bold'
                        : 'text-neutral-500 hover:text-neutral-800'
                "
                @click="$emit('update:visibility', 'all')"
            >
                Semua Katalog
            </button>
            <button
                type="button"
                class="px-2.5 py-1 rounded-md transition-colors cursor-pointer"
                :class="
                    visibility === 'public'
                        ? 'bg-white shadow-xs text-sky-700 font-bold'
                        : 'text-neutral-500 hover:text-neutral-800'
                "
                @click="$emit('update:visibility', 'public')"
            >
                Publik ({{ publicCount }})
            </button>
            <button
                type="button"
                class="px-2.5 py-1 rounded-md transition-colors cursor-pointer"
                :class="
                    visibility === 'hidden'
                        ? 'bg-white shadow-xs text-amber-700 font-bold'
                        : 'text-neutral-500 hover:text-neutral-800'
                "
                @click="$emit('update:visibility', 'hidden')"
            >
                Tersembunyi ({{ hiddenCount }})
            </button>
            <button
                type="button"
                class="px-2.5 py-1 rounded-md transition-colors cursor-pointer"
                :class="
                    visibility === 'custom'
                        ? 'bg-white shadow-xs text-purple-700 font-bold'
                        : 'text-neutral-500 hover:text-neutral-800'
                "
                @click="$emit('update:visibility', 'custom')"
            >
                Custom Merchant ({{ customCount }})
            </button>
        </div>
    </div>
</template>

<script setup>
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'

defineProps({
    search: {
        type: String,
        default: '',
    },
    status: {
        type: String,
        default: 'all',
    },
    visibility: {
        type: String,
        default: 'all',
    },
    plansCount: {
        type: Number,
        default: 0,
    },
    activeCount: {
        type: Number,
        default: 0,
    },
    inactiveCount: {
        type: Number,
        default: 0,
    },
    publicCount: {
        type: Number,
        default: 0,
    },
    hiddenCount: {
        type: Number,
        default: 0,
    },
    customCount: {
        type: Number,
        default: 0,
    },
})

defineEmits(['update:search', 'update:status', 'update:visibility'])
</script>
