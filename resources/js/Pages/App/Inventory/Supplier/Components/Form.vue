<template>
    <div>
        <form class="space-y-2" @submit.prevent="submit">
            <!-- 1. Core Fields (Selalu tampak) -->
            <TextField
                id="name"
                v-model="form.name"
                label="Nama Supplier"
                placeholder="Misal: PT Sumber Pangan Abadi"
                :error="form.errors.name"
                required
            />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <TextField
                    id="phone"
                    v-model="form.phone"
                    label="Nomor Telepon"
                    placeholder="Misal: 081234567890"
                    :error="form.errors.phone"
                />

                <EmailField
                    id="email"
                    v-model="form.email"
                    label="Email"
                    placeholder="Misal: supplier@sumberpangan.com"
                    :error="form.errors.email"
                />
            </div>

            <!-- 2. Progressive Disclosure (Opsi Lanjutan & Alamat) -->
            <DisclosureSection
                title="Informasi Alamat & Bahan Baku"
                description="Alamat pengiriman, catatan, dan daftar bahan yang disuplai"
                :badge="selectedItems.length > 0 ? `${selectedItems.length} item` : null"
                :error="Boolean(form.errors.address || form.errors.notes || form.errors.inventory_items)"
            >
                <TextareaField
                    id="address"
                    v-model="form.address"
                    label="Alamat Lengkap"
                    placeholder="Misal: Jl. Industri Raya No. 12, Pergudangan Blok C, Jakarta Barat"
                    :error="form.errors.address"
                    rows="2"
                />

                <TextareaField
                    id="notes"
                    v-model="form.notes"
                    label="Catatan Khusus"
                    placeholder="Misal: Minimal order 10 kg, jadwal pengiriman tiap Selasa & Kamis"
                    :error="form.errors.notes"
                    rows="2"
                />

                <div class="flex flex-col gap-1">
                    <label class="label">Bahan Baku & Barang yang Disuplai</label>
                    <div class="form-group sm">
                        <span class="form-group-text">
                            <FontAwesomeIcon :icon="faSearch" class="text-slate-400" />
                        </span>
                        <input
                            v-model="searchQuery"
                            type="text"
                            class="form sm"
                            placeholder="Cari bahan baku atau barang..."
                            @input="onSearchInput"
                        />
                    </div>

                    <!-- Loading state -->
                    <div v-if="isSearching" class="text-xs text-slate-500 py-1">Mencari item...</div>

                    <!-- Checkbox List -->
                    <div
                        v-if="searchQuery || searchResults.length > 0"
                        class="border border-slate-200 rounded-lg p-2 max-h-40 overflow-y-auto space-y-1 mt-1 bg-slate-50/50"
                    >
                        <div
                            v-for="item in displayItems"
                            :key="item.id"
                            class="form-check sm hover:bg-white p-1.5 rounded-lg transition-colors cursor-pointer"
                        >
                            <input
                                :id="'supplier-item-' + item.id"
                                v-model="form.inventory_items"
                                type="checkbox"
                                :value="item.id"
                            />
                            <label :for="'supplier-item-' + item.id" class="text-slate-700 font-medium flex-1">
                                {{ item.name }}
                            </label>
                        </div>
                        <div
                            v-if="displayItems.length === 0 && !isSearching"
                            class="text-xs text-slate-500 text-center py-3"
                        >
                            Item tidak ditemukan.
                        </div>
                    </div>

                    <!-- Selected Items Badges -->
                    <div
                        v-if="selectedItems.length > 0"
                        class="flex flex-wrap items-center gap-1.5 mt-1"
                    >
                        <div v-for="item in selectedItems" :key="item.id" class="filter-badge">
                            <span>{{ item.name }}</span>
                            <button
                                type="button"
                                class="filter-badge-remove"
                                title="Hapus item"
                                @click="removeSelectedItem(item.id)"
                            >
                                ✕
                            </button>
                        </div>
                    </div>

                    <span v-if="form.errors.inventory_items" class="form-feedback text-danger mt-1">{{
                        form.errors.inventory_items
                    }}</span>
                </div>
            </DisclosureSection>

            <!-- 3. Status Aktif -->
            <div
                class="flex items-center justify-between border border-slate-200 p-3 rounded-xl cursor-pointer hover:bg-slate-50 transition w-full"
                @click="form.is_active = form.is_active ? false : true"
            >
                <div>
                    <div class="font-semibold text-xs text-slate-700">Status Aktif</div>
                    <div class="text-xs text-slate-500 mt-0.5">
                        {{
                            form.is_active
                                ? 'Supplier aktif dan dapat dipilih untuk pembuatan Purchase Order.'
                                : 'Supplier dinonaktifkan sementara dan disembunyikan dari pilihan transaksi.'
                        }}
                    </div>
                </div>
                <div @click.stop>
                    <Switch id="is_active" v-model="form.is_active" size="sm" />
                </div>
            </div>
        </form>

        <Teleport v-if="isMounted" to="#popUpFooter">
            <button type="button" class="btn btn-flat" :disabled="form.processing" @click="close">
                Batal
            </button>
            <button type="button" class="btn btn-main" :disabled="form.processing" @click="submit">
                Simpan
            </button>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import axios from 'axios'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faSearch } from '@fortawesome/free-solid-svg-icons'
import TextField from '@/Components/Form/TextField.vue'
import EmailField from '@/Components/Form/EmailField.vue'
import TextareaField from '@/Components/Form/TextareaField.vue'
import Switch from '@/Components/Form/Switch.vue'
import DisclosureSection from '@/Components/Form/DisclosureSection.vue'

const props = defineProps({
    supplier: {
        type: Object,
        default: null,
    },
})

const emit = defineEmits(['close'])

const isMounted = ref(false)
onMounted(() => {
    isMounted.value = true
})

const form = useForm({
    name: '',
    phone: '',
    email: '',
    address: '',
    notes: '',
    is_active: true,
    inventory_items: [],
})

// For search
const searchQuery = ref('')
const searchResults = ref([])
const isSearching = ref(false)
const knownItemsMap = ref(new Map()) // To store items that came from supplier edit or search

const displayItems = computed(() => {
    return searchResults.value
})

const selectedItems = computed(() => {
    return form.inventory_items.map(id => knownItemsMap.value.get(id)).filter(Boolean)
})

const removeSelectedItem = id => {
    form.inventory_items = form.inventory_items.filter(itemId => itemId !== id)
}

const onSearchInput = debounce(async () => {
    if (!searchQuery.value) {
        searchResults.value = []
        return
    }

    isSearching.value = true
    try {
        const response = await axios.get(
            route('inventory.suppliers.search-items', {
                search: searchQuery.value,
            })
        )
        searchResults.value = response.data
        response.data.forEach(item => {
            knownItemsMap.value.set(item.id, item)
        })
    } catch (e) {
        console.error(e)
    } finally {
        isSearching.value = false
    }
}, 500)

watch(
    () => props.supplier,
    data => {
        form.reset()
        searchQuery.value = ''
        searchResults.value = []

        if (data) {
            form.name = data.name || ''
            form.phone = data.phone || ''
            form.email = data.email || ''
            form.address = data.address || ''
            form.notes = data.notes || ''
            form.is_active = data.is_active ?? true

            knownItemsMap.value.clear()
            // Map initial items for display
            if (data.inventory_items && data.inventory_items.length > 0) {
                data.inventory_items.forEach(i => {
                    knownItemsMap.value.set(i.id, { id: i.id, name: i.name })
                })
                form.inventory_items = data.inventory_items.map(i => i.id)
            } else if (data.id) {
                axios
                    .get(route('inventory.suppliers.show', data.id))
                    .then(res => {
                        const items = res.data?.inventory_items || []
                        items.forEach(i => {
                            knownItemsMap.value.set(i.id, {
                                id: i.id,
                                name: i.name,
                            })
                        })
                        form.inventory_items = items.map(i => i.id)
                    })
                    .catch(err => console.error(err))
            } else {
                form.inventory_items = []
            }
        } else {
            knownItemsMap.value.clear()
        }
    },
    { immediate: true }
)

const close = () => {
    form.clearErrors()
    emit('close')
}

const submit = () => {
    if (props.supplier?.id) {
        form.put(route('inventory.suppliers.update', props.supplier.id), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => close(),
        })
    } else {
        form.post(route('inventory.suppliers.store'), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => close(),
        })
    }
}
</script>
