<template>
    <div class="space-y-6">
        <!-- Section 1: Konfigurasi POS Kasir -->
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3
                class="text-base font-semibold text-slate-800 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2"
            >
                <FontAwesomeIcon :icon="faCashRegister" class="text-main" />
                <span>Pengaturan Operasional Kasir POS</span>
            </h3>

            <div class="space-y-3">
                <!-- Supervisor PIN Toggle (Gated by DOUBLE_AUTHORIZATION) -->
                <div
                    v-feature.mask="$enums.FeatureEnum.DOUBLE_AUTHORIZATION"
                    class="p-3.5 border border-slate-200 rounded-lg select-none hover:bg-slate-50/80 hover:border-slate-300 transition-colors"
                >
                    <label
                        for="pos_enable_supervisor_pin"
                        class="flex items-center justify-between cursor-pointer"
                    >
                        <div class="pr-4">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-sm text-slate-700"
                                    >Wajibkan PIN Supervisor (Supervisor Guard)</span
                                >
                                <span class="badge badge-warning text-[10px] px-1.5 py-0.2"
                                    >Keamanan</span
                                >
                            </div>
                            <div class="text-xs text-slate-500 mt-0.5">
                                Kunci tindakan sensitif di kasir (Void pesanan, ubah harga satuan
                                manual, atau pemberian diskon khusus) agar wajib diotorisasi oleh
                                manajer/supervisor.
                            </div>
                        </div>
                        <Switch
                            id="pos_enable_supervisor_pin"
                            v-model="posForm.enable_supervisor_pin"
                            size="md"
                        />
                    </label>
                </div>

                <!-- Negative Stock Tolerance for POS -->
                <label
                    for="pos_allow_negative_stock"
                    class="flex items-center justify-between p-3.5 border border-slate-200 rounded-lg cursor-pointer select-none hover:bg-slate-50/80 hover:border-slate-300 transition-colors"
                >
                    <div class="pr-4">
                        <div class="font-medium text-sm text-slate-700">
                            Toleransi Stok Negatif di POS (Stok Minus)
                        </div>
                        <div class="text-xs text-slate-500 mt-0.5">
                            Izinkan kasir tetap memproses checkout pesanan pelanggan meskipun saldo
                            stok di sistem habis atau belum sempat diinput.
                        </div>
                    </div>
                    <Switch
                        id="pos_allow_negative_stock"
                        v-model="posForm.allow_negative_stock"
                        size="md"
                    />
                </label>

                <!-- Tipe Pesanan POS Aktif -->
                <div class="p-3.5 border border-slate-200 rounded-lg bg-slate-50/50 space-y-2.5">
                    <div>
                        <div class="font-medium text-sm text-slate-700">
                            Tipe Pesanan POS Aktif (Order Types)
                        </div>
                        <div class="text-xs text-slate-500 mt-0.5">
                            Pilih tipe pesanan yang tersedia di kasir outlet ini (minimal 1 aktif).
                            Jika hanya 1 aktif, kasir akan langsung menggunakan tipe tersebut saat
                            transaksi.
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 pt-1">
                        <div
                            v-for="orderType in posOrderTypeOptions"
                            :key="orderType.value"
                            class="flex items-center justify-between p-3 border rounded-lg transition-colors"
                            :class="[
                                posForm.pos_order_types.includes(orderType.value)
                                    ? 'border-main bg-main/5'
                                    : 'border-slate-200 bg-white hover:bg-slate-50/80',
                                posForm.pos_order_types.length === 1 &&
                                posForm.pos_order_types.includes(orderType.value)
                                    ? 'opacity-85 cursor-not-allowed'
                                    : 'cursor-pointer',
                            ]"
                            @click="togglePosOrderType(orderType.value)"
                        >
                            <div>
                                <div class="font-semibold text-xs text-slate-800">
                                    {{ orderType.label }}
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    {{ orderType.desc }}
                                </div>
                            </div>
                            <input
                                type="checkbox"
                                :checked="posForm.pos_order_types.includes(orderType.value)"
                                :disabled="
                                    posForm.pos_order_types.length === 1 &&
                                    posForm.pos_order_types.includes(orderType.value)
                                "
                                class="checkbox checkbox-sm checkbox-primary"
                                @click.stop="togglePosOrderType(orderType.value)"
                            />
                        </div>
                    </div>
                    <div v-if="posForm.errors.pos_order_types" class="text-xs text-rose-500 mt-1">
                        {{ posForm.errors.pos_order_types }}
                    </div>
                </div>
            </div>

            <!-- Save POS Settings button -->
            <div class="flex justify-end pt-4 mt-4 border-t border-slate-100">
                <button
                    type="button"
                    class="btn btn-main btn-sm flex items-center gap-2 cursor-pointer"
                    :disabled="posForm.processing || !posForm.isDirty"
                    @click="savePosSettings"
                >
                    <FontAwesomeIcon :icon="faSave" />
                    <span>Simpan Konfigurasi POS</span>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCashRegister, faSave } from '@fortawesome/free-solid-svg-icons'
import Switch from '@/Components/Form/Switch.vue'

const props = defineProps({
    outlet: {
        type: Object,
        required: true,
    },
    posSettings: {
        type: Object,
        default: () => ({
            enable_supervisor_pin: false,
            allow_negative_stock: true,
        }),
    },
})

const posOrderTypeOptions = [
    {
        value: 'walk_in',
        label: 'Walk-In',
        desc: 'Pelanggan datang langsung ke kasir',
    },
    {
        value: 'dine_in',
        label: 'Dine-In',
        desc: 'Makan di tempat / meja restoran',
    },
    {
        value: 'take_away',
        label: 'Take-Away',
        desc: 'Bungkus bawa pulang',
    },
]

const posForm = useForm({
    outlet_id: props.outlet?.id,
    enable_supervisor_pin: props.posSettings?.enable_supervisor_pin ?? false,
    allow_negative_stock: props.posSettings?.allow_negative_stock ?? true,
    pos_order_types: props.posSettings?.pos_order_types || ['walk_in', 'dine_in', 'take_away'],
})

const togglePosOrderType = type => {
    const types = [...posForm.pos_order_types]
    const idx = types.indexOf(type)
    if (idx > -1) {
        if (types.length <= 1) return
        types.splice(idx, 1)
    } else {
        types.push(type)
    }
    posForm.pos_order_types = types
}

watch(
    () => props.posSettings,
    newSettings => {
        posForm.outlet_id = props.outlet?.id
        posForm.enable_supervisor_pin = newSettings?.enable_supervisor_pin ?? false
        posForm.allow_negative_stock = newSettings?.allow_negative_stock ?? true
        posForm.pos_order_types = newSettings?.pos_order_types || [
            'walk_in',
            'dine_in',
            'take_away',
        ]
    },
    { deep: true }
)

watch(
    () => props.outlet?.id,
    newOutletId => {
        posForm.outlet_id = newOutletId
    }
)

const savePosSettings = () => {
    posForm.put(route('settings.sales.update'), {
        preserveScroll: true,
    })
}
</script>
