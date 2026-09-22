<template>
    <form class="space-y-3" @submit.prevent="submit">
        <!-- Core Fields (80% Daily Needs) -->
        <TextField
            v-model="form.name"
            label="Nama Lengkap"
            placeholder="Misal: Budi Santoso"
            :error="form.errors.name"
            required
        />

        <TextField
            v-model="form.phone"
            label="Nomor Telepon"
            placeholder="Misal: 081234567890"
            :error="form.errors.phone"
            type="tel"
            required
        />

        <div class="pt-1">
            <Switch
                v-model="form.is_active"
                label="Status Pelanggan Aktif"
                description="Pelanggan aktif dapat dicari dan dipilih pada transaksi kasir (POS)."
            />
        </div>

        <!-- Progressive Disclosure: Optional Fields (20%) -->
        <DisclosureSection
            title="Informasi Tambahan (Opsional)"
            description="Email, tanggal lahir, jenis kelamin, alamat & catatan"
            :error="hasOptionalError"
            :default-open="hasOptionalData"
        >
            <TextField
                v-model="form.email"
                label="Email"
                placeholder="Misal: budi@contoh.com"
                :error="form.errors.email"
                type="email"
            />

            <TextField
                v-model="form.birthdate"
                label="Tanggal Lahir"
                :error="form.errors.birthdate"
                type="date"
            />

            <SelectionGroupField
                v-model="form.gender"
                label="Jenis Kelamin"
                :options="genderOptions"
                :error="form.errors.gender"
            />

            <TextareaField
                v-model="form.address"
                label="Alamat Lengkap"
                placeholder="Misal: Jl. Sudirman No. 12"
                :error="form.errors.address"
                rows="2"
            />

            <TextareaField
                v-model="form.notes"
                label="Catatan Khusus"
                placeholder="Preferensi pesanan, alergi, atau info membership"
                :error="form.errors.notes"
                rows="2"
            />
        </DisclosureSection>

        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex items-center justify-end w-full gap-2">
                <button type="button" class="btn btn-flat" @click="handleCancel()">
                    Batal
                </button>
                <button
                    type="submit"
                    class="btn btn-highlight-main"
                    :disabled="form.processing"
                    @click="submit"
                >
                    {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                </button>
            </div>
        </Teleport>
    </form>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useEnum } from '@/Composable/useEnum'
import { useFormDirtyGuard } from '@/Composable/useFormDirtyGuard'
import TextField from '@/Components/Form/TextField.vue'
import TextareaField from '@/Components/Form/TextareaField.vue'
import SelectionGroupField from '@/Components/Form/SelectionGroupField.vue'
import Switch from '@/Components/Form/Switch.vue'
import DisclosureSection from '@/Components/Form/DisclosureSection.vue'

const props = defineProps({
    customer: {
        type: Object,
        default: null,
    },
})

const { getOptions } = useEnum()
const isMounted = ref(false)

const genderOptions = computed(() => getOptions('CustomerGender'))

const form = useForm({
    name: props.customer?.name || '',
    phone: props.customer?.phone || '',
    email: props.customer?.email || '',
    birthdate: props.customer?.birthdate || '',
    gender: props.customer?.gender || '',
    address: props.customer?.address || '',
    notes: props.customer?.notes || '',
    is_active: props.customer ? (props.customer.is_active ?? true) : true,
})

const { handleCancel, forceClose } = useFormDirtyGuard({ form })

const hasOptionalError = computed(() => {
    return Boolean(
        form.errors.email ||
            form.errors.birthdate ||
            form.errors.gender ||
            form.errors.address ||
            form.errors.notes
    )
})

const hasOptionalData = computed(() => {
    return Boolean(
        form.email ||
            form.birthdate ||
            form.gender ||
            form.address ||
            form.notes
    )
})

onMounted(() => {
    isMounted.value = true
})

const submit = () => {
    if (props.customer?.id) {
        form.put(route('customers.update', props.customer.id), {
            onSuccess: () => forceClose(),
        })
    } else {
        form.post(route('customers.store'), {
            onSuccess: () => forceClose(),
        })
    }
}
</script>
