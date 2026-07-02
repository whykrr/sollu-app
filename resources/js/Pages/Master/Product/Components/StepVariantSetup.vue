<template>
    <div class="space-y-4">
        <div>
            <h3 class="text-lg font-medium text-neutral-900 mb-1">Setup Variant</h3>
            <p class="text-sm text-neutral-500 mb-4">Tambahkan grup varian seperti Ukuran atau Warna beserta opsi-opsinya. Sistem akan otomatis membuat kombinasi produk.</p>
            
            <div class="space-y-3">
                <div v-for="(group, gIndex) in form.variants" :key="group.id" class="rounded-xl border border-neutral-200 bg-neutral-50 p-3">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-full max-w-sm">
                            <TextField 
                                v-model="group.name"
                                label="Nama Grup Varian"
                                placeholder="Contoh: Ukuran, Warna"
                            />
                        </div>
                        <button type="button" class="text-danger hover:text-danger/80 p-2" @click="removeGroup(gIndex)">
                            <FontAwesomeIcon :icon="faTrash" />
                        </button>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-neutral-700">Opsi Varian</label>
                        <div class="flex flex-wrap gap-2 mb-2">
                            <span v-for="(opt, oIndex) in group.options" :key="oIndex" class="inline-flex items-center rounded-full bg-white border border-neutral-300 px-3 py-1 text-sm">
                                {{ opt }}
                                <button type="button" class="ml-2 text-neutral-400 hover:text-neutral-600" @click="removeOption(gIndex, oIndex)">
                                    <FontAwesomeIcon :icon="faTimes" />
                                </button>
                            </span>
                        </div>
                        
                        <div class="flex gap-2 max-w-sm">
                            <input 
                                type="text" 
                                v-model="group.newOption" 
                                @keydown.enter.prevent="addOption(gIndex)"
                                class="block w-full rounded-lg border-neutral-300 focus:border-main focus:ring-main sm:text-sm"
                                placeholder="Ketik opsi lalu tekan Enter"
                            />
                            <button type="button" class="btn btn-secondary rounded-lg whitespace-nowrap" @click="addOption(gIndex)">
                                Tambah
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="button" class="btn btn-outline-main rounded-lg border-dashed w-full py-2" @click="addGroup">
                    <FontAwesomeIcon :icon="faPlus" class="mr-2" />
                    Tambah Grup Varian
                </button>
            </div>
        </div>

        <!-- Preview Kombinasi -->
        <div v-if="combinations.length > 0" class="mt-6">
            <h4 class="text-md font-medium text-neutral-900 mb-2">Preview Kombinasi ({{ combinations.length }} varian)</h4>
            <div class="overflow-hidden rounded-lg border border-neutral-200">
                <Table :headers="tableHeaders" :data="combinations">
                    <template #name="{ row }">
                        <span class="text-sm text-neutral-900 font-medium">{{ row.name }}</span>
                    </template>
                    <template #sku="{ row }">
                        <span class="text-sm text-neutral-500 italic">Auto generate</span>
                    </template>
                </Table>
            </div>
            <p class="text-xs text-neutral-500 mt-2">Harga per varian dapat diatur pada langkah "Pengaturan Harga".</p>
        </div>
    </div>
</template>

<script setup>
import { inject, computed } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faPlus, faTrash, faTimes } from '@fortawesome/free-solid-svg-icons';
import TextField from '@/Components/Form/TextField.vue';
import Table from '@/Components/Tables/Table.vue';

const form = inject('productForm');

const addGroup = () => {
    form.variants.push({
        id: Date.now(),
        name: '',
        options: [],
        newOption: ''
    });
};

const removeGroup = (index) => {
    form.variants.splice(index, 1);
};

const addOption = (groupIndex) => {
    const group = form.variants[groupIndex];
    if (group.newOption.trim() !== '') {
        // Prevent duplicates
        if (!group.options.includes(group.newOption.trim())) {
            group.options.push(group.newOption.trim());
        }
        group.newOption = '';
    }
};

const removeOption = (groupIndex, optionIndex) => {
    form.variants[groupIndex].options.splice(optionIndex, 1);
};

// Generate combinations for preview
const combinations = computed(() => {
    if (form.variants.length === 0) return [];
    
    // Filter out groups without options
    const validGroups = form.variants.filter(g => g.name && g.options.length > 0);
    if (validGroups.length === 0) return [];

    let combos = validGroups[0].options.map(opt => ({ id: Math.random().toString(), name: opt }));

    for (let i = 1; i < validGroups.length; i++) {
        const currentOptions = validGroups[i].options;
        const newCombos = [];
        
        for (const combo of combos) {
            for (const opt of currentOptions) {
                newCombos.push({
                    id: Math.random().toString(),
                    name: `${combo.name} - ${opt}`
                });
            }
        }
        combos = newCombos;
    }

    return combos;
});

const tableHeaders = [
    { field: 'name', label: 'Nama Kombinasi', slot: 'name' },
    { field: 'sku', label: 'SKU Varian', slot: 'sku' },
];
</script>
