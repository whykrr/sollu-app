<template>
    <div class="flex flex-col h-full">
        <!-- Stepper -->
        <div class="flex items-center justify-center mb-6">
            <div class="flex items-center space-x-4">
                <div class="flex items-center">
                    <div
                        class="flex items-center justify-center w-8 h-8 rounded-full font-bold text-sm"
                        :class="
                            step >= 1
                                ? 'bg-main text-white'
                                : 'bg-slate-200 text-slate-500'
                        "
                    >
                        1
                    </div>
                    <span
                        class="ml-2 font-medium"
                        :class="step >= 1 ? 'text-slate-800' : 'text-slate-400'"
                        >Informasi</span
                    >
                </div>
                <div
                    class="w-12 h-0.5 bg-slate-200"
                    :class="{ 'bg-main': step >= 2 }"
                ></div>
                <div class="flex items-center">
                    <div
                        class="flex items-center justify-center w-8 h-8 rounded-full font-bold text-sm"
                        :class="
                            step >= 2
                                ? 'bg-main text-white'
                                : 'bg-slate-200 text-slate-500'
                        "
                    >
                        2
                    </div>
                    <span
                        class="ml-2 font-medium"
                        :class="step >= 2 ? 'text-slate-800' : 'text-slate-400'"
                        >Konfirmasi</span
                    >
                </div>
            </div>
        </div>

        <!-- Step 1: Informasi Dasar -->
        <div v-if="step === 1" class="flex-1">
            <div class="mb-4">
                <p class="text-sm text-slate-500">
                    Lengkapi informasi dasar mengenai outlet baru Anda.
                </p>
            </div>
            <div class="flex flex-col gap-2">
                <TextField
                    id="name"
                    label="Nama Outlet"
                    placeholder="Contoh: Cabang Sudirman"
                    v-model="formOutlet.name"
                    :class="{ 'is-invalid': formOutlet.errors.name }"
                    :error="formOutlet.errors.name"
                />
                <TextareaField
                    id="address"
                    placeholder="Masukkan alamat lengkap outlet"
                    v-model="formOutlet.address"
                    :class="{ 'is-invalid': formOutlet.errors.address }"
                    :error="formOutlet.errors.address"
                    label="Alamat Lengkap"
                    rows="4"
                />
            </div>
        </div>

        <!-- Step 2: Konfirmasi -->
        <div v-if="step === 2" class="flex-1">
            <div class="mb-4">
                <p class="text-sm text-slate-500">
                    Mohon periksa kembali informasi outlet sebelum menyimpan.
                </p>
            </div>
            <div
                class="bg-slate-50 rounded-xl p-4 border border-slate-100 mb-4"
            >
                <div class="mb-3">
                    <span
                        class="text-xs text-slate-400 uppercase tracking-wider font-semibold"
                        >Nama Outlet</span
                    >
                    <p class="font-medium text-slate-800 mt-1">
                        {{ formOutlet.name || '-' }}
                    </p>
                </div>
                <div>
                    <span
                        class="text-xs text-slate-400 uppercase tracking-wider font-semibold"
                        >Alamat</span
                    >
                    <p
                        class="font-medium text-slate-800 mt-1 whitespace-pre-wrap"
                    >
                        {{ formOutlet.address || '-' }}
                    </p>
                </div>
            </div>
            <div
                v-if="hasActiveSubscription"
                class="bg-amber-50 text-amber-800 p-4 rounded-xl text-sm flex gap-3 items-start mb-4 border border-amber-200"
            >
                <FontAwesomeIcon
                    :icon="faInfoCircle"
                    class="mt-0.5 text-amber-600"
                />
                <div>
                    <p class="font-semibold mb-1">
                        Tagihan Prorasi Akan Diterbitkan
                    </p>
                    <p class="text-xs">
                        Karena Anda memiliki paket langganan aktif, pembuatan
                        outlet baru ini akan menghasilkan invoice penyesuaian
                        (prorasi). Anda perlu melunasi tagihan tersebut agar
                        outlet ini dapat diaktifkan.
                    </p>
                </div>
            </div>
            <div
                class="bg-blue-50 text-blue-700 p-4 rounded-xl text-sm flex gap-3 items-start"
            >
                <FontAwesomeIcon :icon="faInfoCircle" class="mt-0.5" />
                <p>
                    Setelah outlet dibuat, Anda dapat mengkonfigurasi pengaturan
                    tambahan seperti karyawan, jam operasional, dan perangkat di
                    halaman detail outlet.
                </p>
            </div>
        </div>
    </div>

    <Teleport v-if="isMounted" to="#popUpFooter">
        <div class="flex justify-between w-full">
            <button
                v-if="step > 1"
                class="btn btn-outline-main"
                @click="step--"
            >
                Kembali
            </button>
            <div v-else></div>
            <!-- Spacer -->

            <button v-if="step === 1" class="btn btn-main" @click="nextStep">
                Selanjutnya
            </button>
            <button
                v-if="step === 2"
                class="btn btn-success"
                :disabled="formOutlet.processing"
                @click="submitForm"
            >
                <FontAwesomeIcon :icon="faCheck" class="mr-1" />
                Buat Outlet
            </button>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faInfoCircle, faCheck } from '@fortawesome/free-solid-svg-icons';
import { usePopUpStore } from '@/store/popup';

import TextField from '@/Components/Form/TextField.vue';
import TextareaField from '@/Components/Form/TextareaField.vue';

const popUpStore = usePopUpStore();
const isMounted = ref(false);

onMounted(() => {
    isMounted.value = true;
});

const page = usePage();
const hasActiveSubscription = computed(() => {
    return !!(
        page.props.subscription && page.props.subscription.status === 'active'
    );
});

const step = ref(1);

const formOutlet = useForm({
    name: null,
    address: null,
});

const nextStep = () => {
    // Basic validation
    if (!formOutlet.name) {
        formOutlet.setError('name', 'Nama outlet wajib diisi.');
        return;
    }
    formOutlet.clearErrors();
    step.value++;
};

const closeWizard = () => {
    formOutlet.reset();
    formOutlet.clearErrors();
    step.value = 1;
    popUpStore.close();
};

const submitForm = () => {
    formOutlet.post(route('settings.outlets.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeWizard();
        },
    });
};
</script>
