<template>
    <div class="space-y-4">
        <div>
            <h3 class="text-lg font-medium text-neutral-900 mb-1">Opsi Tambahan (Modifier)</h3>
            <p class="text-sm text-neutral-500 mb-4">Pilih modifier (tambahan/topping) yang berlaku untuk produk ini.</p>
            
            <!-- Search Modifier -->
            <div class="relative max-w-md mb-4">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <FontAwesomeIcon :icon="faSearch" class="text-neutral-400" />
                </div>
                <input
                    v-model="searchQuery"
                    type="text"
                    class="block w-full rounded-lg border-neutral-300 pl-10 focus:border-main focus:ring-main sm:text-sm"
                    placeholder="Cari grup modifier..."
                />
                
                <!-- Search Results Dropdown -->
                <div v-if="filteredModifiers.length > 0" class="absolute z-10 w-full mt-1 bg-white border border-neutral-200 rounded-md shadow-lg max-h-60 overflow-auto">
                    <ul class="py-1">
                        <li v-for="mod in filteredModifiers" :key="mod.id" 
                            class="px-4 py-2 hover:bg-neutral-100 cursor-pointer flex justify-between items-center"
                            @click="addModifier(mod)"
                        >
                            <span class="text-sm font-medium text-neutral-900">{{ mod.name }}</span>
                            <span class="text-xs px-2 py-1 bg-neutral-100 rounded text-neutral-500">{{ mod.type === 'single' ? 'Pilih Satu' : 'Pilih Banyak' }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Selected Modifiers -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                <div v-for="(mod, index) in form.modifiers" :key="index" class="relative rounded-lg border border-main bg-main/5 p-3 flex items-center justify-between">
                    <div>
                        <div class="font-medium text-main text-sm">{{ mod.name }}</div>
                        <div class="text-xs text-neutral-500">{{ mod.type === 'single' ? 'Pilih Satu' : 'Pilih Banyak' }}</div>
                    </div>
                    <button type="button" class="text-danger hover:text-danger/80" @click="removeModifier(index)">
                        <FontAwesomeIcon :icon="faTrash" />
                    </button>
                    <!-- Check icon -->
                    <div class="absolute -top-2 -right-2 bg-main text-white rounded-full h-5 w-5 flex items-center justify-center text-[10px]">
                        <FontAwesomeIcon :icon="faCheck" />
                    </div>
                </div>
                
                <div v-if="form.modifiers.length === 0" class="col-span-full py-6 text-center border-2 border-dashed border-neutral-300 rounded-xl text-neutral-500">
                    <p class="text-sm">Tidak ada modifier yang dipilih.</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { inject, ref, computed } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faCheck, faSearch, faTrash } from '@fortawesome/free-solid-svg-icons';

const form = inject('productForm');

const searchQuery = ref('');

// Dummy modifier groups
const existingModifiers = [
    { id: 1, name: 'Pilihan Topping', type: 'multi' },
    { id: 2, name: 'Level Pedas', type: 'single' },
    { id: 3, name: 'Suhu (Panas/Dingin)', type: 'single' },
];

const filteredModifiers = computed(() => {
    if (!searchQuery.value) return [];
    const q = searchQuery.value.toLowerCase();
    const addedIds = form.modifiers.map(m => m.id);
    return existingModifiers.filter(m => m.name.toLowerCase().includes(q) && !addedIds.includes(m.id));
});

const addModifier = (mod) => {
    form.modifiers.push({
        id: mod.id,
        name: mod.name,
        type: mod.type,
    });
    searchQuery.value = '';
};

const removeModifier = (index) => {
    form.modifiers.splice(index, 1);
};
</script>
