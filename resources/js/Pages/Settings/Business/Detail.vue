<template>
    <Container>
        <div class="flex flex-row gap-4 min-h-full justify-center">
            <div
                class="w-[50%] flex h-full flex-col rounded-lg border bg-white p-4"
            >
                <!-- Header -->
                <div class="mb-4 flex items-start justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-800">
                            Informasi Usaha
                        </h2>

                        <p class="text-sm text-slate-500">
                            Kelola identitas dan informasi utama usaha Anda
                        </p>
                    </div>
                </div>

                <!-- Form -->
                <div class="space-y-2">
                    <!-- Business Name -->
                    <div class="space-y-0">
                        <TextField
                            v-model="business.name"
                            label="Nama Usaha"
                            placeholder="Contoh: Sollu Coffee"
                            :class="{
                                'is-invalid': business.errors.name,
                            }"
                            :feedback="business.errors.name"
                        />

                        <p class="text-xs leading-relaxed text-slate-400">
                            Nama usaha akan tampil pada struk, invoice, laporan,
                            dan halaman pelanggan.
                        </p>
                    </div>

                    <!-- Email -->
                    <div class="space-y-0">
                        <EmailField
                            v-model="business.email"
                            label="Email Usaha"
                            placeholder="business@email.com"
                            :class="{
                                'is-invalid': business.errors.email,
                            }"
                            :feedback="business.errors.email"
                        />

                        <p class="text-xs leading-relaxed text-slate-400">
                            Digunakan untuk notifikasi sistem, invoice, dan
                            informasi tagihan.
                        </p>
                    </div>

                    <!-- Owner -->
                    <div class="space-y-0">
                        <TextField
                            v-model="business.owner_name"
                            label="Nama Pemilik"
                            placeholder="Nama pemilik usaha"
                            :class="{
                                'is-invalid': business.errors.owner_name,
                            }"
                            :feedback="business.errors.owner_name"
                        />
                    </div>

                    <!-- Phone -->
                    <div class="space-y-0">
                        <NumberField
                            v-model="business.phone"
                            label="Nomor Telepon"
                            placeholder="08xxxxxxxxxx"
                            :class="{
                                'is-invalid': business.errors.phone,
                            }"
                            :feedback="business.errors.phone"
                        />
                    </div>

                    <!-- Address -->
                    <div class="space-y-0">
                        <TextareaField
                            id="address"
                            v-model="business.address"
                            label="Alamat Usaha"
                            placeholder="Masukkan alamat usaha lengkap"
                            rows="4"
                            :class="{
                                'is-invalid': business.errors.address,
                            }"
                            :feedback="business.errors.address"
                        />

                        <p class="text-xs leading-relaxed text-slate-400">
                            Alamat dapat digunakan untuk kebutuhan invoice,
                            pengiriman, dan profil usaha.
                        </p>
                    </div>
                </div>

                <!-- Footer Action -->
                <div class="border-slate-100 pt-5">
                    <button
                        class="btn btn-success w-full justify-center rounded-lg"
                        :disabled="business.processing"
                        @click="saveDetail"
                    >
                        <span v-if="business.processing"> Menyimpan... </span>

                        <span v-else> Simpan Perubahan </span>
                    </button>
                </div>
            </div>
            <div class="w-80">
                <div
                    class="bg-white rounded-lg p-4 space-y-2 border sticky top-0"
                >
                    <div class="font-semibold text-lg">Logo Usaha</div>
                    <div class="p-0 relative">
                        <LogoCropper
                            @action="saveLogo"
                            :url="auth.business.logo_url"
                        />
                    </div>
                </div>
            </div>
        </div>
        <Modal
            title="Upload Logo"
            :class="{ show: showModalUploadLogo }"
            @close="showModalUploadLogo = false"
        >
            <LogoCropper @action="saveLogo" />
        </Modal>
    </Container>
</template>
<script setup>
import EmailField from '@/Components/Form/EmailField.vue';
import NumberField from '@/Components/Form/NumberField.vue';
import TextareaField from '@/Components/Form/TextareaField.vue';
import TextField from '@/Components/Form/TextField.vue';
import Container from '@/Components/UI/Container.vue';
import { faCamera, faPencil } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useForm, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Notifications/Modal.vue';
import 'vue-advanced-cropper/dist/style.css';
import LogoCropper from './Components/LogoCropper.vue';
import { computed, ref } from 'vue';

const auth = computed(() => usePage().props.auth);

const showModalUploadLogo = ref(false);

const props = defineProps({
    business: Object,
});

const business = useForm({
    id: props.business.id,
    name: props.business.name,
    email: props.business.email,
    owner_name: props.business.owner_name,
    phone: props.business.phone,
    address: props.business.address,
});

const formLogo = useForm({
    logo: null,
});

const saveDetail = () => {
    business.put(route('settings.business.detail.save'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const saveLogo = (logo) => {
    formLogo.logo = logo;
    formLogo.post(route('settings.business.detail.save.logo'), {
        preserveState: true,
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            showModalUploadLogo.value = false;
        },
    });
};
</script>
