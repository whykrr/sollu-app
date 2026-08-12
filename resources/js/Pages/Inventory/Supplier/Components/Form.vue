<template>
    <div>
        <form @submit.prevent="submit" class="space-y-2">
            <TextField
                id="name"
                v-model="form.name"
                label="Nama Supplier"
                :class="{ 'is-invalid': form.errors.name }"
                :feedback="form.errors.name"
                required
            />

            <TextField
                id="phone"
                v-model="form.phone"
                label="Nomor Telepon"
                :class="{ 'is-invalid': form.errors.phone }"
                :feedback="form.errors.phone"
            />

            <EmailField
                id="email"
                v-model="form.email"
                label="Email"
                :class="{ 'is-invalid': form.errors.email }"
                :feedback="form.errors.email"
            />

            <TextareaField
                id="address"
                v-model="form.address"
                label="Alamat"
                :class="{ 'is-invalid': form.errors.address }"
                :feedback="form.errors.address"
            />

            <TextareaField
                id="notes"
                v-model="form.notes"
                label="Catatan"
                :class="{ 'is-invalid': form.errors.notes }"
                :feedback="form.errors.notes"
            />

            <div class="flex flex-col gap-1">
                <label for="inventory_items"
                    >Bahan Baku & Barang (Yang disupply)</label
                >
                <!-- New Search Input -->
                <input
                    type="text"
                    v-model="searchQuery"
                    class="form-input text-sm w-full rounded-lg border-gray-200"
                    placeholder="Cari item inventory berdasarkan nama..."
                    @input="onSearchInput"
                />

                <!-- Loading state -->
                <div v-if="isSearching" class="text-xs text-slate-500 py-1">
                    Mencari...
                </div>

                <!-- Checkbox List MainPage -->
                <div
                    v-if="searchQuery || searchResults.length > 0"
                    class="border border-gray-200 rounded-lg p-2 max-h-48 overflow-y-auto space-y-1 mt-1 bg-gray-50/50"
                >
                    <label
                        v-for="item in displayItems"
                        :key="item.id"
                        class="flex items-center gap-2 text-sm cursor-pointer hover:bg-white p-1.5 rounded transition-colors"
                    >
                        <input
                            type="checkbox"
                            :value="item.id"
                            v-model="form.inventory_items"
                            class="form-check-input rounded border-gray-300 text-main focus:ring-main"
                        />
                        <span class="text-slate-700">{{ item.name }}</span>
                    </label>
                    <div
                        v-if="displayItems.length === 0 && !isSearching"
                        class="text-xs text-slate-500 text-center py-4"
                    >
                        Item tidak ditemukan.
                    </div>
                </div>

                <!-- Selected Items Badges -->
                <div
                    v-if="selectedItems.length > 0"
                    class="flex flex-wrap items-center gap-1.5 mt-2"
                >
                    <div
                        v-for="item in selectedItems"
                        :key="item.id"
                        class="filter-badge"
                    >
                        <span>{{ item.name }}</span>
                        <button
                            type="button"
                            @click="removeSelectedItem(item.id)"
                            class="filter-badge-remove"
                            title="Hapus item"
                        >
                            ✕
                        </button>
                    </div>
                </div>

                <span
                    v-if="form.errors.inventory_items"
                    class="form-feedback text-danger mt-1"
                    >{{ form.errors.inventory_items }}</span
                >
            </div>

            <div
                @click="form.is_active = form.is_active ? 0 : 1"
                class="flex items-center justify-between border p-3 rounded-lg cursor-pointer hover:bg-slate-50 transition w-full mt-2"
            >
                <div>
                    <div class="font-bold text-sm text-slate-700">
                        Status Aktif
                    </div>
                    <div class="text-xs text-slate-500 mt-0.5">
                        {{
                            form.is_active
                                ? 'Supplier dalam keadaan aktif dan dapat dipilih untuk pembuatan Purchase Order.'
                                : 'Supplier ditangguhkan sementara dan disembunyikan dari pilihan transaksi.'
                        }}
                    </div>
                </div>
                <div @click.stop>
                    <Switch id="is_active" v-model="form.is_active" />
                </div>
            </div>
        </form>

        <Teleport v-if="isMounted" to="#popUpFooter">
            <button
                type="button"
                class="btn btn-flat"
                @click="close"
                :disabled="form.processing"
            >
                Batal
            </button>
            <button
                type="button"
                class="btn btn-main"
                @click="submit"
                :disabled="form.processing"
            >
                Simpan
            </button>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import axios from 'axios';
import TextField from '@/Components/Form/TextField.vue';
import EmailField from '@/Components/Form/EmailField.vue';
import TextareaField from '@/Components/Form/TextareaField.vue';
import Switch from '@/Components/Form/Switch.vue';

const props = defineProps({
    supplier: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close']);

const isMounted = ref(false);
onMounted(() => {
    isMounted.value = true;
});

const form = useForm({
    name: '',
    phone: '',
    email: '',
    address: '',
    notes: '',
    is_active: true,
    inventory_items: [],
});

// For search
const searchQuery = ref('');
const searchResults = ref([]);
const isSearching = ref(false);
const knownItemsMap = ref(new Map()); // To store items that came from supplier edit or search

const displayItems = computed(() => {
    return searchResults.value;
});

const selectedItems = computed(() => {
    return form.inventory_items
        .map((id) => knownItemsMap.value.get(id))
        .filter(Boolean);
});

const removeSelectedItem = (id) => {
    form.inventory_items = form.inventory_items.filter(
        (itemId) => itemId !== id,
    );
};

const onSearchInput = debounce(async () => {
    if (!searchQuery.value) {
        searchResults.value = [];
        return;
    }

    isSearching.value = true;
    try {
        const response = await axios.get(
            route('inventory.suppliers.search-items', {
                search: searchQuery.value,
            }),
        );
        searchResults.value = response.data;
        response.data.forEach((item) => {
            knownItemsMap.value.set(item.id, item);
        });
    } catch (e) {
        console.error(e);
    } finally {
        isSearching.value = false;
    }
}, 500);

watch(
    () => props.supplier,
    (data) => {
        form.reset();
        searchQuery.value = '';
        searchResults.value = [];

        if (data) {
            form.name = data.name || '';
            form.phone = data.phone || '';
            form.email = data.email || '';
            form.address = data.address || '';
            form.notes = data.notes || '';
            form.is_active = data.is_active ?? true;

            knownItemsMap.value.clear();
            // Map initial items for display
            if (data.inventory_items && data.inventory_items.length > 0) {
                data.inventory_items.forEach((i) => {
                    knownItemsMap.value.set(i.id, { id: i.id, name: i.name });
                });
                form.inventory_items = data.inventory_items.map((i) => i.id);
            } else {
                form.inventory_items = [];
            }
        } else {
            knownItemsMap.value.clear();
        }
    },
    { immediate: true },
);

const close = () => {
    form.clearErrors();
    emit('close');
};

const submit = () => {
    if (props.supplier?.id) {
        form.put(route('inventory.suppliers.update', props.supplier.id), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => close(),
        });
    } else {
        form.post(route('inventory.suppliers.store'), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => close(),
        });
    }
};
</script>
