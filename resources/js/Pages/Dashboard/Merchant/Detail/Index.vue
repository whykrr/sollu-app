<template>
    <Container>
        <div class="grid grid-cols-10 gap-4 min-h-full">
            <div class="col-span-7 bg-white rounded-lg p-2 space-y-2 border">
                <div class="font-semibold">Informasi</div>
                <div>
                    <TextField
                        label="Nama"
                        class="sm"
                        v-model="merchant.name"
                        :class="{ 'is-invalid': merchant.errors.name }"
                        :feedback="merchant.errors.name"
                    />
                    <div class="text-xs text-slate-400">
                        Nama merchant akan ditampilkan pada faktur, struk, dan
                        halaman yang dilihat pelanggan. Nama dapat diubah kapan
                        saja melalui pengaturan
                    </div>
                </div>
                <div>
                    <EmailField
                        label="Email Usaha"
                        class="sm"
                        v-model="merchant.email"
                        :class="{ 'is-invalid': merchant.errors.email }"
                        :feedback="merchant.errors.email"
                    />
                    <div class="text-xs text-slate-400">
                        Email merchant digunakan untuk notifikasi dan tagihan
                        via email
                    </div>
                </div>
                <div>
                    <TextField
                        label="Nama Pemilik"
                        class="sm"
                        v-model="merchant.owner_name"
                        :class="{ 'is-invalid': merchant.errors.owner_name }"
                        :feedback="merchant.errors.owner_name"
                    />
                </div>
                <div>
                    <NumberField
                        label="Telepon Usaha"
                        class="sm"
                        v-model="merchant.phone"
                        :class="{ 'is-invalid': merchant.errors.phone }"
                        :feedback="merchant.errors.phone"
                    />
                </div>
                <div>
                    <TextareaField
                        id="address"
                        label="Alamat"
                        class="sm"
                        :class="{ 'is-invalid': merchant.errors.address }"
                        v-model="merchant.address"
                        :feedback="merchant.errors.address"
                    />
                </div>
            </div>
            <div class="col-span-3">
                <div class="font-semibold">Logo</div>
                <div class="p-6 relative">
                    <DefaultLogo v-if="!auth.merchant.logo" />
                    <UploadedLogo v-else :url="auth.merchant.logo_url" />

                    <div class="absolute bottom-10 left-10">
                        <button
                            v-if="!auth.merchant.logo"
                            @click="showModalUploadLogo = true"
                            class="btn btn-neutral-600 btn-sm"
                        >
                            <FontAwesomeIcon :icon="faCamera" />
                            Upload
                        </button>

                        <button
                            v-else
                            @click="showModalUploadLogo = true"
                            class="btn btn-neutral-600 btn-sm"
                        >
                            <FontAwesomeIcon :icon="faPencil" />
                            Ubah
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <template #footer>
            <button @click="saveDetail" class="btn btn-success btn-sm">
                Simpan
            </button>
        </template>
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
import EmailField from "@/Components/Dashboard/Form/EmailField.vue";
import NumberField from "@/Components/Dashboard/Form/NumberField.vue";
import TextareaField from "@/Components/Dashboard/Form/TextareaField.vue";
import TextField from "@/Components/Dashboard/Form/TextField.vue";
import Container from "@/Components/Dashboard/UI/Container.vue";
import { faCamera, faPencil } from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { useForm, usePage } from "@inertiajs/vue3";
import DefaultLogo from "./Components/DefaultLogo.vue";
import Modal from "@/Components/Dashboard/Notifications/Modal.vue";
import "vue-advanced-cropper/dist/style.css";
import LogoCropper from "./Components/LogoCropper.vue";
import { computed, ref } from "vue";
import UploadedLogo from "./Components/UploadedLogo.vue";

const auth = computed(() => usePage().props.auth);

const showModalUploadLogo = ref(false);

const props = defineProps({
    merchant: Object,
});

const merchant = useForm({
    id: props.merchant.id,
    name: props.merchant.name,
    email: props.merchant.email,
    owner_name: props.merchant.owner_name,
    phone: props.merchant.phone,
    address: props.merchant.address,
});

const formLogo = useForm({
    logo: null,
});

const saveDetail = () => {
    merchant.put(route("dashboard.merchant.info.detail.save"), {
        preserveState: true,
        preserveScroll: true,
    });
};

const saveLogo = (logo) => {
    formLogo.logo = logo;
    formLogo.post(route("dashboard.merchant.info.detail.save.logo"), {
        preserveState: true,
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            showModalUploadLogo.value = false;
        },
    });
};
</script>
