<template>
    <div>
        <form class="space-y-4" @submit.prevent="submitForm">
            <TextField
                id="bank_name"
                v-model="form.bank_name"
                label="Nama Bank / Dompet Digital"
                placeholder="Contoh: BANK BCA"
                :error="form.errors.bank_name"
            />

            <TextField
                id="account_number"
                v-model="form.account_number"
                label="Nomor Rekening"
                class="font-mono"
                placeholder="Contoh: 1234567890"
                :error="form.errors.account_number"
            />

            <TextField
                id="account_name"
                v-model="form.account_name"
                label="Nama Pemilik Rekening (a/n)"
                placeholder="Contoh: PT Solusi Dari Anak Bangsa"
                :error="form.errors.account_name"
            />

            <div class="pt-2">
                <Switch id="is_active" v-model="form.is_active" labeling="Aktifkan Metode Ini" />
                <p class="text-[11px] text-gray-500 mt-1">
                    Jika diaktifkan, rekening ini akan muncul di pilihan transfer pengguna.
                </p>
                <p v-if="form.errors.is_active" class="form-feedback text-danger">
                    {{ form.errors.is_active }}
                </p>
            </div>
        </form>

        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex justify-end gap-3 w-full">
                <button
                    type="button"
                    class="btn btn-outline-main"
                    :disabled="form.processing"
                    @click="popUpStore.close()"
                >
                    Batal
                </button>
                <button
                    type="button"
                    class="btn btn-main"
                    :disabled="form.processing"
                    @click="submitForm"
                >
                    <span v-if="form.processing">Menyimpan...</span>
                    <span v-else>Simpan Rekening</span>
                </button>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { usePopUpStore } from '@/store/popup'
import TextField from '@/Components/Form/TextField.vue'
import Switch from '@/Components/Form/Switch.vue'

const props = defineProps({
    methodData: {
        type: Object,
        default: null,
    },
})

const popUpStore = usePopUpStore()
const isMounted = ref(false)

const form = useForm({
    bank_name: props.methodData ? props.methodData.bank_name : '',
    account_number: props.methodData ? props.methodData.account_number : '',
    account_name: props.methodData ? props.methodData.account_name : '',
    is_active: props.methodData ? Boolean(props.methodData.is_active) : true,
})

onMounted(() => {
    isMounted.value = true
})

const submitForm = () => {
    if (props.methodData) {
        form.put(route('cockpit.payment-methods.update', props.methodData.id), {
            preserveScroll: true,
            onSuccess: () => {
                popUpStore.close()
            },
        })
    } else {
        form.post(route('cockpit.payment-methods.store'), {
            preserveScroll: true,
            onSuccess: () => {
                popUpStore.close()
            },
        })
    }
}
</script>
