<template>
    <Container>
        <template #header>
            <div class="flex flex-row justify-between gap-2">
                <div class="flex-1 border-r border-slate-200 pr-2">
                    <FilterSearch v-model="search" />
                </div>
                <div>
                    <button
                        class="btn btn-highlight-main btn-sm"
                        @click="openModal()"
                    >
                        <FontAwesomeIcon :icon="faPlus" />
                        Grup Tambahan
                    </button>
                </div>
            </div>
        </template>

        <Table :headers="headers" :data="modifiers.data" :action="true">
            <template #type="{ row }">
                {{
                    row.selection_type === 'single'
                        ? 'Pilih Satu'
                        : 'Pilih Banyak'
                }}
            </template>
            <template #options_count="{ row }">
                {{ row.options.length }}
            </template>
            <template #actions="{ row }">
                <button
                    class="btn btn-highlight-main btn-sm mr-1"
                    title="Ubah"
                    @click="openModal(row)"
                >
                    <FontAwesomeIcon :icon="faPencil" />
                </button>
                <button
                    class="btn btn-outline-danger btn-sm"
                    title="Hapus"
                    @click="deleteModifier(row.id)"
                >
                    <FontAwesomeIcon :icon="faTrash" />
                </button>
            </template>
        </Table>

        <template #footer>
            <Pagination
                :links="modifiers.links"
                :from="modifiers.from"
                :to="modifiers.to"
                :total="modifiers.total"
                :per-page="modifiers.per_page ?? 10"
            />
        </template>

        <PopUpPage
            :class="{ show: showModal }"
            :title="isEdit ? 'Edit Modifier' : 'Tambah Modifier'"
            size="lg"
            @close="closeModal"
        >
            <form @submit.prevent="submit" class="p-4">
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <TextField
                        v-model="form.name"
                        label="Nama Grup Modifier"
                        :feedback="form.errors.name"
                        required
                    />
                    <DropdownField
                        v-model="form.selection_type"
                        :options="[
                            { label: 'Pilih Satu (Single)', value: 'single' },
                            { label: 'Pilih Banyak (Multi)', value: 'multi' },
                        ]"
                        label="Tipe Pilihan"
                        :feedback="form.errors.selection_type"
                    />
                    <div class="flex items-center gap-2 mt-6">
                        <Switch
                            id="is_required"
                            size="sm"
                            labeling="Wajib Dipilih"
                            v-model="form.is_required"
                        />
                    </div>
                    <NumberField
                        v-if="form.selection_type === 'multi'"
                        v-model="form.max_select"
                        label="Maksimal Pilih (Opsional)"
                        :feedback="form.errors.max_select"
                    />
                </div>

                <hr class="my-4" />
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold">Opsi Item</h3>
                    <button
                        type="button"
                        @click="addOption"
                        class="btn btn-secondary btn-sm"
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
                                size="sm"
                                labeling="Default"
                                v-model="opt.is_default"
                            />
                            <button
                                type="button"
                                @click="removeOption(index)"
                                class="text-red-500 font-bold px-2"
                            >
                                &times;
                            </button>
                        </div>
                    </div>
                </div>
                <div
                    v-if="form.errors.options"
                    class="text-red-500 text-sm mb-4"
                >
                    {{ form.errors.options }}
                </div>
            </form>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <button
                        type="button"
                        @click="closeModal"
                        class="btn btn-secondary"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="submit"
                        :disabled="form.processing"
                        class="btn btn-success"
                    >
                        Simpan
                    </button>
                </div>
            </template>
        </PopUpPage>
    </Container>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import Container from '@/Components/UI/Container.vue';
import Table from '@/Components/Tables/Table.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import PopUpPage from '@/Components/UI/PopUpPage.vue';
import TextField from '@/Components/Form/TextField.vue';
import DropdownField from '@/Components/Form/DropdownField.vue';
import NumberField from '@/Components/Form/NumberField.vue';
import Switch from '@/Components/Form/Switch.vue';
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faPlus, faPencil, faTrash } from '@fortawesome/free-solid-svg-icons';
import { debounce } from 'lodash';
import { useModalStore } from '@/store/notification';

const props = defineProps({
    modifiers: Object,
    filters: Object,
});

const modalStore = useModalStore();

const headers = [
    { label: 'Nama Grup', field: 'name', sortable: true },
    {
        label: 'Tipe Pilihan',
        field: 'selection_type',
        slot: 'type',
        sortable: false,
    },
    {
        label: 'Jumlah Opsi',
        field: 'options_count',
        slot: 'options_count',
        sortable: false,
    },
];

const search = ref(props.filters?.search || '');
const showModal = ref(false);
const isEdit = ref(false);
const editingId = ref(null);

const form = useForm({
    name: '',
    selection_type: 'single',
    max_select: null,
    is_required: 0,
    options: [],
});

watch(
    search,
    debounce((newVal) => {
        router.get(
            route('master.modifiers.index'),
            { ...route().params, search: newVal, page: 1 },
            { preserveState: true, preserveScroll: true },
        );
    }, 500),
);

const openModal = (modifier = null) => {
    isEdit.value = !!modifier;
    if (modifier) {
        editingId.value = modifier.id;
        form.name = modifier.name;
        form.selection_type = modifier.selection_type;
        form.max_select = modifier.max_select;
        form.is_required = modifier.is_required ? 1 : 0;
        form.options = modifier.options.map((o) => ({
            name: o.name,
            additional_price: o.additional_price,
            is_default: o.is_default ? 1 : 0,
        }));
    } else {
        editingId.value = null;
        form.reset();
        form.options = [{ name: '', additional_price: 0, is_default: 0 }];
    }
    showModal.value = true;
};

const addOption = () => {
    form.options.push({ name: '', additional_price: 0, is_default: 0 });
};

const removeOption = (index) => {
    form.options.splice(index, 1);
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
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

const deleteModifier = (id) => {
    modalStore.openModalDelete(route('master.modifiers.destroy', id));
};
</script>
