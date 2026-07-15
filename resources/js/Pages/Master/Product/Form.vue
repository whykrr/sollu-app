<template>
    <Container>
        <template #header>
            <h2 class="text-2xl font-bold">
                <span v-if="!isEdit">Membuat</span>
                Data Produk
                <span v-if="!isEdit">Baru</span>
                <span v-if="isEdit" class="text-slate-400"
                    >#{{ product.code }}</span
                >
            </h2>
            <div class="flex items-center gap-2 border-b pb-2">
                <template v-for="(step, index) in steps" :key="index">
                    <div
                        v-if="isStepVisible(step.id)"
                        class="flex items-center cursor-pointer text-sm"
                        @click="currentStep = index"
                        :class="{
                            'text-primary font-bold': currentStep === index,
                            'text-slate-400': currentStep !== index,
                        }"
                    >
                        <div
                            class="w-6 h-6 rounded-full flex items-center justify-center border mr-1 text-xs"
                            :class="
                                currentStep === index
                                    ? 'border-primary bg-primary-100 text-primary'
                                    : 'border-slate-300'
                            "
                        >
                            {{ index + 1 }}
                        </div>
                        <span>{{ step.label }}</span>
                        <span
                            v-if="index < steps.length - 1"
                            class="mx-2 text-slate-300"
                            >/</span
                        >
                    </div>
                </template>
            </div>
        </template>

        <form
            @submit.prevent="submit"
            class="bg-white rounded-lg p-4 space-y-4"
        >
            <!-- Step Components -->
            <StepBasicInfo v-show="steps[currentStep].id === 'basic'" />
            <StepTypeSelection v-show="steps[currentStep].id === 'type'" />
            <StepFeatureFlags v-show="steps[currentStep].id === 'flags'" />
            <StepVariantSetup v-show="steps[currentStep].id === 'variant'" />
            <StepRecipeSetup v-show="steps[currentStep].id === 'recipe'" />
            <StepBundleSetup v-show="steps[currentStep].id === 'bundle'" />
            <StepModifierSetup v-show="steps[currentStep].id === 'modifier'" />
            <StepPricing v-show="steps[currentStep].id === 'pricing'" />
            <StepOutlet v-show="steps[currentStep].id === 'outlet'" />
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
                        v-if="currentStep < steps.length - 1 && !isEdit"
                        type="button"
                        class="btn btn-highlight-main"
                        @click="nextStep"
                    >
                        Lanjut
                        <FontAwesomeIcon :icon="faArrowRight" class="ml-1" />
                    </button>
                    <button
                        v-else-if="isEdit"
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
import { ref, computed, watch, provide } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Container from '@/Components/UI/Container.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faArrowLeft,
    faArrowRight,
    faSave,
} from '@fortawesome/free-solid-svg-icons';

// Steps Components
import StepBasicInfo from './Components/StepBasicInfo.vue';
import StepTypeSelection from './Components/StepTypeSelection.vue';
import StepFeatureFlags from './Components/StepFeatureFlags.vue';
import StepVariantSetup from './Components/StepVariantSetup.vue';
import StepRecipeSetup from './Components/StepRecipeSetup.vue';
import StepBundleSetup from './Components/StepBundleSetup.vue';
import StepModifierSetup from './Components/StepModifierSetup.vue';
import StepPricing from './Components/StepPricing.vue';
import StepOutlet from './Components/StepOutlet.vue';

const props = defineProps({
    product: Object,
    categories: Array,
    outlets: Array,
    modifierGroups: Array,
    inventoryItems: Array,
    products: Array,
});

const isEdit = computed(() => !!props.product);

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
    base_price: '',
    outlet_prices: [],
    outlets: [],
    variants: [],
    variant_combinations: [],
    recipes: [],
    bundle_items: [],
    modifier_groups: [],
    min_stock: '0',
    images: props.product?.images || [],
});

if (isEdit.value && props.product.prices) {
    const bp = props.product.prices.find(
        (p) => !p.outlet_id && !p.inventory_item_id,
    );
    if (bp) form.base_price = String(bp.amount);
}

const outletPriceMap = ref({});
const outletStatusMap = ref({});
const selectedModifiers = ref([]);
const variantOutletPriceMap = ref({});
const customizeVariantPrices = ref(false);
const customizeOutletPrices = ref(false);

const getComboKey = (comboOptions) => {
    return Object.keys(comboOptions)
        .sort()
        .map((k) => `${k}:${comboOptions[k]}`)
        .join('|');
};

const generateCombinations = (groups) => {
    const activeGroups = groups.filter(
        (g) =>
            g.name.trim() !== '' && g.options.some((o) => o.name.trim() !== ''),
    );
    if (activeGroups.length === 0) return [];

    const results = [];
    const helper = (groupIndex, currentCombo) => {
        if (groupIndex === activeGroups.length) {
            results.push(currentCombo);
            return;
        }

        const group = activeGroups[groupIndex];
        group.options.forEach((opt) => {
            if (opt.name.trim() !== '') {
                helper(groupIndex + 1, {
                    ...currentCombo,
                    [group.name]: opt.name,
                });
            }
        });
    };

    helper(0, {});
    return results;
};

const generateSku = (comboOptions) => {
    const baseCode = form.code || form.name.substring(0, 3).toUpperCase();
    const suffix = Object.values(comboOptions)
        .map((v) => v.toUpperCase().replace(/\s+/g, ''))
        .join('-');
    return `${baseCode}-${suffix}`;
};

const updateCombinations = () => {
    const newCombos = generateCombinations(form.variants);

    const existingMap = {};
    form.variant_combinations.forEach((combo) => {
        existingMap[getComboKey(combo.options)] = combo;
    });

    form.variant_combinations = newCombos.map((options) => {
        const key = getComboKey(options);
        const existing = existingMap[key];

        if (!variantOutletPriceMap.value[key]) {
            variantOutletPriceMap.value[key] = {};
        }

        return {
            options: options,
            sku: existing ? existing.sku : generateSku(options),
            barcode: existing ? existing.barcode : '',
            price: existing ? String(existing.price) : String(form.base_price),
            min_stock: existing ? String(existing.min_stock) : '0',
        };
    });
};

const autoGenerateAllSkus = () => {
    form.variant_combinations.forEach((combo) => {
        combo.sku = generateSku(combo.options);
    });
};

if (isEdit.value) {
    let hasCustomOutletPrices = false;
    props.outlets.forEach((o) => {
        const p = props.product.prices.find(
            (pr) => pr.outlet_id === o.id && !pr.inventory_item_id,
        );
        if (p) {
            outletPriceMap.value[o.id] = p.amount;
            hasCustomOutletPrices = true;
        }

        const out = props.product.outlets.find((out) => out.id === o.id);
        outletStatusMap.value[o.id] = out ? out.pivot.is_enabled : true;
    });

    if (hasCustomOutletPrices) {
        customizeOutletPrices.value = true;
    }

    if (props.product.modifier_groups) {
        selectedModifiers.value = props.product.modifier_groups.map(
            (m) => m.id,
        );
    }

    // Load existing variants
    if (
        props.product.variant_groups &&
        props.product.variant_groups.length > 0
    ) {
        form.variants = props.product.variant_groups.map((vg) => ({
            name: vg.name,
            options: vg.options.map((opt) => ({ name: opt.name })),
        }));

        if (props.product.inventory_items) {
            const variantItems = props.product.inventory_items.filter(
                (item) => item.item_type === 'variant_sku',
            );
            if (variantItems.length > 0) {
                let hasCustomPrices = false;
                form.variant_combinations = variantItems.map((invItem) => {
                    const combinationOptions = {};
                    invItem.variant_group_options.forEach((opt) => {
                        const vg = props.product.variant_groups.find(
                            (g) => g.id === opt.variant_group_id,
                        );
                        const gName = vg ? vg.name : '';
                        if (gName) {
                            combinationOptions[gName] = opt.name;
                        }
                    });

                    const priceObj = props.product.prices.find(
                        (p) =>
                            p.inventory_item_id === invItem.id && !p.outlet_id,
                    );
                    const price = priceObj ? priceObj.amount : form.base_price;
                    if (
                        priceObj &&
                        Number(priceObj.amount) !== Number(form.base_price)
                    ) {
                        hasCustomPrices = true;
                    }

                    const comboKey = getComboKey(combinationOptions);
                    variantOutletPriceMap.value[comboKey] = {};

                    props.outlets.forEach((outlet) => {
                        const op = props.product.prices.find(
                            (p) =>
                                p.inventory_item_id === invItem.id &&
                                p.outlet_id === outlet.id,
                        );
                        if (op) {
                            variantOutletPriceMap.value[comboKey][outlet.id] =
                                op.amount;
                            hasCustomPrices = true;
                        }
                    });

                    return {
                        options: combinationOptions,
                        sku: invItem.sku || '',
                        barcode: invItem.barcode || '',
                        price: String(price),
                        min_stock: String(
                            invItem.min_stock ?? 0,
                        ),
                    };
                });

                if (hasCustomPrices) {
                    customizeVariantPrices.value = true;
                }
            }
        }
    } else {
        form.variants = [{ name: '', options: [{ name: '' }] }];
    }
} else {
    props.outlets.forEach((o) => {
        outletStatusMap.value[o.id] = true;
    });

    form.variants = [{ name: '', options: [{ name: '' }] }];
}

// Provide states and helpers to Step subcomponents
provide('productForm', form);
provide('isEdit', isEdit);
provide('categories', props.categories);
provide('outlets', props.outlets);
provide('modifierGroups', props.modifierGroups);
provide('inventoryItems', props.inventoryItems);
provide('products', props.products);

provide('outletPriceMap', outletPriceMap);
provide('outletStatusMap', outletStatusMap);
provide('selectedModifiers', selectedModifiers);
provide('variantOutletPriceMap', variantOutletPriceMap);
provide('customizeVariantPrices', customizeVariantPrices);
provide('customizeOutletPrices', customizeOutletPrices);
provide('getComboKey', getComboKey);
provide('autoGenerateAllSkus', autoGenerateAllSkus);

// Watch variants deeply to update combinations
watch(
    () => form.variants,
    () => {
        updateCombinations();
    },
    { deep: true },
);

// Update combination prices if base price changes (unless already manually customized)
watch(
    () => form.base_price,
    (newVal) => {
        form.variant_combinations.forEach((combo) => {
            if (!combo.price || combo.price == 0) {
                combo.price = newVal;
            }
        });
    },
);

const allSteps = [
    { id: 'basic', label: 'Informasi Dasar' },
    { id: 'type', label: 'Tipe Produk' },
    { id: 'flags', label: 'Pengaturan' },
    { id: 'variant', label: 'Setup Varian' },
    { id: 'recipe', label: 'Setup Resep' },
    { id: 'bundle', label: 'Setup Paket' },
    { id: 'modifier', label: 'Modifier' },
    { id: 'pricing', label: 'Harga' },
    { id: 'outlet', label: 'Outlet' },
];

watch(
    () => form.product_type,
    (newType) => {
        if (newType === 'service') {
            form.track_inventory = false;
            form.has_variant = false;
            form.has_recipe = false;
        } else if (newType === 'bundle') {
            form.track_inventory = false;
            form.has_variant = false;
            form.has_modifier = false;
            form.has_recipe = false;
        }
    },
);

const currentStep = ref(0);

const isStepVisible = (id) => {
    if (id === 'variant')
        return form.product_type === 'basic' && form.has_variant;
    if (id === 'recipe')
        return form.product_type === 'basic' && form.has_recipe;
    if (id === 'bundle') return form.product_type === 'bundle';
    if (id === 'modifier') return form.has_modifier;
    return true;
};

const steps = computed(() => {
    return allSteps.filter((s) => isStepVisible(s.id));
});

const nextStep = () => {
    if (currentStep.value < steps.value.length - 1) {
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 0) {
        currentStep.value--;
    }
};

const submit = () => {
    if (!form.has_variant) {
        if (customizeOutletPrices.value) {
            form.outlet_prices = Object.keys(outletPriceMap.value)
                .filter((k) => outletPriceMap.value[k])
                .map((k) => ({
                    outlet_id: k,
                    amount: outletPriceMap.value[k],
                }));
        } else {
            form.outlet_prices = [];
        }

        form.variants = [];
        form.variant_combinations = [];
    } else {
        form.outlet_prices = [];
        if (customizeVariantPrices.value) {
            form.variant_combinations.forEach((combo) => {
                const key = getComboKey(combo.options);
                const map = variantOutletPriceMap.value[key] || {};
                combo.outlet_prices = Object.keys(map)
                    .filter(
                        (oId) =>
                            map[oId] !== undefined &&
                            map[oId] !== null &&
                            map[oId] !== '',
                    )
                    .map((oId) => ({
                        outlet_id: oId,
                        amount: map[oId],
                    }));
            });
        } else {
            form.variant_combinations.forEach((combo) => {
                combo.price = form.base_price;
                combo.outlet_prices = [];
            });
        }
    }

    form.outlets = Object.keys(outletStatusMap.value).map((k) => ({
        outlet_id: k,
        is_enabled: outletStatusMap.value[k],
        is_available: outletStatusMap.value[k],
    }));

    form.modifier_groups = selectedModifiers.value.map((id) => ({
        modifier_group_id: id,
    }));

    if (isEdit.value) {
        form.transform((data) => ({
            ...data,
            _method: 'PUT',
        })).post(route('master.products.update', props.product.id), {
            preserveState: true,
            preserveScroll: true,
        });
    } else {
        form.post(route('master.products.store'), {
            preserveState: true,
            preserveScroll: true,
        });
    }
};
</script>
