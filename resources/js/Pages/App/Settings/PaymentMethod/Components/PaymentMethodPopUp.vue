<template>
    <form class="space-y-4" @submit.prevent="submit">
        <!-- Section 1: Informasi Dasar -->
        <div class="space-y-3 pb-3 border-b border-neutral-200">
            <h4 class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">
                1. Informasi Dasar
            </h4>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <TextField
                    v-model="form.name"
                    label="Nama Metode Pembayaran"
                    placeholder="Contoh: QRIS Statis BCA, Tunai, EDC Mandiri"
                    :feedback="form.errors.name"
                    required
                />

                <DropdownField
                    v-model="form.type"
                    label="Jenis Pembayaran"
                    placeholder="Pilih jenis pembayaran"
                    :options="types"
                    :disabled="Boolean(paymentMethod?.is_system)"
                    :feedback="form.errors.type"
                    required
                />
            </div>

            <div class="flex items-center justify-between pt-1">
                <div>
                    <label class="text-xs font-medium text-neutral-700">Status Aktif Global</label>
                    <p class="text-[11px] text-neutral-500">
                        Aktifkan metode ini agar dapat digunakan di kasir seluruh outlet yang
                        diizinkan.
                    </p>
                </div>
                <Switch v-model="form.is_active" size="sm" />
            </div>
        </div>

        <!-- Section 2: Konfigurasi Spesifik Jenis Pembayaran -->
        <div class="space-y-3 pb-3 border-b border-neutral-200">
            <h4 class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">
                2. Detail & Konfigurasi Operasional ({{ getTypeLabel(form.type) }})
            </h4>

            <!-- Provider Chips Preset -->
            <div v-if="form.type !== 'cash'" class="space-y-1.5">
                <label class="text-xs font-medium text-neutral-700">Nama Provider / Bank</label>
                <div class="flex flex-wrap gap-1.5 mb-1.5">
                    <button
                        v-for="providerName in filteredProviders"
                        :key="providerName"
                        type="button"
                        class="px-2.5 py-1 text-xs rounded-lg border transition cursor-pointer"
                        :class="
                            form.provider === providerName
                                ? 'bg-primary-50 border-primary-300 text-primary-700 font-semibold'
                                : 'bg-neutral-50 border-neutral-200 text-neutral-600 hover:bg-neutral-100'
                        "
                        @click="selectProvider(providerName)"
                    >
                        {{ providerName }}
                    </button>
                </div>
                <TextField
                    v-model="form.provider"
                    placeholder="Atau ketik nama provider/bank kustom..."
                    :feedback="form.errors.provider"
                />
            </div>

            <!-- Khusus QRIS: Dropzone Gambar QR Code -->
            <div v-if="form.type === 'qris'" class="space-y-2">
                <label class="text-xs font-medium text-neutral-700"
                    >Gambar QR Code Statis (Kasir)</label
                >

                <div
                    v-if="qrPreviewUrl"
                    class="relative border rounded-xl p-3 bg-neutral-50 flex items-center gap-4"
                >
                    <img
                        :src="qrPreviewUrl"
                        alt="Preview QR Code"
                        class="w-24 h-24 object-contain rounded-lg border border-neutral-200 bg-white"
                    />
                    <div class="flex-1 space-y-1">
                        <p class="text-xs font-medium text-neutral-800">QR Code Terpasang</p>
                        <p class="text-[11px] text-neutral-500">
                            Gambar ini akan ditampilkan pada layar kasir POS untuk dipindai
                            pelanggan.
                        </p>
                        <button
                            type="button"
                            class="btn btn-outline btn-sm text-xs text-rose-600 hover:bg-rose-50 border-rose-200 cursor-pointer"
                            @click="removeQrImage"
                        >
                            <FontAwesomeIcon :icon="faTrash" class="mr-1 text-xs" />
                            Hapus Gambar
                        </button>
                    </div>
                </div>

                <div
                    v-else
                    class="border-2 border-dashed border-neutral-300 hover:border-primary-400 rounded-xl p-4 text-center cursor-pointer transition bg-neutral-50 hover:bg-neutral-100/60"
                    @click="triggerFileInput"
                >
                    <input
                        ref="fileInputRef"
                        type="file"
                        accept="image/png,image/jpeg,image/webp"
                        class="hidden"
                        @change="handleFileUpload"
                    />
                    <FontAwesomeIcon :icon="faQrcode" class="text-2xl text-neutral-400 mb-1" />
                    <p class="text-xs font-medium text-neutral-700">
                        Klik untuk unggah gambar QR Code (PNG, JPG, WEBP)
                    </p>
                    <p class="text-[11px] text-neutral-400">Maksimal 2 MB</p>
                </div>
                <span v-if="form.errors.qr_image" class="text-xs text-rose-600">
                    {{ form.errors.qr_image }}
                </span>
            </div>

            <!-- Khusus Transfer Bank -->
            <div v-if="form.type === 'bank_transfer'" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <TextField
                    v-model="form.account_number"
                    label="Nomor Rekening"
                    placeholder="Contoh: 1234567890"
                    :feedback="form.errors.account_number"
                />
                <TextField
                    v-model="form.account_name"
                    label="Atas Nama (Pemilik Rekening)"
                    placeholder="Contoh: PT Solusi Sukses"
                    :feedback="form.errors.account_name"
                />
            </div>

            <!-- Petunjuk / Instruksi Pembayaran (opsional untuk QRIS, Bank, Custom) -->
            <div v-if="form.type !== 'cash'" class="space-y-1">
                <TextareaField
                    v-model="form.instructions"
                    label="Petunjuk / Catatan Kasir (Opsional)"
                    placeholder="Contoh: Minta pelanggan menunjukkan bukti transfer atau resi EDC..."
                    :feedback="form.errors.instructions"
                    rows="2"
                />
            </div>
        </div>

        <!-- Section 3: Kebijakan Operasional Kasir -->
        <div class="space-y-3 pb-3 border-b border-neutral-200">
            <h4 class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">
                3. Kebijakan Operasional Kasir
            </h4>

            <div class="space-y-2">
                <label class="text-xs font-medium text-neutral-700">
                    Kebijakan Input Nomor Referensi / Approval Code
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <button
                        v-for="policy in referencePolicies"
                        :key="policy.value"
                        type="button"
                        class="p-2.5 rounded-xl border text-left transition cursor-pointer"
                        :class="
                            form.reference_policy === policy.value
                                ? 'bg-primary-50 border-primary-300 text-primary-900 ring-1 ring-primary-300'
                                : 'bg-neutral-50 border-neutral-200 text-neutral-700 hover:bg-neutral-100'
                        "
                        @click="form.reference_policy = policy.value"
                    >
                        <div class="text-xs font-semibold">{{ policy.label }}</div>
                        <div class="text-[10px] text-neutral-500 mt-0.5">
                            {{ getPolicyDescription(policy.value) }}
                        </div>
                    </button>
                </div>
                <span v-if="form.errors.reference_policy" class="text-xs text-rose-600">
                    {{ form.errors.reference_policy }}
                </span>
            </div>

            <div class="flex items-center justify-between pt-1">
                <div>
                    <label class="text-xs font-medium text-neutral-700"
                        >Buka Laci Kasir (Cash Drawer)</label
                    >
                    <p class="text-[11px] text-neutral-500">
                        Otomatis mengirim sinyal pembukaan laci kasir thermal printer saat metode
                        ini diselesaikan.
                    </p>
                </div>
                <Switch v-model="form.open_cash_drawer" size="sm" />
            </div>
        </div>

        <!-- Section 4: Aktivasi di Outlet -->
        <div v-if="!selectedOutlet && outlets.length > 1" class="space-y-2 pt-1">
            <h4 class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">
                4. Aktivasi Cabang / Outlet
            </h4>
            <SelectionGroupField
                v-model="form.outlet_ids"
                label="Pilih Outlet yang Menerima Metode Ini"
                :options="outletOptions"
                multiple
                show-select-all
                :feedback="form.errors.outlet_ids"
            />
        </div>

        <!-- PopUp Footer -->
        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex items-center justify-end w-full gap-2">
                <button
                    type="button"
                    class="btn btn-flat cursor-pointer"
                    @click="popUpStore.close()"
                >
                    Batal
                </button>
                <button
                    type="button"
                    class="btn btn-highlight-main cursor-pointer"
                    :disabled="form.processing"
                    @click="submit"
                >
                    {{ paymentMethod ? 'Simpan Perubahan' : 'Tambah Metode' }}
                </button>
            </div>
        </Teleport>
    </form>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { usePopUpStore } from '@/store/popup'
import { useAuth } from '@/Composable/useAuth'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faQrcode, faTrash } from '@fortawesome/free-solid-svg-icons'

import TextField from '@/Components/Form/TextField.vue'
import TextareaField from '@/Components/Form/TextareaField.vue'
import DropdownField from '@/Components/Form/DropdownField.vue'
import SelectionGroupField from '@/Components/Form/SelectionGroupField.vue'
import Switch from '@/Components/Form/Switch.vue'

const props = defineProps({
    paymentMethod: {
        type: Object,
        default: null,
    },
    outlets: {
        type: Array,
        default: () => [],
    },
    types: {
        type: Array,
        default: () => [],
    },
    referencePolicies: {
        type: Array,
        default: () => [
            { value: 'optional', label: 'Opsional' },
            { value: 'mandatory', label: 'Wajib Diisi' },
            { value: 'hidden', label: 'Disembunyikan' },
        ],
    },
    presetProviders: {
        type: Array,
        default: () => [
            'BCA',
            'Mandiri',
            'BRI',
            'BNI',
            'BSI',
            'CIMB Niaga',
            'GoPay',
            'OVO',
            'DANA',
            'ShopeePay',
            'LinkAja',
        ],
    },
})

const popUpStore = usePopUpStore()
const { selectedOutlet } = useAuth()
const isMounted = ref(false)
const fileInputRef = ref(null)

const outletOptions = computed(() => {
    return props.outlets.map(o => ({
        value: o.id,
        label: o.name,
    }))
})

const filteredProviders = computed(() => {
    if (form.type === 'bank_transfer') {
        return ['BCA', 'Mandiri', 'BRI', 'BNI', 'BSI', 'CIMB Niaga']
    }
    if (form.type === 'ewallet') {
        return ['GoPay', 'OVO', 'DANA', 'ShopeePay', 'LinkAja']
    }
    if (form.type === 'edc') {
        return ['BCA EDC', 'Mandiri EDC', 'BNI EDC', 'BRI EDC']
    }
    return props.presetProviders
})

const getInitialSelectedOutlets = () => {
    if (!props.paymentMethod) {
        if (selectedOutlet.value) {
            return [selectedOutlet.value.id]
        }
        return props.outlets.map(o => o.id)
    }

    if (props.paymentMethod.outlets && props.paymentMethod.outlets.length > 0) {
        return props.paymentMethod.outlets
            .filter(o => (o.pivot ? o.pivot.is_enabled : true))
            .map(o => o.id)
    }

    return props.outlets.map(o => o.id)
}

const form = useForm({
    name: props.paymentMethod?.name || '',
    type: props.paymentMethod?.type || props.types[0]?.value || 'cash',
    provider: props.paymentMethod?.provider || '',
    account_number: props.paymentMethod?.account_number || '',
    account_name: props.paymentMethod?.account_name || '',
    reference_policy:
        props.paymentMethod?.reference_policy?.value ||
        props.paymentMethod?.reference_policy ||
        (props.paymentMethod?.type === 'cash' ? 'hidden' : 'optional'),
    open_cash_drawer: props.paymentMethod ? Boolean(props.paymentMethod.open_cash_drawer) : false,
    instructions: props.paymentMethod?.instructions || '',
    qr_image: null,
    remove_qr_image: false,
    is_active: props.paymentMethod ? Boolean(props.paymentMethod.is_active) : true,
    outlet_ids: getInitialSelectedOutlets(),
})

const qrPreviewUrl = ref(props.paymentMethod?.qr_image_url || null)

const getTypeLabel = type => {
    const found = props.types.find(t => t.value === type)
    return found ? found.label : type
}

const getPolicyDescription = policy => {
    switch (policy) {
        case 'mandatory':
            return 'Kasir wajib mengisi no. referensi / auth code sebelum menyelesaikan pembayaran.'
        case 'hidden':
            return 'Kolom referensi tidak ditampilkan sama sekali pada layar kasir.'
        default:
            return 'Kasir dapat mengisi no. referensi jika ada, atau mengosongkannya.'
    }
}

const selectProvider = providerName => {
    form.provider = providerName
    if (
        !form.name ||
        form.name === '' ||
        form.name.startsWith('QRIS') ||
        form.name.startsWith('Transfer')
    ) {
        if (form.type === 'qris') {
            form.name = `QRIS ${providerName}`
        } else if (form.type === 'bank_transfer') {
            form.name = `Transfer Bank ${providerName}`
        } else if (form.type === 'edc') {
            form.name = `EDC ${providerName}`
        } else if (form.type === 'ewallet') {
            form.name = providerName
        }
    }
}

const triggerFileInput = () => {
    fileInputRef.value?.click()
}

const handleFileUpload = event => {
    const file = event.target.files?.[0]
    if (file) {
        form.qr_image = file
        form.remove_qr_image = false
        qrPreviewUrl.value = URL.createObjectURL(file)
    }
}

const removeQrImage = () => {
    form.qr_image = null
    form.remove_qr_image = true
    qrPreviewUrl.value = null
    if (fileInputRef.value) {
        fileInputRef.value.value = ''
    }
}

const submit = () => {
    if (props.paymentMethod) {
        form.transform(data => ({
            ...data,
            _method: 'PUT',
        })).post(route('settings.payment-methods.update', props.paymentMethod.id), {
            preserveScroll: true,
            onSuccess: () => {
                popUpStore.close()
            },
        })
    } else {
        form.post(route('settings.payment-methods.store'), {
            preserveScroll: true,
            onSuccess: () => {
                popUpStore.close()
            },
        })
    }
}

onMounted(() => {
    isMounted.value = true
})
</script>
