<template>
    <Modal
        :show="show"
        title="Atur Diskon Baris Item"
        size="max-w-md"
        :show-close="true"
        :close-on-backdrop="true"
        @close="handleClose"
    >
        <div class="space-y-4 text-left">
            <!-- Item Summary Header -->
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
                <div class="font-bold text-xs sm:text-sm text-slate-800 line-clamp-1">
                    {{ item?.product_name || 'Item Produk' }}
                </div>
                <div class="flex items-center justify-between text-xs text-slate-500">
                    <span>
                        {{ item?.qty || 1 }} {{ item?.uom_name || 'Pcs' }} ×
                        {{ formatCurrency(item?.price || 0) }}
                    </span>
                    <span class="font-semibold text-slate-700">
                        Gross: {{ formatCurrency(grossSubtotal) }}
                    </span>
                </div>
            </div>

            <!-- Discount Type Segmented Selector -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Tipe Diskon
                </label>
                <div
                    class="grid grid-cols-2 gap-2 bg-slate-100 p-1 rounded-lg border border-slate-200"
                >
                    <button
                        type="button"
                        class="py-1.5 px-3 text-xs font-medium rounded-md transition cursor-pointer text-center"
                        :class="
                            discountType === 'percentage'
                                ? 'bg-white text-slate-900 font-bold border border-slate-200 shadow-2xs'
                                : 'text-slate-600 hover:text-slate-900'
                        "
                        @click="discountType = 'percentage'"
                    >
                        Persentase (%)
                    </button>
                    <button
                        type="button"
                        class="py-1.5 px-3 text-xs font-medium rounded-md transition cursor-pointer text-center"
                        :class="
                            discountType === 'fixed'
                                ? 'bg-white text-slate-900 font-bold border border-slate-200 shadow-2xs'
                                : 'text-slate-600 hover:text-slate-900'
                        "
                        @click="discountType = 'fixed'"
                    >
                        Nominal Tetap (Rp)
                    </button>
                </div>
            </div>

            <!-- Discount Value Input -->
            <div>
                <NumberField
                    v-if="discountType === 'percentage'"
                    v-model="discountRate"
                    label="Persentase Diskon (%)"
                    placeholder="Contoh: 10"
                    suffix="%"
                    size="sm"
                    :min="0"
                    :max="100"
                />
                <NumberField
                    v-else
                    v-model="discountFixed"
                    label="Nominal Diskon (Rp)"
                    placeholder="0"
                    prefix="Rp"
                    size="sm"
                    :min="0"
                    :max="grossSubtotal"
                />
            </div>

            <!-- Financial Impact Preview -->
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 space-y-1.5 text-xs">
                <div class="flex justify-between items-center text-slate-600">
                    <span>Subtotal Kotor:</span>
                    <span class="font-medium text-slate-800">{{
                        formatCurrency(grossSubtotal)
                    }}</span>
                </div>
                <div class="flex justify-between items-center text-emerald-600 font-semibold">
                    <span>Potongan Diskon:</span>
                    <span>- {{ formatCurrency(calculatedDiscountAmount) }}</span>
                </div>
                <div
                    class="pt-1.5 border-t border-slate-200 flex justify-between items-center font-bold text-slate-900 text-xs sm:text-sm"
                >
                    <span>Subtotal Bersih:</span>
                    <span class="text-main">{{ formatCurrency(netSubtotal) }}</span>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <template #footer>
            <div class="flex items-center justify-between w-full">
                <button
                    v-if="currentDiscountAmount > 0"
                    type="button"
                    class="btn btn-outline-danger btn-sm h-[30px] text-xs inline-flex items-center gap-1 cursor-pointer"
                    @click="removeDiscount"
                >
                    <FontAwesomeIcon :icon="faTrash" class="text-[10px]" />
                    <span>Hapus Diskon</span>
                </button>
                <div v-else></div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm h-[30px]"
                        @click="handleClose"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        class="btn btn-main btn-sm h-[30px] inline-flex items-center gap-1.5"
                        @click="applyDiscount"
                    >
                        <FontAwesomeIcon :icon="faCheck" />
                        <span>Terapkan</span>
                    </button>
                </div>
            </div>
        </template>
    </Modal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCheck, faTrash } from '@fortawesome/free-solid-svg-icons'
import Modal from '@/Components/Notifications/Modal.vue'
import NumberField from '@/Components/Form/NumberField.vue'
import { formatIDR as formatCurrency } from '@/Composable/currency-format.js'

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    item: {
        type: Object,
        default: null,
    },
})

const emit = defineEmits(['close', 'apply'])

const discountType = ref('fixed') // 'fixed' | 'percentage'
const discountFixed = ref(0)
const discountRate = ref(0)

const grossSubtotal = computed(() => {
    if (!props.item) return 0
    return Math.max(0, Number(props.item.qty || 0) * Number(props.item.price || 0))
})

const currentDiscountAmount = computed(() => {
    return Number(props.item?.discount_amount || 0)
})

const calculatedDiscountAmount = computed(() => {
    if (discountType.value === 'percentage') {
        const rate = Math.min(100, Math.max(0, Number(discountRate.value || 0)))
        return Math.round((grossSubtotal.value * rate) / 100)
    }
    return Math.min(grossSubtotal.value, Math.max(0, Number(discountFixed.value || 0)))
})

const netSubtotal = computed(() => {
    return Math.max(0, grossSubtotal.value - calculatedDiscountAmount.value)
})

const syncFromProps = () => {
    if (!props.item) {
        discountType.value = 'fixed'
        discountFixed.value = 0
        discountRate.value = 0
        return
    }

    const currentAmount = Number(props.item.discount_amount || 0)
    if (props.item.discount_type === 'percentage' && props.item.discount_rate) {
        discountType.value = 'percentage'
        discountRate.value = Number(props.item.discount_rate)
        discountFixed.value = currentAmount
    } else {
        discountType.value = 'fixed'
        discountFixed.value = currentAmount
        discountRate.value =
            grossSubtotal.value > 0 ? Math.round((currentAmount / grossSubtotal.value) * 100) : 0
    }
}

watch(
    () => props.show,
    isOpen => {
        if (isOpen) {
            syncFromProps()
        }
    },
    { immediate: true }
)

const handleClose = () => {
    emit('close')
}

const removeDiscount = () => {
    emit('apply', {
        discount_amount: 0,
        discount_type: 'fixed',
        discount_rate: 0,
    })
    emit('close')
}

const applyDiscount = () => {
    emit('apply', {
        discount_amount: calculatedDiscountAmount.value,
        discount_type: discountType.value,
        discount_rate: discountType.value === 'percentage' ? Number(discountRate.value || 0) : 0,
    })
    emit('close')
}
</script>
