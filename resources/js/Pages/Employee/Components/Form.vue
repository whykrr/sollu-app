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
                    v-model="form.name"
                    label="Nama Lengkap"
                    :class="{ 'is-invalid': form.errors.name }"
                    :feedback="form.errors.name"
                />
            </div>
            <div>
                <EmailField
                    id="email"
                    v-model="form.email"
                    label="Email"
                    :class="{ 'is-invalid': form.errors.email }"
                    :feedback="form.errors.email"
                    :disabled="user"
                />
            </div>
            <div>
                <NumberField
                    id="phone"
                    v-model="form.phone"
                    label="Telepon"
                    :class="{ 'is-invalid': form.errors.phone }"
                    :feedback="form.errors.phone"
                />
            </div>
            <div>
                <label class="label">Peran</label>
                <span class="block text-xs text-neutral-400"
                    >Pilih peran yang akan digunakan karyawan</span
                >
                <div class="flex flex-wrap gap-1 border p-2 rounded-lg">
                    <RadioButtonField
                        v-model="form.role"
                        name="role"
                        :options="roles"
                        class="sm btn-sm"
                        :feedback="form.errors.role"
                    />
                </div>
                <div class="text-danger text-xs select-none">
                    {{ form.errors.role }}
                </div>
            </div>
            <div v-if="!selectedOutlet">
                <label class="label">Outlet</label>
                <span class="block text-xs text-neutral-400"
                    >Pilih akses outlet untuk karyawan</span
                >
                <div class="flex flex-wrap gap-1 border p-2 rounded-lg">
                    <CheckboxButtonField
                        v-model="form.outlets"
                        :options="outlets"
                        name="outlets"
                        class="sm btn-sm"
                    />
                </div>
                <div class="text-danger text-xs select-none">
                    {{ form.errors.outlets }}
                </div>
            </div>

            <span v-if="user" class="text-xs text-neutral-400"
                >Diperbarui {{ formatDateTime(user.updated_at) }}</span
            >
        </div>
        <template #footer>
            <button
                class="btn btn-outline-danger ml-2"
                :disabled="form.processing"
                @click="closeForm"
            >
                Batal
            </button>
            <button
                class="btn btn-success"
                :disabled="form.processing"
                @click="submitForm"
            >
                Simpan
            </button>
        </template>
    </PopUpPage>
</template>
<script setup>
import CheckboxButtonField from '@/Components/Form/CheckboxButtonField.vue';
import EmailField from '@/Components/Form/EmailField.vue';
import NumberField from '@/Components/Form/NumberField.vue';
import RadioButtonField from '@/Components/Form/RadioButtonField.vue';
import TextField from '@/Components/Form/TextField.vue';
import PopUpPage from '@/Components/UI/PopUpPage.vue';
import { formatDateID } from '@/Composable/date';
import { formatDateTime } from '@/Composable/time';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const title = computed(() =>
    props.user ? 'Detail karyawan' : 'Tambahkan karyawan baru',
);

const subTitle = computed(() => (props.user ? '#' + props.user.email : null));

const emit = defineEmits(['close']);

const closeForm = () => {
    form.reset();
    emit('close');
};

const props = defineProps({
    user: Object,
    roles: Array,
    show: Boolean,
});

const selectedOutlet = computed(() => usePage().props.selectedOutlet);

const outlets = usePage().props.auth.outlets.map((store) => ({
    value: store.id,
    label: store.name,
}));

const form = useForm({
    name: null,
    email: null,
    phone: null,
    role: '',
    outlets: [],
});

watch(
    () => props.user,
    (user) => {
        form.reset();

        if (user) {
            form.name = props.user.name;
            form.email = props.user.email;
            form.phone = props.user.phone;
            form.role = props.user.roles[0].name;
            form.outlets = props.user.outlets
                ? props.user.outlets?.map((outlet) => outlet.id)
                : selectedOutlet.value
                  ? [selectedOutlet.value?.id]
                  : [];
        }
    },
    { immediate: true },
);

const submitForm = () => {
    if (props.user) {
        form.put(route('employees.update', { user: props.user.id }), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                form.reset();
                emit('close');
            },
        });
        return;
    }

    form.post(route('employees.store'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            form.reset();
            emit('close');
        },
    });
};
</script>
