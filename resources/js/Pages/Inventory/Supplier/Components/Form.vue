<template>
    <PopUpPage :class="{ show: show }" title="Supplier" @close="close">
        <form @submit.prevent="submit" class="p-4 space-y-4">
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
                <label for="inventory_items">Bahan Baku (Yang disupply)</label>
                <select 
                    id="inventory_items" 
                    v-model="form.inventory_items" 
                    multiple 
                    class="form" 
                    :class="{ 'is-invalid': form.errors.inventory_items }"
                    style="min-height: 120px"
                >
                    <option v-for="item in inventoryItems" :key="item.id" :value="item.id">
                        {{ item.name }}
                    </option>
                </select>
                <span v-if="form.errors.inventory_items" class="form-feedback">{{ form.errors.inventory_items }}</span>
                <span class="text-xs text-gray-500 mt-1">Tahan Ctrl / Cmd untuk memilih lebih dari satu.</span>
            </div>
            
            <Switch
                id="is_active"
                v-model="form.is_active"
                label="Status Aktif"
                description="Tandai jika supplier ini masih aktif"
            />
        </form>

        <template #footer>
            <button type="button" class="btn btn-flat" @click="close" :disabled="form.processing">
                Batal
            </button>
            <button type="button" class="btn btn-main" @click="submit" :disabled="form.processing">
                Simpan
            </button>
        </template>
    </PopUpPage>
</template>

<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import PopUpPage from '@/Components/UI/PopUpPage.vue';
import TextField from '@/Components/Form/TextField.vue';
import EmailField from '@/Components/Form/EmailField.vue';
import TextareaField from '@/Components/Form/TextareaField.vue';
import Switch from '@/Components/Form/Switch.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    supplier: {
        type: Object,
        default: null,
    },
    inventoryItems: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close']);

const form = useForm({
    name: '',
    phone: '',
    email: '',
    address: '',
    notes: '',
    is_active: true,
    inventory_items: [],
});

watch(
    () => props.supplier,
    (data) => {
        form.reset();
        if (data) {
            form.name = data.name || '';
            form.phone = data.phone || '';
            form.email = data.email || '';
            form.address = data.address || '';
            form.notes = data.notes || '';
            form.is_active = data.is_active ?? true;
            form.inventory_items = data.inventory_items?.map(i => i.id) || [];
        }
    },
    { immediate: true }
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
