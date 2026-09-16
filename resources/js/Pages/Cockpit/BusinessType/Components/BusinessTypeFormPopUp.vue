<template>
    <div v-if="loading" class="flex justify-center items-center h-48">
        <div class="animate-pulse flex flex-col items-center gap-2">
            <div
                class="w-8 h-8 border-4 border-main border-t-transparent rounded-full animate-spin"
            ></div>
            <span class="text-sm text-neutral-500">Memuat data jenis bisnis...</span>
        </div>
    </div>

    <form v-else class="flex flex-col gap-3" @submit.prevent="submit">
        <!-- Informasi Dasar Jenis Bisnis -->
        <div class="bg-white border border-slate-200 rounded-lg p-3 flex flex-col gap-2.5">
            <h4
                class="text-sm font-bold text-slate-800 border-b border-slate-100 pb-1.5 flex items-center justify-between"
            >
                <span>Informasi Jenis Bisnis</span>
                <span
                    v-if="isEdit"
                    class="text-xs font-mono font-semibold px-2 py-0.5 bg-slate-100 rounded text-slate-600"
                >
                    {{ currentCode }}
                </span>
            </h4>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <TextField
                    v-model="form.name"
                    label="Nama Jenis Bisnis"
                    placeholder="cth. Minimarket / Coffee Shop"
                    :feedback="form.errors.name"
                    @input="handleNameInput"
                />

                <TextField
                    v-model="form.code"
                    label="Kode Jenis Bisnis (Unik)"
                    placeholder="cth. minimarket"
                    :feedback="form.errors.code"
                />
            </div>

            <NumberField
                v-model="form.sort_order"
                label="Urutan Prioritas Tampilan"
                placeholder="cth. 1"
                :feedback="form.errors.sort_order"
            />

            <!-- Toggle Switch Visibilitas Registrasi -->
            <div
                class="flex items-center justify-between p-2.5 bg-slate-50 rounded-lg border border-slate-200 mt-1"
            >
                <div>
                    <div class="text-xs font-semibold text-slate-800">
                        Tampilkan di Pendaftaran Merchant
                    </div>
                    <div class="text-[11px] text-slate-500">
                        Jika aktif, calon merchant dapat memilih opsi jenis bisnis ini saat
                        registrasi awal
                    </div>
                </div>
                <Switch v-model="form.is_visible" />
            </div>
        </div>

        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex justify-end gap-2 w-full">
                <button
                    type="button"
                    class="btn btn-outline-slate-400"
                    :disabled="form.processing"
                    @click="close"
                >
                    Batal
                </button>
                <button
                    type="submit"
                    class="btn btn-main"
                    :disabled="form.processing"
                    @click="submit"
                >
                    {{
                        form.processing
                            ? 'Menyimpan...'
                            : isEdit
                              ? 'Simpan Perubahan'
                              : 'Buat Jenis Bisnis'
                    }}
                </button>
            </div>
        </Teleport>
    </form>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import TextField from '@/Components/Form/TextField.vue'
import NumberField from '@/Components/Form/NumberField.vue'
import Switch from '@/Components/Form/Switch.vue'
import { usePopUpStore } from '@/store/popup'
import axios from 'axios'

const props = defineProps({
    businessTypeId: {
        type: Number,
        default: null,
    },
})

const popUpStore = usePopUpStore()
const isMounted = ref(false)
const loading = ref(Boolean(props.businessTypeId))
const isEdit = computed(() => Boolean(props.businessTypeId))
const currentCode = ref('')

const form = useForm({
    code: '',
    name: '',
    sort_order: 1,
    is_visible: true,
})

const handleNameInput = () => {
    if (!isEdit.value && !form.code) {
        form.code = (form.name || '')
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '')
    }
}

onMounted(async () => {
    isMounted.value = true
    if (props.businessTypeId) {
        try {
            const response = await axios.get(
                route('cockpit.business-types.show', props.businessTypeId)
            )
            const data = response.data
            currentCode.value = data.code
            form.code = data.code
            form.name = data.name
            form.sort_order = Number(data.sort_order) || 0
            form.is_visible = Boolean(data.is_visible)
        } catch (err) {
            console.error('Gagal memuat data jenis bisnis:', err)
        } finally {
            loading.value = false
        }
    }
})

const close = () => {
    popUpStore.close()
}

const submit = () => {
    if (isEdit.value) {
        form.put(route('cockpit.business-types.update', props.businessTypeId), {
            preserveScroll: true,
            onSuccess: () => {
                popUpStore.close()
            },
        })
    } else {
        form.post(route('cockpit.business-types.store'), {
            preserveScroll: true,
            onSuccess: () => {
                popUpStore.close()
            },
        })
    }
}
</script>
