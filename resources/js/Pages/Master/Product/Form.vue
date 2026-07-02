<template>
    <Container>
        <template #header>
            <div class="flex items-center gap-2 border-b pb-2">
                <template v-for="(step, index) in steps" :key="index">
                    <div 
                        v-if="isStepVisible(step.id)"
                        class="flex items-center cursor-pointer text-sm"
                        @click="currentStep = index"
                        :class="{'text-primary font-bold': currentStep === index, 'text-slate-400': currentStep !== index}"
                    >
                        <div class="w-6 h-6 rounded-full flex items-center justify-center border mr-1 text-xs" 
                            :class="currentStep === index ? 'border-primary bg-primary-100 text-primary' : 'border-slate-300'">
                            {{ index + 1 }}
                        </div>
                        <span>{{ step.label }}</span>
                        <span v-if="index < steps.length - 1" class="mx-2 text-slate-300">/</span>
                    </div>
                </template>
            </div>
        </template>

        <form @submit.prevent="submit" class="bg-white rounded-lg p-4 space-y-4">
            <!-- Step 1: Basic Info -->
            <div v-show="steps[currentStep].id === 'basic'" class="space-y-3">
                <div class="font-semibold text-lg border-b pb-1">Informasi Dasar</div>
                <div class="grid grid-cols-2 gap-3">
                    <TextField v-model="form.name" label="Nama Produk" :class="{ 'is-invalid': form.errors.name }" :feedback="form.errors.name" required />
                    <TextField v-model="form.code" label="Kode / SKU (Opsional)" :class="{ 'is-invalid': form.errors.code }" :feedback="form.errors.code" />
                    <div class="col-span-2">
                        <DropdownField 
                            v-model="form.product_category_id" 
                            :options="categoryOptions" 
                            label="Kategori" 
                            :class="{ 'is-invalid': form.errors.product_category_id }"
                            :feedback="form.errors.product_category_id"
                        />
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi</label>
                        <textarea v-model="form.description" class="form w-full border-slate-300 rounded-md" rows="3"></textarea>
                    </div>
                    <div class="col-span-2 flex gap-4 mt-2">
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="is_show" v-model="form.is_show" class="rounded text-primary"> 
                            <label for="is_show" class="text-sm">Tampilkan di POS</label>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="sellable" v-model="form.sellable" class="rounded text-primary"> 
                            <label for="sellable" class="text-sm">Dapat Dijual</label>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="purchasable" v-model="form.purchasable" class="rounded text-primary"> 
                            <label for="purchasable" class="text-sm">Dapat Dibeli (PO)</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Type Selection -->
            <div v-show="steps[currentStep].id === 'type'" class="space-y-3">
                <div class="font-semibold text-lg border-b pb-1">Tipe Produk</div>
                <div class="grid grid-cols-3 gap-3">
                    <div @click="form.product_type = 'basic'" class="border rounded-lg p-3 cursor-pointer text-center hover:border-primary transition" :class="{'border-primary bg-slate-50': form.product_type === 'basic'}">
                        <div class="font-bold mb-1">Produk Fisik (Basic)</div>
                        <div class="text-xs text-slate-500">Barang fisik yang dikelola stoknya (seperti Kopi, Makanan).</div>
                    </div>
                    <div @click="form.product_type = 'service'" class="border rounded-lg p-3 cursor-pointer text-center hover:border-primary transition" :class="{'border-primary bg-slate-50': form.product_type === 'service'}">
                        <div class="font-bold mb-1">Layanan (Service)</div>
                        <div class="text-xs text-slate-500">Produk tak berwujud, tanpa stok (seperti Ongkir, Jasa).</div>
                    </div>
                    <div @click="form.product_type = 'bundle'" class="border rounded-lg p-3 cursor-pointer text-center hover:border-primary transition" :class="{'border-primary bg-slate-50': form.product_type === 'bundle'}">
                        <div class="font-bold mb-1">Paket (Bundle)</div>
                        <div class="text-xs text-slate-500">Kombinasi dari beberapa produk fisik atau layanan.</div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Feature Flags -->
            <div v-show="steps[currentStep].id === 'flags'" class="space-y-3">
                <div class="font-semibold text-lg border-b pb-1">Pengaturan Lanjutan</div>
                <div class="space-y-2">
                    <div v-if="form.product_type === 'basic'" class="flex items-center justify-between border p-3 rounded-lg">
                        <div>
                            <div class="font-bold text-sm">Memiliki Varian</div>
                            <div class="text-xs text-slate-500">Produk ini memiliki pilihan varian seperti ukuran (S, M, L).</div>
                        </div>
                        <input type="checkbox" v-model="form.has_variant" class="rounded h-5 w-5 text-primary">
                    </div>

                    <div v-if="form.product_type !== 'bundle'" class="flex items-center justify-between border p-3 rounded-lg">
                        <div>
                            <div class="font-bold text-sm">Memiliki Modifier (Opsi Tambahan)</div>
                            <div class="text-xs text-slate-500">Bisa menambahkan topping atau instruksi khusus.</div>
                        </div>
                        <input type="checkbox" v-model="form.has_modifier" class="rounded h-5 w-5 text-primary">
                    </div>

                    <div v-if="form.product_type === 'basic'" class="flex items-center justify-between border p-3 rounded-lg">
                        <div>
                            <div class="font-bold text-sm">Memiliki Resep</div>
                            <div class="text-xs text-slate-500">Stok dipotong berdasarkan bahan baku pembentuk.</div>
                        </div>
                        <input type="checkbox" v-model="form.has_recipe" class="rounded h-5 w-5 text-primary">
                    </div>

                    <div v-if="form.product_type === 'basic' && !form.has_recipe" class="flex items-center justify-between border p-3 rounded-lg">
                        <div>
                            <div class="font-bold text-sm">Lacak Inventori (Stok)</div>
                            <div class="text-xs text-slate-500">Lacak stok masuk dan keluar untuk produk ini.</div>
                        </div>
                        <input type="checkbox" v-model="form.track_inventory" class="rounded h-5 w-5 text-primary">
                    </div>
                </div>
            </div>

            <!-- Conditional Steps -->
            <div v-show="steps[currentStep].id === 'variant'" class="space-y-3">
                <div class="font-semibold text-lg border-b pb-1">Setup Varian</div>
                <p class="text-xs text-slate-500">Tambahkan grup varian dan opsinya, sistem akan meng-generate kombinasi SKU secara otomatis.</p>
                <div class="text-danger text-sm">Form detail varian akan diintegrasikan lebih lanjut berdasarkan aturan UI progressive.</div>
            </div>

            <div v-show="steps[currentStep].id === 'recipe'" class="space-y-3">
                <div class="font-semibold text-lg border-b pb-1">Setup Resep</div>
                <p class="text-xs text-slate-500">Tentukan bahan baku yang digunakan untuk produk ini.</p>
                <div class="text-danger text-sm">Form detail resep diintegrasikan disini.</div>
            </div>

            <div v-show="steps[currentStep].id === 'bundle'" class="space-y-3">
                <div class="font-semibold text-lg border-b pb-1">Setup Paket (Bundle)</div>
                <p class="text-xs text-slate-500">Pilih produk-produk yang masuk ke dalam paket ini.</p>
                <div class="text-danger text-sm">Form pemilihan item bundle diintegrasikan disini.</div>
            </div>

            <div v-show="steps[currentStep].id === 'modifier'" class="space-y-3">
                <div class="font-semibold text-lg border-b pb-1">Pilih Modifier</div>
                <div class="grid grid-cols-2 gap-3">
                    <div v-for="mod in modifierGroups" :key="mod.id" class="flex items-center gap-2 border p-3 rounded-lg">
                        <input type="checkbox" :id="'mod-' + mod.id" :value="mod.id" v-model="selectedModifiers" class="rounded text-primary">
                        <label :for="'mod-' + mod.id" class="text-sm cursor-pointer">{{ mod.name }} ({{ mod.selection_type }})</label>
                    </div>
                </div>
            </div>

            <!-- Step Pricing -->
            <div v-show="steps[currentStep].id === 'pricing'" class="space-y-3">
                <div class="font-semibold text-lg border-b pb-1">Setup Harga</div>
                <div class="mb-4 border p-3 rounded-lg bg-slate-50">
                    <NumberField v-model="form.base_price" label="Harga Dasar (Berlaku untuk semua outlet jika tidak ditimpa)" :class="{ 'is-invalid': form.errors.base_price }" :feedback="form.errors.base_price" required />
                </div>
                
                <h3 class="font-bold text-sm">Timpa Harga per Outlet (Opsional)</h3>
                <div class="space-y-2">
                    <div v-for="outlet in outlets" :key="outlet.id" class="flex items-center gap-3">
                        <div class="w-1/3 text-sm font-medium text-slate-600">{{ outlet.name }}</div>
                        <div class="w-2/3">
                            <NumberField v-model="outletPriceMap[outlet.id]" placeholder="Biarkan kosong untuk pakai harga dasar" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step Outlet -->
            <div v-show="steps[currentStep].id === 'outlet'" class="space-y-3">
                <div class="font-semibold text-lg border-b pb-1">Distribusi Outlet</div>
                <table class="w-full text-left border rounded-lg overflow-hidden">
                    <thead>
                        <tr class="bg-slate-50">
                            <th class="p-2 border-b text-sm font-semibold text-slate-700">Outlet</th>
                            <th class="p-2 border-b text-sm font-semibold text-slate-700 text-center">Tersedia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="outlet in outlets" :key="outlet.id" class="hover:bg-slate-50/50">
                            <td class="p-2 border-b text-sm">{{ outlet.name }}</td>
                            <td class="p-2 border-b text-center">
                                <input type="checkbox" v-model="outletStatusMap[outlet.id]" class="rounded h-5 w-5 text-primary">
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </form>

        <template #footer>
            <div class="flex justify-between">
                <button 
                    type="button" 
                    class="btn btn-secondary" 
                    @click="prevStep" 
                    :disabled="currentStep === 0"
                >
                    <FontAwesomeIcon :icon="faArrowLeft" class="mr-1" />
                    Kembali
                </button>
                <div class="flex gap-2">
                    <button 
                        v-if="currentStep < steps.length - 1" 
                        type="button" 
                        class="btn btn-highlight-main" 
                        @click="nextStep"
                    >
                        Lanjut
                        <FontAwesomeIcon :icon="faArrowRight" class="ml-1" />
                    </button>
                    <button 
                        v-else 
                        type="button" 
                        class="btn btn-success" 
                        :disabled="form.processing"
                        @click="submit"
                    >
                        <FontAwesomeIcon :icon="faSave" class="mr-1" />
                        Simpan Produk
                    </button>
                </div>
            </div>
        </template>
    </Container>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Container from '@/Components/UI/Container.vue'
import TextField from '@/Components/Form/TextField.vue'
import DropdownField from '@/Components/Form/DropdownField.vue'
import NumberField from '@/Components/Form/NumberField.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faArrowLeft, faArrowRight, faSave } from '@fortawesome/free-solid-svg-icons'

const props = defineProps({
    product: Object,
    categories: Array,
    outlets: Array,
    modifierGroups: Array,
    inventoryItems: Array,
    products: Array,
})

const isEdit = computed(() => !!props.product)

const categoryOptions = computed(() => props.categories.map(c => ({ label: c.name, value: c.id })))

const form = useForm({
    name: props.product?.name || '',
    code: props.product?.code || '',
    product_category_id: props.product?.product_category_id || '',
    description: props.product?.description || '',
    product_type: props.product?.product_type || 'basic',
    is_show: props.product?.is_show ?? true,
    sellable: props.product?.sellable ?? true,
    purchasable: props.product?.purchasable ?? false,
    has_variant: props.product?.has_variant ?? false,
    has_modifier: props.product?.has_modifier ?? false,
    has_recipe: props.product?.has_recipe ?? false,
    track_inventory: props.product?.track_inventory ?? false,
    base_price: 0,
    outlet_prices: [],
    outlets: [],
    variants: [],
    variant_combinations: [],
    recipes: [],
    bundle_items: [],
    modifier_groups: [],
    stock: 0, 
})

if (isEdit.value && props.product.prices) {
    const bp = props.product.prices.find(p => !p.outlet_id && !p.inventory_item_id)
    if (bp) form.base_price = bp.amount
}

const outletPriceMap = ref({})
const outletStatusMap = ref({})
const selectedModifiers = ref([])

if (isEdit.value) {
    props.outlets.forEach(o => {
        const p = props.product.prices.find(pr => pr.outlet_id === o.id)
        if (p) outletPriceMap.value[o.id] = p.amount
        
        const out = props.product.outlets.find(out => out.id === o.id)
        outletStatusMap.value[o.id] = out ? out.pivot.is_enabled : true
    })
    
    if (props.product.modifier_groups) {
        selectedModifiers.value = props.product.modifier_groups.map(m => m.id)
    }
} else {
    props.outlets.forEach(o => {
        outletStatusMap.value[o.id] = true
    })
}

const allSteps = [
    { id: 'basic', label: 'Basic Info' },
    { id: 'type', label: 'Tipe Produk' },
    { id: 'flags', label: 'Pengaturan' },
    { id: 'variant', label: 'Setup Varian' },
    { id: 'recipe', label: 'Setup Resep' },
    { id: 'bundle', label: 'Setup Paket' },
    { id: 'modifier', label: 'Modifier' },
    { id: 'pricing', label: 'Harga' },
    { id: 'outlet', label: 'Outlet' },
]

watch(() => form.product_type, (newType) => {
    if (newType === 'service') {
        form.track_inventory = false
        form.has_variant = false
        form.has_recipe = false
    } else if (newType === 'bundle') {
        form.track_inventory = false
        form.has_variant = false
        form.has_modifier = false
        form.has_recipe = false
    }
})

const currentStep = ref(0)

const isStepVisible = (id) => {
    if (id === 'variant') return form.product_type === 'basic' && form.has_variant;
    if (id === 'recipe') return form.product_type === 'basic' && form.has_recipe;
    if (id === 'bundle') return form.product_type === 'bundle';
    if (id === 'modifier') return form.has_modifier;
    return true;
}

const steps = computed(() => {
    return allSteps.filter(s => isStepVisible(s.id))
})

const nextStep = () => {
    if (currentStep.value < steps.value.length - 1) {
        currentStep.value++
    }
}

const prevStep = () => {
    if (currentStep.value > 0) {
        currentStep.value--
    }
}

const submit = () => {
    form.outlet_prices = Object.keys(outletPriceMap.value)
        .filter(k => outletPriceMap.value[k])
        .map(k => ({ outlet_id: k, amount: outletPriceMap.value[k] }))
        
    form.outlets = Object.keys(outletStatusMap.value)
        .map(k => ({ outlet_id: k, is_enabled: outletStatusMap.value[k], is_available: outletStatusMap.value[k] }))
        
    form.modifier_groups = selectedModifiers.value.map(id => ({ modifier_group_id: id }))

    if (isEdit.value) {
        form.put(route('master.products.update', props.product.id), {
            preserveState: true,
            preserveScroll: true
        })
    } else {
        form.post(route('master.products.store'), {
            preserveState: true,
            preserveScroll: true
        })
    }
}
</script>
