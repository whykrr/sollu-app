<template>
    <div>
        <div class="flex flex-col gap-2">
            <div>
                <TextField
                    id="name"
                    v-model="form.name"
                    label="Nama Lengkap"
                    :class="{ 'is-invalid': form.errors.name }"
                    :error="form.errors.name"
                />
            </div>
            <div>
                <EmailField
                    id="email"
                    v-model="form.email"
                    label="Email"
                    :class="{ 'is-invalid': form.errors.email }"
                    :error="form.errors.email"
                    :disabled="user"
                />
            </div>
            <div>
                <NumberField
                    id="phone"
                    v-model="form.phone"
                    label="Telepon"
                    :class="{ 'is-invalid': form.errors.phone }"
                    :error="form.errors.phone"
                />
            </div>
            <div>
                <span class="block text-xs text-neutral-400"
                    >Pilih peran yang akan digunakan karyawan</span
                >
                <div
                    class="bg-slate-50/60 border border-slate-200 p-3 rounded-xl space-y-2"
                >
                    <SelectionGroupField
                        v-model="form.role"
                        label="Peran"
                        :options="roles"
                        name="role"
                        class="sm btn-sm"
                    />
                </div>
                <div class="text-danger text-xs select-none">
                    {{ form.errors.role }}
                </div>
            </div>
            <div v-if="!selectedOutlet" class="space-y-1">
                <span class="block text-xs text-neutral-400 mb-1"
                    >Pilih akses outlet untuk karyawan</span
                >
                <div
                    class="bg-slate-50/60 border border-slate-200 p-3 rounded-xl space-y-2"
                >
                    <SelectionGroupField
                        v-model="form.outlets"
                        multiple
                        label="Outlet"
                        :options="outlets"
                        name="outlets"
                        class="sm btn-sm"
                        show-select-all
                    />
                </div>
                <div
                    v-if="form.errors.outlets"
                    class="text-danger text-xs select-none"
                >
                    {{ form.errors.outlets }}
                </div>
            </div>

            <span v-if="user" class="text-xs text-neutral-400"
                >Diperbarui {{ formatDateTime(user.updated_at) }}</span
            >
        </div>
        <Teleport v-if="isMounted" to="#popUpFooter">
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
        </Teleport>
    </div>
</template>
<script setup>
import SelectionGroupField from '@/Components/Form/SelectionGroupField.vue';
import EmailField from '@/Components/Form/EmailField.vue';
import NumberField from '@/Components/Form/NumberField.vue';
import TextField from '@/Components/Form/TextField.vue';
import { formatDateID } from '@/Composable/date';
import { formatDateTime } from '@/Composable/time';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, watch, onMounted, ref } from 'vue';

const emit = defineEmits(['close']);

const isMounted = ref(false);
onMounted(() => {
    isMounted.value = true;
});

const closeForm = () => {
    form.reset();
    emit('close');
};

const props = defineProps({
    user: Object,
    roles: Array,
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
