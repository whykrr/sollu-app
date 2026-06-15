<template>
    <PopUpPage
        :title
        :sub-title="subTitle"
        :class="{
            show: show,
        }"
        @close="closeForm"
    >
        <div class="flex flex-col gap-2">
            <div>
                <TextField
                    id="name"
                    label="Nama"
                    placeholder="Masukkan nama outlet"
                    v-model="formOutlet.name"
                    :class="{ 'is-invalid': formOutlet.errors.name }"
                    :feedback="formOutlet.errors.name"
                />
            </div>
            <div>
                <TextareaField
                    id="address"
                    placeholder="Masukkan alamat outlet"
                    v-model="formOutlet.address"
                    :class="{ 'is-invalid': formOutlet.errors.address }"
                    :feedback="formOutlet.errors.address"
                    label="Alamat"
                    rows="5"
                />
            </div>
            <span v-if="outlet" class="text-xs text-neutral-400"
                >Diperbarui {{ formatDateTime(outlet.updated_at) }}</span
            >
        </div>
        <template #footer>
            <button class="btn btn-outline-danger ml-2" @click="closeForm">
                Batal
            </button>
            <button
                class="btn btn-success"
                :disabled="formOutlet.processing"
                @click="submitForm"
            >
                Simpan
            </button>
        </template>
    </PopUpPage>
</template>
<script setup>
import TextareaField from '@/Components/Form/TextareaField.vue';
import TextField from '@/Components/Form/TextField.vue';
import PopUpPage from '@/Components/UI/PopUpPage.vue';
import { formatDateID } from '@/Composable/date';
import { formatDateTime } from '@/Composable/time';
import { router, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const emit = defineEmits(['close']);

const props = defineProps({
    show: Boolean,
    outlet: Object,
});

const title = computed(() =>
    props.outlet ? 'Detail Outlet' : 'Tambahkan Outlet Baru',
);

const subTitle = computed(() =>
    props.outlet ? '#' + props.outlet.slug : null,
);

const formOutlet = useForm({
    name: null,
    address: null,
});

watch(
    () => props.outlet,
    (outlet) => {
        formOutlet.reset();

        if (outlet) {
            formOutlet.name = outlet.name;
            formOutlet.address = outlet.address;
        }
    },
    { immediate: true },
);

const closeForm = () => {
    formOutlet.reset();
    emit('close');

    if (props.outlet) {
        router.get(
            route('settings.outlets.index'),
            {},
            {
                only: ['outlet'],
                preserveState: true,
                preserveScroll: true,
            },
        );
    }
};

const submitForm = () => {
    if (props.outlet) {
        formOutlet.put(
            route('settings.outlets.update', { outlet: props.outlet.id }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    formOutlet.reset();
                    emit('close');
                },
            },
        );
        return;
    }

    formOutlet.post(route('settings.outlets.store'), {
        preserveScroll: true,
        onSuccess: () => {
            formOutlet.reset();
            emit('close');
        },
    });
};
</script>
