<template>
    <div>
        <form @submit.prevent="submit">
            <div class="grid grid-cols-2 gap-2 mb-4">
                <TextField
                    v-model="form.name"
                    label="Nama Grup Modifier"
                    :error="form.errors.name"
                    required
                />
                <DropdownField
                    v-model="form.selection_type"
                    :options="[
                        { label: 'Pilih Satu (Single)', value: 'single' },
                        { label: 'Pilih Banyak (Multi)', value: 'multi' },
                    ]"
                    label="Tipe Pilihan"
                    :error="form.errors.selection_type"
                />
                <div class="flex items-center gap-2 mt-6">
                    <Switch
                        id="is_required"
                        v-model="form.is_required"
                        size="sm"
                        labeling="Wajib Dipilih"
                    />
                </div>
                <NumberField
                    v-if="form.selection_type === 'multi'"
                    v-model="form.max_select"
                    label="Maksimal Pilih (Opsional)"
                    :error="form.errors.max_select"
                />
            </div>

            <hr class="my-4" />
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold">Opsi Item</h3>
                <button
                    type="button"
                    class="btn btn-secondary btn-sm"
                    @click="addOption"
                >
                    Tambah Opsi
                </button>
            </div>

            <div class="space-y-2 mb-4">
                <div
                    v-for="(opt, index) in form.options"
                    :key="index"
                    class="flex gap-2 items-start"
                >
                    <div class="flex-1">
                        <TextField
                            v-model="opt.name"
                            placeholder="Nama Opsi"
                            required
                        />
                    </div>
                    <div class="w-1/3">
                        <NumberField
                            v-model="opt.additional_price"
                            placeholder="Harga Tambahan"
                        />
                    </div>
                    <div class="flex items-center gap-2 mt-2">
                        <Switch
                            :id="'default_opt_' + index"
                            v-model="opt.is_default"
                            size="sm"
                            labeling="Default"
                        />
                        <button
                            type="button"
                            class="text-red-500 font-bold px-2"
                            @click="removeOption(index)"
                        >
                            &times;
                        </button>
                    </div>
                </div>
            </div>
            <div v-if="form.errors.options" class="text-red-500 text-sm mb-4">
                {{ form.errors.options }}
            </div>
        </form>

        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex justify-end gap-2 w-full">
                <button
                    type="button"
                    class="btn btn-secondary"
                    @click="closeModal"
                >
                    Batal
                </button>
                <button
                    type="button"
                    :disabled="form.processing"
                    class="btn btn-success"
                    @click="submit"
                >
                    Simpan
                </button>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { usePopUpStore } from '@/store/popup';

import TextField from '@/Components/Form/TextField.vue';
import DropdownField from '@/Components/Form/DropdownField.vue';
import NumberField from '@/Components/Form/NumberField.vue';
import Switch from '@/Components/Form/Switch.vue';

const props = defineProps({
    modifier: Object,
});

const popUpStore = usePopUpStore();
const isMounted = ref(false);

const isEdit = ref(false);
const editingId = ref(null);

const form = useForm({
    name: '',
    selection_type: 'single',
    max_select: null,
    is_required: 0,
    options: [{ name: '', additional_price: 0, is_default: 0 }],
});

onMounted(() => {
    isMounted.value = true;
    if (props.modifier) {
        isEdit.value = true;
        editingId.value = props.modifier.id;
        form.name = props.modifier.name;
        form.selection_type = props.modifier.selection_type;
        form.max_select = props.modifier.max_select;
        form.is_required = props.modifier.is_required ? 1 : 0;
        form.options = props.modifier.options.map((o) => ({
            name: o.name,
            additional_price: o.additional_price,
            is_default: o.is_default ? 1 : 0,
        }));
    }
});

const addOption = () => {
    form.options.push({ name: '', additional_price: 0, is_default: 0 });
};

const removeOption = (index) => {
    form.options.splice(index, 1);
};

const closeModal = () => {
    popUpStore.close();
};

const submit = () => {
    if (isEdit.value) {
        form.put(route('master.modifiers.update', editingId.value), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('master.modifiers.store'), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => closeModal(),
        });
    }
};
</script>
