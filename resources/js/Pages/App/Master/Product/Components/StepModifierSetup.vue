<template>
    <div class="space-y-4">
        <FeatureLock :feature="$enums.FeatureEnum.PRODUCT_MODIFIERS" as="div" class="w-full">
            <div class="space-y-3 border border-slate-200 p-3 rounded-xl bg-white">
                <div class="pb-2 border-b border-slate-100">
                    <div class="font-semibold text-xs text-slate-800">
                        Opsi Tambahan & Modifier Produk
                    </div>
                    <div class="text-[11px] text-slate-500">
                        Pilih grup opsi tambahan yang dapat dipilih pelanggan saat memesan produk
                        ini di kasir (misal: Topping, Level Gula, Ukuran).
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-if="modifierGroupList.length === 0"
                    class="py-8 text-center text-slate-400 border border-dashed border-slate-200 rounded-lg"
                >
                    <FontAwesomeIcon :icon="faSliders" class="text-2xl text-slate-300 mb-2" />
                    <div class="text-xs font-medium text-slate-600">
                        Belum ada grup modifier terdaftar
                    </div>
                    <div class="text-[11px] text-slate-400 mt-0.5">
                        Anda dapat membuat grup opsi tambahan melalui menu Master Produk > Opsi
                        Tambahan.
                    </div>
                </div>

                <!-- Modifier Groups Grid -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div
                        v-for="group in modifierGroupList"
                        :key="group.id"
                        class="border rounded-xl p-3 transition-all cursor-pointer select-none"
                        :class="
                            isSelected(group.id)
                                ? 'border-main bg-main/5 shadow-xs ring-1 ring-main/20'
                                : 'border-slate-200 hover:border-slate-300 bg-white'
                        "
                        @click="toggleGroup(group.id)"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-xs text-slate-800 truncate">
                                        {{ group.name }}
                                    </span>
                                    <span
                                        class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium"
                                        :class="
                                            group.selection_type === 'single'
                                                ? 'bg-blue-50 text-blue-700 border border-blue-200'
                                                : 'bg-purple-50 text-purple-700 border border-purple-200'
                                        "
                                    >
                                        {{
                                            group.selection_type === 'single'
                                                ? 'Pilih Satu'
                                                : 'Pilih Banyak'
                                        }}
                                    </span>
                                    <span
                                        v-if="group.is_required"
                                        class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-50 text-amber-700 border border-amber-200"
                                    >
                                        Wajib
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-500 mt-1">
                                    {{ group.options?.length || 0 }} opsi tersedia
                                </div>
                            </div>
                            <div class="shrink-0 pt-0.5">
                                <input
                                    type="checkbox"
                                    :checked="isSelected(group.id)"
                                    class="rounded text-main focus:ring-main/20 cursor-pointer"
                                    @click.stop="toggleGroup(group.id)"
                                />
                            </div>
                        </div>

                        <!-- Options Preview Chips -->
                        <div
                            v-if="group.options && group.options.length > 0"
                            class="flex flex-wrap gap-1 mt-2.5 pt-2 border-t border-slate-100"
                        >
                            <span
                                v-for="opt in group.options.slice(0, 4)"
                                :key="opt.id"
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-600 font-medium"
                            >
                                {{ opt.name }}
                                <span v-if="opt.additional_price > 0" class="text-slate-400 ml-1">
                                    +{{ formatIDR(opt.additional_price) }}
                                </span>
                            </span>
                            <span
                                v-if="group.options.length > 4"
                                class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] bg-slate-50 text-slate-400"
                            >
                                +{{ group.options.length - 4 }} lainnya
                            </span>
                        </div>
                    </div>
                </div>

                <div v-if="form.errors.modifier_group_ids" class="text-xs text-red-500 mt-1">
                    {{ form.errors.modifier_group_ids }}
                </div>
            </div>
        </FeatureLock>
    </div>
</template>

<script setup>
import { inject, computed } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faSliders } from '@fortawesome/free-solid-svg-icons'
import FeatureLock from '@/Components/UI/FeatureLock.vue'
import { formatIDR } from '@/Composable/currency-format'

const form = inject('productForm')
const modifierGroups = inject(
    'modifierGroups',
    computed(() => [])
)

const modifierGroupList = computed(() => {
    const list =
        modifierGroups && modifierGroups.value !== undefined ? modifierGroups.value : modifierGroups
    return Array.isArray(list) ? list : []
})

const isSelected = groupId => {
    return (form.modifier_group_ids || []).includes(groupId)
}

const toggleGroup = groupId => {
    if (!Array.isArray(form.modifier_group_ids)) {
        form.modifier_group_ids = []
    }
    const idx = form.modifier_group_ids.indexOf(groupId)
    if (idx !== -1) {
        form.modifier_group_ids.splice(idx, 1)
    } else {
        form.modifier_group_ids.push(groupId)
    }
}
</script>
