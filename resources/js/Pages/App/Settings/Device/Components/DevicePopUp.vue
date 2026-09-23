<template>
    <form class="space-y-3" @submit.prevent="submitDeviceForm">
        <!-- Peringatan Batas Kuota Multi-Device (jika paket Starter/Non-Multi-Device) -->
        <div
            v-if="isQuotaExceeded && !device"
            class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 space-y-1.5"
        >
            <div class="font-semibold flex items-center gap-1.5 text-amber-900">
                <FontAwesomeIcon :icon="faExclamationTriangle" class="text-amber-600" />
                <span>Batas Kuota Perangkat Tercapai</span>
            </div>
            <p>
                Outlet yang dipilih sudah memiliki 1 perangkat kasir terdaftar. Paket tokomu saat
                ini membatasi 1 perangkat per outlet.
            </p>
            <div>
                <a
                    :href="route('settings.billing.plans')"
                    class="font-semibold text-main hover:underline inline-flex items-center gap-1"
                >
                    <span>Tingkatkan ke Paket Pro</span>
                    <FontAwesomeIcon :icon="faArrowRight" class="text-[10px]" />
                </a>
            </div>
        </div>

        <!-- Outlet Field (Kontekstual: Sembunyikan jika hanya punya 1 outlet) -->
        <div v-if="outlets.length > 1">
            <DropdownField
                id="outlet_id"
                v-model="form.outlet_id"
                label="Outlet"
                placeholder="Pilih Outlet"
                :options="outletOptions"
                :feedback="form.errors.outlet_id"
                :disabled="!!device"
                required
            />
            <p v-if="device" class="text-[11px] text-slate-400 mt-1">
                Perangkat terdaftar pada outlet ini.
            </p>
        </div>

        <!-- Nama Perangkat -->
        <TextField
            id="device_name"
            v-model="form.device_name"
            label="Nama Perangkat"
            placeholder="Contoh: Kasir Utama Lt. 1 / Tablet Waiter"
            :feedback="form.errors.device_name"
            required
        />

        <!-- Tipe Perangkat -->
        <DropdownField
            id="device_type"
            v-model="form.device_type"
            label="Tipe Perangkat"
            placeholder="Pilih tipe perangkat"
            :options="deviceTypeOptions"
            :feedback="form.errors.device_type"
            required
        />

        <!-- Nomor Seri / ID Fisik -->
        <TextField
            id="serial_number"
            v-model="form.serial_number"
            label="Nomor Seri / ID Fisik (Opsional)"
            placeholder="Contoh: POS-01 / SN-12345678"
            :feedback="form.errors.serial_number"
        />

        <!-- Status Aktif -->
        <div class="flex items-center justify-between p-3 border border-slate-200 rounded-lg">
            <div>
                <div class="font-medium text-sm text-slate-700">Status Perangkat</div>
                <div class="text-xs text-slate-500">
                    Perangkat aktif dapat login kasir dan menyinkronkan transaksi.
                </div>
            </div>
            <Switch id="is_active" v-model="form.is_active" size="md" />
        </div>

        <!-- Teleport Footer -->
        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex justify-end gap-2 w-full">
                <button
                    type="button"
                    class="btn btn-secondary px-4 py-2 rounded-lg text-sm cursor-pointer"
                    @click="handleCancel"
                >
                    Batal
                </button>
                <button
                    type="button"
                    class="btn btn-main px-5 py-2 rounded-lg text-sm font-medium cursor-pointer"
                    :disabled="form.processing || (isQuotaExceeded && !device)"
                    @click="submitDeviceForm"
                >
                    {{ device ? 'Simpan Perubahan' : 'Tambah Perangkat' }}
                </button>
            </div>
        </Teleport>
    </form>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faArrowRight, faExclamationTriangle } from '@fortawesome/free-solid-svg-icons'
import { useFormDirtyGuard } from '@/Composable/useFormDirtyGuard'
import TextField from '@/Components/Form/TextField.vue'
import DropdownField from '@/Components/Form/DropdownField.vue'
import Switch from '@/Components/Form/Switch.vue'

const props = defineProps({
    device: {
        type: Object,
        default: null,
    },
    outlets: {
        type: Array,
        default: () => [],
    },
    defaultOutletId: {
        type: String,
        default: '',
    },
    hasMultiDevice: {
        type: Boolean,
        default: true,
    },
    outletDeviceCounts: {
        type: Object,
        default: () => ({}),
    },
})

const isMounted = ref(false)

// Resolve initial outlet ID
const initialOutletId = computed(() => {
    if (props.device?.outlet_id) {
        return props.device.outlet_id
    }
    if (props.defaultOutletId) {
        return props.defaultOutletId
    }
    if (props.outlets.length === 1) {
        return String(props.outlets[0].id)
    }
    return ''
})

const outletOptions = computed(() =>
    props.outlets.map(store => {
        const count = props.outletDeviceCounts?.[store.id] ?? 0
        let label = store.name
        if (
            !props.hasMultiDevice &&
            count >= 1 &&
            (!props.device || props.device.outlet_id !== store.id)
        ) {
            label += ' (Penuh - Maks 1)'
        }
        return {
            value: String(store.id),
            label,
        }
    })
)

const deviceTypeOptions = [
    { value: 'pos_terminal', label: 'POS Terminal (Desktop)' },
    { value: 'pos_mobile', label: 'POS Mobile (Tablet/HP)' },
    { value: 'kiosk', label: 'Kiosk / Self-Service (Segera Hadir)', disabled: true },
    { value: 'kitchen_display', label: 'Kitchen Device (Segera Hadir)', disabled: true },
]

const form = useForm({
    outlet_id: initialOutletId.value,
    device_name: '',
    device_type: 'pos_terminal',
    serial_number: '',
    is_active: true,
})

const { handleCancel, forceClose } = useFormDirtyGuard({ form })

// Check if selected outlet has reached quota
const isQuotaExceeded = computed(() => {
    if (props.hasMultiDevice) return false
    if (!form.outlet_id) return false

    const count = props.outletDeviceCounts?.[form.outlet_id] ?? 0
    if (props.device && props.device.outlet_id === form.outlet_id) {
        return false
    }
    return count >= 1
})

watch(
    () => props.device,
    device => {
        if (device) {
            form.device_name = device.device_name || ''
            form.device_type = device.device_type || 'pos_terminal'
            form.serial_number = device.serial_number || ''
            form.is_active = !!device.is_active
            form.outlet_id = device.outlet_id || initialOutletId.value
        } else {
            form.outlet_id = initialOutletId.value
        }
    },
    { immediate: true }
)

const submitDeviceForm = () => {
    if (isQuotaExceeded.value && !props.device) {
        return
    }

    if (props.device) {
        form.put(route('settings.devices.update', { device: props.device.id }), {
            preserveScroll: true,
            onSuccess: () => {
                forceClose()
            },
        })
    } else {
        form.post(route('settings.devices.store'), {
            preserveScroll: true,
            onSuccess: () => {
                forceClose()
            },
        })
    }
}

onMounted(() => {
    isMounted.value = true
})
</script>
