<template>
    <form class="space-y-3" @submit.prevent="submit">
        <!-- Nama Lengkap -->
        <TextField
            id="name"
            v-model="form.name"
            label="Nama Lengkap"
            placeholder="Misal: Ahmad Fauzi"
            :error="form.errors.name"
            required
        />

        <!-- Email -->
        <EmailField
            id="email"
            v-model="form.email"
            label="Alamat Email"
            placeholder="Misal: ahmad@bisnis.com"
            :error="form.errors.email"
            :disabled="Boolean(props.user)"
            required
        />

        <!-- Telepon -->
        <NumberField
            id="phone"
            v-model="form.phone"
            label="Nomor Telepon"
            placeholder="Misal: 081234567890"
            :error="form.errors.phone"
        />

        <!-- PIN -->
        <div class="space-y-1">
            <PinField
                v-if="showPinField"
                id="pin"
                v-model="form.pin"
                label="PIN Kasir / Pegawai (6 Digit)"
                :error="form.errors.pin"
                :hint="
                    props.user
                        ? 'Masukkan 6 digit angka untuk mengganti PIN'
                        : 'Wajib 6 digit angka untuk otentikasi kasir & operasional'
                "
            />
            <div
                v-else
                class="flex items-center justify-between p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs"
            >
                <div>
                    <span class="font-medium text-slate-700 block">PIN Kasir / Pegawai</span>
                    <span class="text-slate-400 text-[11px]">
                        PIN sudah aktif tersimpan secara aman.
                    </span>
                </div>
                <button
                    type="button"
                    class="btn btn-outline-primary btn-xs"
                    @click.prevent="requestPinReset"
                >
                    Reset PIN
                </button>
            </div>
        </div>

        <!-- Peran (Hanya jika bukan Root User) -->
        <div v-if="!props.user?.is_root_user" class="space-y-1">
            <label class="block text-xs font-medium text-slate-700">Peran Pegawai</label>
            <div class="bg-slate-50 border border-slate-200 p-2.5 rounded-lg">
                <SelectionGroupField
                    v-model="form.role"
                    :options="roleOptions"
                    name="role"
                    class="sm btn-sm"
                />
            </div>
            <div v-if="form.errors.role" class="text-danger text-xs">
                {{ form.errors.role }}
            </div>
        </div>

        <!-- Akses Outlet (Hanya jika ada >1 outlet dan tidak sedang dalam mode single outlet terpilih di sidebar) -->
        <div v-if="showOutletField" class="space-y-1">
            <label class="block text-xs font-medium text-slate-700">Akses Outlet</label>
            <div class="bg-slate-50 border border-slate-200 p-2.5 rounded-lg">
                <SelectionGroupField
                    v-model="form.outlets"
                    multiple
                    :options="outletOptions"
                    name="outlets"
                    class="sm btn-sm"
                    show-select-all
                />
            </div>
            <div v-if="form.errors.outlets" class="text-danger text-xs">
                {{ form.errors.outlets }}
            </div>
        </div>

        <div v-if="props.user" class="text-[11px] text-slate-400 pt-1">
            Terakhir diperbarui: {{ formatDateTime(props.user.updated_at) }}
        </div>

        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex items-center justify-end w-full gap-2">
                <button
                    type="button"
                    class="btn btn-flat"
                    :disabled="form.processing"
                    @click="handleCancel()"
                >
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
import { ref, computed, watch, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useAuth } from '@/Composable/useAuth'
import { useFormDirtyGuard } from '@/Composable/useFormDirtyGuard'
import { useModalStore } from '@/store/notification'
import { formatDateTime } from '@/Composable/time'
import TextField from '@/Components/Form/TextField.vue'
import EmailField from '@/Components/Form/EmailField.vue'
import NumberField from '@/Components/Form/NumberField.vue'
import PinField from '@/Components/Form/PinField.vue'
import SelectionGroupField from '@/Components/Form/SelectionGroupField.vue'

const props = defineProps({
    user: {
        type: Object,
        default: null,
    },
    roles: {
        type: Array,
        default: () => [],
    },
})

const { outlets: authOutlets, selectedOutlet } = useAuth()
const modalStore = useModalStore()
const isMounted = ref(false)

const roleOptions = computed(() => {
    return (props.roles || []).map(r => ({
        value: String(r.value ?? r.id ?? r.name),
        label: r.label ?? r.name,
    }))
})

const outletOptions = computed(() => {
    return (authOutlets.value || []).map(store => ({
        value: store.id,
        label: store.name,
    }))
})

const showOutletField = computed(() => {
    if (props.user?.is_root_user) return false
    if ((authOutlets.value || []).length <= 1) return false
    if (selectedOutlet.value) return false
    return true
})

const resolveInitialOutlets = user => {
    if (user?.outlets && user.outlets.length > 0) {
        return user.outlets.map(o => o.id)
    }
    if (selectedOutlet.value?.id) {
        return [selectedOutlet.value.id]
    }
    if ((authOutlets.value || []).length > 0) {
        return (authOutlets.value || []).map(o => o.id)
    }
    return []
}

const form = useForm({
    name: props.user?.name || '',
    email: props.user?.email || '',
    phone: props.user?.phone || '',
    pin: '',
    role: props.user?.roles?.[0]?.name || (roleOptions.value[0]?.value ?? ''),
    outlets: resolveInitialOutlets(props.user),
})

const { handleCancel, forceClose } = useFormDirtyGuard({ form })

const showPinField = ref(!props.user?.has_pin)

const requestPinReset = () => {
    modalStore.confirm({
        title: 'Ubah / Reset PIN',
        message: 'Kamu akan mereset PIN untuk akun pegawai ini. Masukkan 6 digit angka PIN baru.',
        type: 'warning',
        confirmText: 'Ya, Ubah PIN',
        cancelText: 'Batal',
        onConfirm: () => {
            showPinField.value = true
        },
    })
}

watch(
    () => props.user,
    user => {
        if (user) {
            form.name = user.name || ''
            form.email = user.email || ''
            form.phone = user.phone || ''
            form.pin = ''
            if (user.roles && user.roles.length > 0) {
                form.role = user.roles[0].name
            }
            form.outlets = resolveInitialOutlets(user)
            showPinField.value = !user.has_pin
        }
    }
)

onMounted(() => {
    isMounted.value = true
})

const submit = () => {
    if (props.user?.id) {
        form.put(route('employees.update', { user: props.user.id }), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                forceClose()
            },
        })
        return
    }

    form.post(route('employees.store'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            forceClose()
        },
    })
}
</script>
