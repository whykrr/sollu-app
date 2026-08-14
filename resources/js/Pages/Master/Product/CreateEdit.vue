<template>
    <div>
        <!-- Stepper indicator -->
        <div v-if="!isEdit" class="mb-2">
            <div class="flex items-center justify-between">
                <template v-for="(step, index) in steps" :key="step.id">
                    <div class="flex flex-col items-center">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold transition-colors"
                            :class="[
                                index < currentStepIndex
                                    ? 'bg-main text-white'
                                    : '',
                                index === currentStepIndex
                                    ? 'bg-main text-white ring-2 ring-main/20'
                                    : '',
                                index > currentStepIndex
                                    ? 'bg-neutral-100 text-neutral-500'
                                    : '',
                            ]"
                        >
                            {{ index + 1 }}
                        </div>
                        <span
                            class="mt-2 text-xs font-medium text-neutral-500"
                            :class="{ 'text-main': index === currentStepIndex }"
                        >
                            {{ step.title }}
                        </span>
                    </div>
                    <div
                        v-if="index < steps.length - 1"
                        class="h-1 flex-1 bg-neutral-100 mx-2 rounded-full overflow-hidden"
                    >
                        <div
                            class="h-full bg-main transition-all"
                            :style="{
                                width: index < currentStepIndex ? '100%' : '0%',
                            }"
                        ></div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Dynamic Component -->
        <div class="min-h-[400px]">
            <component :is="currentStep.component" />
        </div>
    </div>

    <Teleport v-if="isMounted" to="#popUpFooter">
        <div class="flex items-center w-full gap-2" :class="isEdit ? 'justify-end' : 'justify-between'">
            <template v-if="!isEdit">
                <button
                    type="button"
                    class="btn btn-outline-main"
                    :disabled="isFirstStep"
                    :class="{ 'opacity-50 cursor-not-allowed': isFirstStep }"
                    @click="prevStep"
                >
                    <FontAwesomeIcon :icon="faChevronLeft" class="mr-2" />
                    Kembali
                </button>

                <button
                    v-if="!isLastStep"
                    type="button"
                    class="btn btn-highlight-main"
                    @click="nextStep"
                >
                    Lanjut
                    <FontAwesomeIcon :icon="faChevronRight" class="ml-2" />
                </button>

                <button
                    v-else
                    type="button"
                    class="btn btn-success"
                    :disabled="form.processing"
                    @click="submit"
                >
                    <FontAwesomeIcon :icon="faSave" class="mr-2" />
                    Simpan Produk
                </button>
            </template>
            <template v-else>
                <button
                    type="button"
                    class="btn btn-success"
                    :disabled="form.processing"
                    @click="submit"
                >
                    <FontAwesomeIcon :icon="faSave" class="mr-2" />
                    Simpan Perubahan
                </button>
            </template>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch, provide, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faChevronLeft,
    faChevronRight,
    faSave,
} from '@fortawesome/free-solid-svg-icons';
import { usePopUpStore } from '@/store/popup';

import StepBasicInfo from './Components/StepBasicInfo.vue';
import StepInventorySetup from './Components/StepInventorySetup.vue';
import StepPricing from './Components/StepPricing.vue';
import { inject } from 'vue';

const props = defineProps({
    editMode: { type: Boolean, default: false },
    product: { type: Object, default: null },
    initialStep: { type: Number, default: 0 },
    targetStepId: { type: String, default: null },
    categories: { type: Array, default: () => [] },
    outlets: { type: Array, default: () => [] },
    uoms: { type: Array, default: () => [] },
});

const popUpStore = usePopUpStore();

const isEdit = computed(() => props.editMode);
const isMounted = ref(false);

onMounted(() => {
    isMounted.value = true;
});

const getInitialUomId = () => {
    if (props.product?.inventory_items?.length > 0) {
        const item = props.product.inventory_items.find((item) => item.uom_id);
        return item ? item.uom_id : '';
    }
    return '';
};

const getInitialMinStock = () => {
    if (props.product?.inventory_items?.length > 0) {
        const item = props.product.inventory_items.find(
            (item) => item.min_stock !== null && item.min_stock !== undefined,
        );
        return item ? String(item.min_stock) : '0';
    }
    return '0';
};

const getInitialBarcode = () => {
    if (props.product?.inventory_items?.length > 0) {
        const item = props.product.inventory_items.find((item) => item.barcode);
        return item ? item.barcode : '';
    }
    return '';
};

const form = useForm({
    id: props.product?.id || null,
    name: props.product?.name || '',
    code: props.product?.code || '',
    product_category_id: props.product?.product_category_id || '',
    description: props.product?.description || '',
    product_type: props.product?.product_type || 'basic',
    is_show: props.product?.is_show ?? true,
    sellable: props.product?.sellable ?? true,
    purchasable: props.product?.purchasable ?? false,
    has_variant: props.product?.has_variant ?? false,
    track_inventory: props.product?.track_inventory ?? false,
    uom_id: getInitialUomId(),
    barcode: getInitialBarcode(),
    base_price: '',
    outlet_prices: [],
    outlets: [],
    variants: [],
    variant_combinations: [],
    min_stock: getInitialMinStock(),
    images: props.product?.images || [],
});

if (isEdit.value && props.product?.prices) {
    const bp = props.product.prices.find((p) => !p.outlet_id);
    if (bp) form.base_price = String(bp.amount);
}

const outletPriceMap = ref({});
const outletStatusMap = ref({});
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
        if (!variantOutletPriceMap.value[key])
            variantOutletPriceMap.value[key] = {};
        return {
            options: options,
            sku: existing ? existing.sku : generateSku(options),
            barcode: existing ? existing.barcode : '',
            price: existing ? String(existing.price) : String(form.base_price),
            min_stock: existing ? String(existing.min_stock) : '0',
            image_url: existing ? existing.image_url : null,
        };
    });
};

const autoGenerateAllSkus = () => {
    form.variant_combinations.forEach((combo) => {
        combo.sku = generateSku(combo.options);
    });
};

if (isEdit.value && props.product) {
    let hasCustomOutletPrices = false;
    props.outlets.forEach((o) => {
        const p = props.product.prices.find((pr) => pr.outlet_id === o.id);
        if (p) {
            outletPriceMap.value[o.id] = p.amount;
            hasCustomOutletPrices = true;
        }
        const out = props.product.outlets?.find((out) => out.id === o.id);
        outletStatusMap.value[o.id] = out ? out.pivot.is_enabled : true;
    });
    if (hasCustomOutletPrices) customizeOutletPrices.value = true;

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
                        if (gName) combinationOptions[gName] = opt.name;
                    });
                    const priceObj = props.product.prices.find(
                        (p) =>
                            p.inventory_item_id === invItem.id && !p.outlet_id,
                    );
                    const price = priceObj ? priceObj.amount : form.base_price;
                    if (
                        priceObj &&
                        Number(priceObj.amount) !== Number(form.base_price)
                    )
                        hasCustomPrices = true;
                    const comboKey = getComboKey(combinationOptions);
                    variantOutletPriceMap.value[comboKey] = {};

                    const itemImage = props.product.images?.find(
                        (img) => img.inventory_item_id === invItem.id,
                    );

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
                        min_stock: String(invItem.min_stock ?? 0),
                        image_url: itemImage ? itemImage.image_url : null,
                    };
                });
                if (hasCustomPrices) customizeVariantPrices.value = true;
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

provide('productForm', form);
provide('isEdit', isEdit);
provide('originalProduct', props.product);
provide(
    'categories',
    computed(() => props.categories),
);
provide(
    'outlets',
    computed(() => props.outlets),
);
provide(
    'uoms',
    computed(() => props.uoms),
);
provide('outletPriceMap', outletPriceMap);
provide('outletStatusMap', outletStatusMap);
provide('variantOutletPriceMap', variantOutletPriceMap);
provide('customizeVariantPrices', customizeVariantPrices);
provide('customizeOutletPrices', customizeOutletPrices);
provide('getComboKey', getComboKey);
provide('autoGenerateAllSkus', autoGenerateAllSkus);

watch(
    () => form.variants,
    () => {
        updateCombinations();
    },
    { deep: true },
);
watch(
    () => form.track_inventory,
    (track) => {
        if (!track) form.uom_id = '';
    },
);
watch(
    () => form.base_price,
    (newVal) => {
        form.variant_combinations.forEach((combo) => {
            if (!combo.price || combo.price == 0) combo.price = newVal;
        });
    },
);

const steps = computed(() => {
    let s = [
        { id: 'basic', component: StepBasicInfo, title: 'Informasi Dasar' },
    ];
    if (form.product_type === 'basic' && (form.track_inventory || form.has_variant)) {
        s.push({
            id: 'inventory',
            component: StepInventorySetup,
            title: 'Setup Inventori',
        });
    }
    s.push({ id: 'pricing', component: StepPricing, title: 'Harga & Outlet' });
    return s;
});

const currentStepIndex = ref(0);
watch(
    () => steps.value,
    () => {
        if (props.targetStepId) {
            const idx = steps.value.findIndex(
                (s) => s.id === props.targetStepId,
            );
            if (idx !== -1) {
                currentStepIndex.value = idx;
                return;
            }
        }
        if (currentStepIndex.value >= steps.value.length) {
            currentStepIndex.value = steps.value.length - 1;
        }
    },
    { immediate: true },
);

const currentStep = computed(() => steps.value[currentStepIndex.value]);
const isFirstStep = computed(() => currentStepIndex.value === 0);
const isLastStep = computed(
    () => currentStepIndex.value === steps.value.length - 1,
);

const nextStep = () => {
    if (!isLastStep.value) currentStepIndex.value++;
};
const prevStep = () => {
    if (!isFirstStep.value) currentStepIndex.value--;
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
                    .map((oId) => ({ outlet_id: oId, amount: map[oId] }));
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

    if (isEdit.value && props.product?.id) {
        form.transform((data) => ({ ...data, _method: 'PUT' })).post(
            route('master.products.update', props.product.id),
            {
                onSuccess: () => popUpStore.close(),
            },
        );
    } else {
        form.post(route('master.products.store'), {
            onSuccess: () => popUpStore.close(),
        });
    }
};
</script>
