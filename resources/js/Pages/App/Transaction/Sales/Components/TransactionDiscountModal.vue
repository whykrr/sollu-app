<template>
    <Modal
        :show="show"
        title="Diskon & Promosi Transaksi"
        size="max-w-2xl"
        :show-close="true"
        :close-on-backdrop="true"
        @close="handleClose"
    >
        <div class="space-y-4 text-left">
            <!-- Top Segmented Tabs: Promo & Voucher vs Diskon Manual -->
            <div class="flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200">
                <button
                    type="button"
                    class="flex-1 py-1.5 px-3 text-xs font-semibold rounded-lg transition cursor-pointer text-center flex items-center justify-center gap-2"
                    :class="
                        activeTab === 'promo'
                            ? 'bg-white text-slate-900 shadow-2xs border border-slate-200'
                            : 'text-slate-500 hover:text-slate-800'
                    "
                    @click="activeTab = 'promo'"
                >
                    <FontAwesomeIcon :icon="faTags" class="text-main" />
                    <span>Katalog Promo & Voucher</span>
                    <span
                        v-if="promotions.length > 0"
                        class="px-1.5 py-0.2 rounded-full bg-slate-200 text-slate-700 text-[10px]"
                    >
                        {{ promotions.length }}
                    </span>
                </button>
                <button
                    type="button"
                    class="flex-1 py-1.5 px-3 text-xs font-semibold rounded-lg transition cursor-pointer text-center flex items-center justify-center gap-2"
                    :class="
                        activeTab === 'manual'
                            ? 'bg-white text-slate-900 shadow-2xs border border-slate-200'
                            : 'text-slate-500 hover:text-slate-800'
                    "
                    @click="activeTab = 'manual'"
                >
                    <FontAwesomeIcon :icon="faPercent" class="text-indigo-600" />
                    <span>Diskon Manual Transaksi</span>
                </button>
            </div>

            <!-- TAB 1: KATALOG PROMO & VOUCHER -->
            <div v-if="activeTab === 'promo'" class="space-y-3.5">
                <!-- Voucher Code Input Bar -->
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <div class="flex items-center gap-2">
                        <div class="flex-1 relative">
                            <input
                                v-model="voucherInput"
                                type="text"
                                placeholder="Masukkan kode promo / voucher (cth: PROMOB2B)..."
                                class="form sm w-full uppercase font-mono tracking-wider text-xs"
                                @keyup.enter="applyVoucherCode"
                            />
                        </div>
                        <button
                            type="button"
                            class="btn btn-main btn-sm h-[30px] inline-flex items-center gap-1.5 cursor-pointer shrink-0"
                            :disabled="!voucherInput?.trim() || isCheckingVoucher"
                            @click="applyVoucherCode"
                        >
                            <FontAwesomeIcon
                                :icon="isCheckingVoucher ? faSpinner : faCheck"
                                :class="{ 'animate-spin': isCheckingVoucher }"
                            />
                            <span>Terapkan</span>
                        </button>
                    </div>
                    <div
                        v-if="voucherFeedback.message"
                        class="text-xs"
                        :class="
                            voucherFeedback.isError
                                ? 'text-rose-600'
                                : 'text-emerald-600 font-medium'
                        "
                    >
                        {{ voucherFeedback.message }}
                    </div>
                </div>

                <!-- Promo List Header & Loading -->
                <div class="flex items-center justify-between text-xs px-0.5">
                    <span class="font-bold text-slate-700">Daftar Promo Aktif</span>
                    <span class="text-slate-500">
                        Subtotal Item Saat Ini:
                        <strong class="text-slate-800">{{ formatCurrency(subtotal) }}</strong>
                    </span>
                </div>

                <!-- Loading State -->
                <div
                    v-if="isLoadingPromos"
                    class="py-10 text-center text-xs text-slate-400 space-y-2"
                >
                    <FontAwesomeIcon :icon="faSpinner" class="animate-spin text-main text-base" />
                    <div>Memuat promosi yang tersedia...</div>
                </div>

                <!-- Empty State -->
                <div
                    v-else-if="promotions.length === 0"
                    class="py-8 text-center text-xs text-slate-400 border border-slate-200 rounded-xl bg-slate-50/50"
                >
                    <FontAwesomeIcon :icon="faTags" class="text-2xl text-slate-300 mb-1" />
                    <div class="font-medium text-slate-600">
                        Tidak ada promo transaksi yang aktif saat ini.
                    </div>
                    <div class="text-[11px] text-slate-400">
                        Anda tetap dapat menggunakan tab Diskon Manual.
                    </div>
                </div>

                <!-- Promo Cards List -->
                <div v-else class="space-y-2 max-h-[280px] overflow-y-auto pr-1">
                    <div
                        v-for="promo in promotions"
                        :key="promo.id"
                        class="p-3 rounded-xl border transition-all"
                        :class="[
                            isPromoSelected(promo)
                                ? 'bg-emerald-50/50 border-emerald-300 ring-1 ring-emerald-200'
                                : isPromoEligible(promo)
                                  ? 'bg-white border-slate-200 hover:border-slate-300'
                                  : 'bg-slate-50/70 border-slate-200 opacity-75',
                        ]"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="space-y-1 min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-bold text-xs sm:text-sm text-slate-800">
                                        {{ promo.name }}
                                    </span>
                                    <span
                                        class="px-1.5 py-0.2 rounded text-[10px] font-semibold"
                                        :class="
                                            promo.application_mode === 'automatic'
                                                ? 'bg-blue-100 text-blue-700'
                                                : 'bg-amber-100 text-amber-700 font-mono'
                                        "
                                    >
                                        {{
                                            promo.application_mode === 'automatic'
                                                ? 'Otomatis'
                                                : `Voucher: ${promo.promo_code}`
                                        }}
                                    </span>
                                    <span
                                        v-if="isPromoSelected(promo)"
                                        class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700"
                                    >
                                        ✓ Digunakan
                                    </span>
                                </div>

                                <div class="text-xs text-slate-600">
                                    <span
                                        v-if="promo.discount_type === 'percentage'"
                                        class="font-semibold text-emerald-600"
                                    >
                                        Diskon {{ promo.discount_value }}%
                                        <template v-if="promo.max_discount_amount">
                                            (Maks. {{ formatCurrency(promo.max_discount_amount) }})
                                        </template>
                                    </span>
                                    <span v-else class="font-semibold text-emerald-600">
                                        Potongan {{ formatCurrency(promo.discount_value) }}
                                    </span>
                                    <span v-if="promo.description" class="text-slate-400">
                                        — {{ promo.description }}</span
                                    >
                                </div>

                                <!-- Eligibility Condition Details -->
                                <div class="text-[11px] text-slate-500 space-y-0.5 pt-0.5">
                                    <div
                                        v-if="promo.min_subtotal > 0"
                                        class="flex items-center gap-1.5"
                                    >
                                        <span
                                            >Min. Belanja:
                                            {{ formatCurrency(promo.min_subtotal) }}</span
                                        >
                                        <span
                                            v-if="subtotal < promo.min_subtotal"
                                            class="text-rose-500 font-medium"
                                        >
                                            (Kurang
                                            {{ formatCurrency(promo.min_subtotal - subtotal) }})
                                        </span>
                                        <span v-else class="text-emerald-600 font-medium"
                                            >✓ Terpenuhi</span
                                        >
                                    </div>
                                    <div
                                        v-if="promo.min_quantity > 1"
                                        class="flex items-center gap-1.5"
                                    >
                                        <span>Min. Kuantitas: {{ promo.min_quantity }} item</span>
                                        <span
                                            v-if="totalQty < promo.min_quantity"
                                            class="text-rose-500 font-medium"
                                        >
                                            (Kurang {{ promo.min_quantity - totalQty }} item)
                                        </span>
                                        <span v-else class="text-emerald-600 font-medium"
                                            >✓ Terpenuhi</span
                                        >
                                    </div>
                                </div>
                            </div>

                            <!-- Action Button: Gunakan -->
                            <div class="shrink-0 pt-1">
                                <button
                                    v-if="isPromoSelected(promo)"
                                    type="button"
                                    class="btn btn-outline-danger btn-sm h-[28px] text-[11px] px-2.5 cursor-pointer"
                                    @click="removeSelectedPromo"
                                >
                                    Hapus
                                </button>
                                <button
                                    v-else
                                    type="button"
                                    class="btn btn-sm h-[28px] text-[11px] px-3 inline-flex items-center gap-1"
                                    :class="
                                        isPromoEligible(promo)
                                            ? 'btn-main cursor-pointer'
                                            : 'btn-outline-secondary opacity-50 cursor-not-allowed'
                                    "
                                    :disabled="!isPromoEligible(promo)"
                                    @click="selectPromo(promo)"
                                >
                                    <FontAwesomeIcon
                                        :icon="isPromoEligible(promo) ? faCheck : faLock"
                                        class="text-[10px]"
                                    />
                                    <span>Gunakan</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: DISKON MANUAL TRANSAKSI -->
            <div v-else class="space-y-4">
                <!-- Segmented Type Selector -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Tipe Diskon Manual
                    </label>
                    <div
                        class="grid grid-cols-2 gap-2 bg-slate-100 p-1 rounded-lg border border-slate-200"
                    >
                        <button
                            type="button"
                            class="py-1.5 px-3 text-xs font-medium rounded-md transition cursor-pointer text-center"
                            :class="
                                manualType === 'percentage'
                                    ? 'bg-white text-slate-900 font-bold border border-slate-200 shadow-2xs'
                                    : 'text-slate-600 hover:text-slate-900'
                            "
                            @click="manualType = 'percentage'"
                        >
                            Persentase (%)
                        </button>
                        <button
                            type="button"
                            class="py-1.5 px-3 text-xs font-medium rounded-md transition cursor-pointer text-center"
                            :class="
                                manualType === 'fixed'
                                    ? 'bg-white text-slate-900 font-bold border border-slate-200 shadow-2xs'
                                    : 'text-slate-600 hover:text-slate-900'
                            "
                            @click="manualType = 'fixed'"
                        >
                            Nominal Tetap (Rp)
                        </button>
                    </div>
                </div>

                <!-- Input Value -->
                <div>
                    <NumberField
                        v-if="manualType === 'percentage'"
                        v-model="manualRate"
                        label="Persentase Diskon Dokumen (%)"
                        placeholder="Contoh: 5"
                        suffix="%"
                        size="sm"
                        :min="0"
                        :max="100"
                    />
                    <NumberField
                        v-else
                        v-model="manualFixed"
                        label="Nominal Diskon Dokumen (Rp)"
                        placeholder="0"
                        prefix="Rp"
                        size="sm"
                        :min="0"
                        :max="subtotal"
                    />
                </div>

                <!-- Financial Preview -->
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 space-y-1.5 text-xs">
                    <div class="flex justify-between items-center text-slate-600">
                        <span>Subtotal Item Dokumen:</span>
                        <span class="font-medium text-slate-800">{{
                            formatCurrency(subtotal)
                        }}</span>
                    </div>
                    <div class="flex justify-between items-center text-emerald-600 font-semibold">
                        <span>Potongan Diskon Manual:</span>
                        <span>- {{ formatCurrency(calculatedManualDiscount) }}</span>
                    </div>
                    <div
                        class="pt-1.5 border-t border-slate-200 flex justify-between items-center font-bold text-slate-900 text-xs sm:text-sm"
                    >
                        <span>Sisa Subtotal Bersih:</span>
                        <span class="text-main">{{
                            formatCurrency(Math.max(0, subtotal - calculatedManualDiscount))
                        }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <template #footer>
            <div class="flex items-center justify-between w-full">
                <button
                    v-if="hasActiveDiscount"
                    type="button"
                    class="btn btn-outline-danger btn-sm h-[30px] text-xs inline-flex items-center gap-1 cursor-pointer"
                    @click="resetAllDiscount"
                >
                    <FontAwesomeIcon :icon="faTrash" class="text-[10px]" />
                    <span>Hapus Diskon Aktif</span>
                </button>
                <div v-else></div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm h-[30px]"
                        @click="handleClose"
                    >
                        Tutup
                    </button>
                    <button
                        v-if="activeTab === 'manual'"
                        type="button"
                        class="btn btn-main btn-sm h-[30px] inline-flex items-center gap-1.5"
                        @click="applyManualDiscount"
                    >
                        <FontAwesomeIcon :icon="faCheck" />
                        <span>Terapkan Diskon Manual</span>
                    </button>
                </div>
            </div>
        </template>
    </Modal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faCheck,
    faTrash,
    faTags,
    faPercent,
    faSpinner,
    faLock,
} from '@fortawesome/free-solid-svg-icons'
import Modal from '@/Components/Notifications/Modal.vue'
import NumberField from '@/Components/Form/NumberField.vue'
import { formatIDR as formatCurrency } from '@/Composable/currency-format.js'

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    outletId: {
        type: [String, Number, null],
        default: null,
    },
    channel: {
        type: String,
        default: 'wholesale',
    },
    subtotal: {
        type: Number,
        default: 0,
    },
    totalQty: {
        type: Number,
        default: 0,
    },
    currentDiscount: {
        type: Object,
        default: () => ({
            type: null,
            value: 0,
            amount: 0,
            promo_id: null,
            promo_name: null,
            promo_code: null,
        }),
    },
})

const emit = defineEmits(['close', 'apply'])

const activeTab = ref('promo') // 'promo' | 'manual'
const promotions = ref([])
const isLoadingPromos = ref(false)

const voucherInput = ref('')
const isCheckingVoucher = ref(false)
const voucherFeedback = ref({ message: '', isError: false })

const manualType = ref('fixed') // 'fixed' | 'percentage'
const manualFixed = ref(0)
const manualRate = ref(0)

const hasActiveDiscount = computed(() => {
    return Number(props.currentDiscount?.amount || 0) > 0 || !!props.currentDiscount?.promo_id
})

const isPromoSelected = promo => {
    if (props.currentDiscount?.type !== 'promo') return false
    return String(props.currentDiscount?.promo_id) === String(promo.id)
}

const isPromoEligible = promo => {
    if (promo.min_subtotal > 0 && props.subtotal < promo.min_subtotal) {
        return false
    }
    if (promo.min_quantity > 1 && props.totalQty < promo.min_quantity) {
        return false
    }
    return true
}

const calculatedManualDiscount = computed(() => {
    if (manualType.value === 'percentage') {
        const rate = Math.min(100, Math.max(0, Number(manualRate.value || 0)))
        return Math.round((props.subtotal * rate) / 100)
    }
    return Math.min(props.subtotal, Math.max(0, Number(manualFixed.value || 0)))
})

const fetchPromotions = async () => {
    if (!props.outletId) return
    isLoadingPromos.value = true
    try {
        const res = await axios.get(route('api.internal.promos.available'), {
            params: {
                outlet_id: props.outletId,
                target_scope: 'transaction',
            },
        })
        promotions.value = res.data?.data || []
    } catch (e) {
        console.error('Failed to fetch available promotions:', e)
        promotions.value = []
    } finally {
        isLoadingPromos.value = false
    }
}

const applyVoucherCode = async () => {
    const code = voucherInput.value?.trim().toUpperCase()
    if (!code) return

    isCheckingVoucher.value = true
    voucherFeedback.value = { message: '', isError: false }

    const matchedPromo = promotions.value.find(
        p => p.promo_code && p.promo_code.toUpperCase() === code
    )

    if (!matchedPromo) {
        voucherFeedback.value = {
            message: `Kode voucher "${code}" tidak ditemukan atau tidak berlaku untuk outlet ini.`,
            isError: true,
        }
        isCheckingVoucher.value = false
        return
    }

    if (!isPromoEligible(matchedPromo)) {
        let reason = ''
        if (matchedPromo.min_subtotal > 0 && props.subtotal < matchedPromo.min_subtotal) {
            reason = `Minimal belanja ${formatCurrency(matchedPromo.min_subtotal)} (Kurang ${formatCurrency(matchedPromo.min_subtotal - props.subtotal)})`
        } else if (matchedPromo.min_quantity > 1 && props.totalQty < matchedPromo.min_quantity) {
            reason = `Minimal kuantitas ${matchedPromo.min_quantity} item (Kurang ${matchedPromo.min_quantity - props.totalQty} item)`
        }
        voucherFeedback.value = {
            message: `Syarat voucher "${code}" belum terpenuhi: ${reason}`,
            isError: true,
        }
        isCheckingVoucher.value = false
        return
    }

    selectPromo(matchedPromo)
    voucherFeedback.value = {
        message: `Voucher "${code}" berhasil diterapkan!`,
        isError: false,
    }
    isCheckingVoucher.value = false
}

const selectPromo = promo => {
    let discountAmount = 0
    if (promo.discount_type === 'percentage') {
        discountAmount = (props.subtotal * promo.discount_value) / 100
        if (promo.max_discount_amount > 0) {
            discountAmount = Math.min(discountAmount, promo.max_discount_amount)
        }
    } else {
        discountAmount = promo.discount_value
    }

    discountAmount = Math.min(props.subtotal, discountAmount)

    emit('apply', {
        type: 'promo',
        value: discountAmount,
        amount: discountAmount,
        promo_id: promo.id,
        promo_name: promo.name,
        promo_code: promo.promo_code || null,
    })
    emit('close')
}

const removeSelectedPromo = () => {
    resetAllDiscount()
}

const applyManualDiscount = () => {
    const amount = calculatedManualDiscount.value
    emit('apply', {
        type: 'manual',
        value: amount,
        amount: amount,
        promo_id: null,
        promo_name: null,
        promo_code: null,
        manual_rate: manualType.value === 'percentage' ? Number(manualRate.value) : null,
    })
    emit('close')
}

const resetAllDiscount = () => {
    emit('apply', {
        type: null,
        value: 0,
        amount: 0,
        promo_id: null,
        promo_name: null,
        promo_code: null,
    })
    emit('close')
}

const handleClose = () => {
    emit('close')
}

const syncInitialState = () => {
    voucherInput.value = props.currentDiscount?.promo_code || ''
    voucherFeedback.value = { message: '', isError: false }

    if (props.currentDiscount?.type === 'manual') {
        activeTab.value = 'manual'
        manualFixed.value = Number(props.currentDiscount.value || 0)
        manualType.value = 'fixed'
    } else {
        activeTab.value = 'promo'
    }

    fetchPromotions()
}

watch(
    () => props.show,
    isOpen => {
        if (isOpen) {
            syncInitialState()
        }
    },
    { immediate: true }
)
</script>
