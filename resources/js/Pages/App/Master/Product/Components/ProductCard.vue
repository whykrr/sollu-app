<template>
    <div
        class="bg-white border border-slate-200 rounded-xl overflow-hidden hover:border-primary-400 hover:bg-slate-50/30 transition-all duration-150 cursor-pointer flex flex-col justify-between h-full group select-none"
        @click="$emit('click', product)"
    >
        <!-- Card Cover & Badges -->
        <div
            class="relative aspect-4/3 sm:aspect-square bg-slate-100 overflow-hidden flex items-center justify-center"
        >
            <img
                v-if="product.cover_image_url"
                :src="product.cover_image_url"
                :alt="product.name"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            />
            <div v-else class="text-slate-300 flex items-center justify-center">
                <FontAwesomeIcon :icon="faImage" class="text-2xl sm:text-3xl" />
            </div>

            <!-- Badges Overlay -->
            <div
                class="absolute top-2 left-2 right-2 flex items-center justify-between gap-1 pointer-events-none"
            >
                <!-- Product Type -->
                <span
                    v-if="product.product_type === 'service'"
                    class="badge badge-info text-[10px] py-0.5 px-1.5 shadow-none"
                >
                    Layanan
                </span>
                <span
                    v-else-if="product.product_type === 'bundle'"
                    class="badge badge-warning text-[10px] py-0.5 px-1.5 shadow-none"
                >
                    Bundle
                </span>
                <span v-else class="badge badge-neutral-400 text-[10px] py-0.5 px-1.5 shadow-none">
                    Barang
                </span>

                <!-- Status Badge -->
                <span
                    v-if="product.is_show"
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
                    <span v-if="product.code" class="font-mono text-neutral-400 text-[11px]">
                        #{{ product.code }}
                    </span>
                    <span v-if="product.code && product.category?.name" class="text-neutral-300"
                        >•</span
                    >
                    <span
                        v-if="product.category?.name"
                        class="truncate text-[11px] font-medium text-neutral-600"
                    >
                        {{ product.category.name }}
                    </span>
                </div>

                <!-- Product Name -->
                <h3
                    class="font-semibold text-neutral-800 text-sm line-clamp-2 leading-snug group-hover:text-primary-600 transition-colors"
                    :title="product.name"
                >
                    {{ product.name }}
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
                        title="Ubah Produk"
                        @click.stop="$emit('edit', product)"
                    >
                        <FontAwesomeIcon :icon="faPencil" />
                    </button>
                    <button
                        type="button"
                        class="btn btn-flat btn-xs text-danger px-1.5 py-1"
                        title="Arsipkan"
                        @click.stop="$emit('archive', product.id)"
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
import { faImage, faPencil, faTrash } from '@fortawesome/free-solid-svg-icons'

const props = defineProps({
    product: {
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
    if (!props.product.prices || props.product.prices.length === 0) return '-'

    let price = null
    if (props.activeOutletId) {
        price = props.product.prices.find(p => p.outlet_id === props.activeOutletId)
    }
    if (!price) {
        price = props.product.prices.find(p => !p.outlet_id)
    }
    if (!price) {
        price = props.product.prices[0]
    }

    return price
        ? new Intl.NumberFormat('id-ID', {
              style: 'currency',
              currency: 'IDR',
          }).format(price.amount)
        : '-'
})
</script>
