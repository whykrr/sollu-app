<template>
    <div
        class="bg-white border border-slate-200 rounded-xl overflow-hidden hover:border-primary-400 hover:bg-slate-50/30 transition-all duration-150 cursor-pointer flex flex-col justify-between h-full group select-none"
        @click="$emit('click', service)"
    >
        <!-- Card Cover & Badges -->
        <div
            class="relative aspect-4/3 sm:aspect-square bg-slate-100 overflow-hidden flex items-center justify-center"
        >
            <img
                v-if="service.cover_image_url"
                :src="service.cover_image_url"
                :alt="service.name"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            />
            <div v-else class="text-slate-300 flex items-center justify-center">
                <FontAwesomeIcon :icon="faBellConcierge" class="text-2xl sm:text-3xl" />
            </div>

            <!-- Badges Overlay -->
            <div
                class="absolute top-2 left-2 right-2 flex items-center justify-between gap-1 pointer-events-none"
            >
                <span class="badge badge-info text-[10px] py-0.5 px-1.5 shadow-none">
                    Layanan
                </span>

                <!-- Status Badge -->
                <span
                    v-if="service.is_show"
                    class="badge badge-success text-[10px] py-0.5 px-1.5 shadow-none"
                >
                    Aktif
                </span>
                <span v-else class="badge badge-neutral-500 text-[10px] py-0.5 px-1.5 shadow-none">
                    Non-Aktif
                </span>
            </div>
        </div>

        <!-- Card Body -->
        <div class="p-2.5 flex-1 flex flex-col justify-between gap-2">
            <div class="space-y-1">
                <!-- Code & Category -->
                <div class="flex items-center gap-1.5 text-xs text-neutral-500">
                    <span v-if="service.code" class="font-mono text-neutral-400 text-[11px]">
                        #{{ service.code }}
                    </span>
                    <span v-if="service.code && service.category?.name" class="text-neutral-300"
                        >•</span
                    >
                    <span
                        v-if="service.category?.name"
                        class="truncate text-[11px] font-medium text-neutral-600"
                    >
                        {{ service.category.name }}
                    </span>
                </div>

                <!-- Service Name -->
                <h3
                    class="font-semibold text-neutral-800 text-sm line-clamp-2 leading-snug group-hover:text-primary-600 transition-colors"
                    :title="service.name"
                >
                    {{ service.name }}
                </h3>
            </div>

            <!-- Footer / Price & Actions -->
            <div
                class="pt-2 border-t border-slate-100 flex items-center justify-between gap-1 mt-auto"
            >
                <div class="font-bold text-sm text-neutral-900 truncate">
                    {{ formattedPrice }}
                </div>

                <div class="flex items-center gap-1 shrink-0">
                    <button
                        type="button"
                        class="btn btn-flat btn-xs text-neutral-600 hover:text-neutral-900 px-1.5 py-1"
                        title="Ubah Layanan"
                        @click.stop="$emit('edit', service)"
                    >
                        <FontAwesomeIcon :icon="faPencil" />
                    </button>
                    <button
                        type="button"
                        class="btn btn-flat btn-xs text-danger hover:bg-danger-50 px-1.5 py-1"
                        title="Hapus Layanan"
                        @click.stop="$emit('archive', service.id)"
                    >
                        <FontAwesomeIcon :icon="faTrash" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faPencil, faTrash, faBellConcierge } from '@fortawesome/free-solid-svg-icons'

const props = defineProps({
    service: {
        type: Object,
        required: true,
    },
    activeOutletId: {
        type: [String, Number],
        default: null,
    },
})

defineEmits(['click', 'edit', 'archive'])

const formattedPrice = computed(() => {
    if (!props.service.prices || props.service.prices.length === 0) return '-'

    let price = null
    if (props.activeOutletId) {
        price = props.service.prices.find(p => p.outlet_id === props.activeOutletId)
    }
    if (!price) {
        price = props.service.prices.find(p => !p.outlet_id)
    }
    if (!price) {
        price = props.service.prices[0]
    }

    return price
        ? new Intl.NumberFormat('id-ID', {
              style: 'currency',
              currency: 'IDR',
              maximumFractionDigits: 0,
          }).format(price.amount)
        : '-'
})
</script>
