<template>
    <div class="space-y-3.5 pb-16">
        <!-- Input Dasar Utama: Foto, Nama, Kategori, Harga Jual Dasar, Pengaturan Lanjutan -->
        <ProductBasicSection id="section-basic" />

        <!-- Card 1: Stok & Inventori (FeatureEnum: INVENTORY_MANAGEMENT) -->
        <ProductInventorySection id="card-inventory" v-model:is-open="isInventoryOpen" />

        <!-- Card 2: Varian Produk (FeatureEnum: PRODUCT_VARIANT) -->
        <ProductVariantSection id="card-variant" v-model:is-open="isVariantOpen" />

        <!-- Card 3: Matriks Harga (Kondisional: Cabang > 1 ATAU Varian Aktif, FeatureEnum: PRODUCT_PRICE_MATRIX) -->
        <ProductPricingMatrixSection
            v-if="showPricingMatrix"
            id="card-pricing"
            v-model:is-open="isPricingOpen"
        />

        <!-- Card 4: Resep BOM (FeatureEnum: PRODUCT_BOM) -->
        <ProductRecipeSection id="card-recipe" v-model:is-open="isRecipeOpen" />

        <!-- Card 5: Opsi Tambahan / Modifier (FeatureEnum: PRODUCT_MODIFIER) -->
        <ProductModifierSection id="card-modifier" v-model:is-open="isModifierOpen" />
    </div>

    <!-- Sticky Footer Actions (Thumb Zone Ergonomics, Teleported to #popUpFooter) -->
    <Teleport v-if="isMounted" to="#popUpFooter">
        <div class="flex items-center justify-between w-full gap-2">
            <button
                type="button"
                class="btn btn-flat"
                :disabled="form.processing"
                @click="handleCancel"
            >
                Batal
            </button>

            <button type="button" class="btn btn-main" :disabled="form.processing" @click="submit">
                <FontAwesomeIcon
                    :icon="form.processing ? faSpinner : faSave"
                    :class="{ 'animate-spin': form.processing }"
                    class="mr-1"
                />
                <span>Simpan</span>
            </button>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch, provide, nextTick, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faSave, faSpinner } from '@fortawesome/free-solid-svg-icons'
import { useAuth } from '@/Composable/useAuth'
import { useFormDirtyGuard } from '@/Composable/useFormDirtyGuard'

import ProductBasicSection from './Components/ProductBasicSection.vue'
import ProductInventorySection from './Components/ProductInventorySection.vue'
import ProductVariantSection from './Components/ProductVariantSection.vue'
import ProductPricingMatrixSection from './Components/ProductPricingMatrixSection.vue'
import ProductRecipeSection from './Components/ProductRecipeSection.vue'
import ProductModifierSection from './Components/ProductModifierSection.vue'

defineEmits(['close'])

const props = defineProps({
    editMode: { type: Boolean, default: false },
    product: { type: Object, default: null },
    targetStepId: { type: String, default: null },
    categories: { type: Array, default: () => [] },
    outlets: { type: Array, default: () => [] },
    uoms: { type: Array, default: () => [] },
    modifierGroups: { type: Array, default: () => [] },
    rawMaterials: { type: Array, default: () => [] },
})

const { selectedOutlet } = useAuth()

const isEdit = computed(() => props.editMode)
const isMounted = ref(false)

const getInitialUomId = () => {
    const items = props.product?.product_items || props.product?.inventory_items || []
    if (items.length > 0) {
        const item = items.find(item => item.uom_id)
        return item ? item.uom_id : ''
    }
    return ''
}

const getInitialBarcode = () => {
    const items = props.product?.product_items || props.product?.inventory_items || []
    if (items.length > 0) {
        const item = items.find(item => item.barcode)
        return item ? item.barcode : ''
    }
    return ''
}

const getInitialRecipeItems = () => {
    const rawItems =
        props.product?.active_recipe?.items ||
        props.product?.recipe_versions?.[0]?.items ||
        props.product?.recipe_items ||
        []
    return rawItems.map(item => ({
        product_item_id: item.product_item_id,
        qty: item.qty,
        uom: item.uom || item.product_item?.uom?.name || 'Pcs',
    }))
}

const getInitialModifierGroupIds = () => {
    if (Array.isArray(props.product?.modifier_groups)) {
        return props.product.modifier_groups.map(g => g.id)
    }
    return []
}

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
    has_variant: Boolean(props.product?.has_variant),
    track_inventory: Boolean(props.product?.track_inventory),
    has_recipe: Boolean(props.product?.has_recipe),
    has_modifier: Boolean(props.product?.has_modifier),
    uom_id: getInitialUomId(),
    barcode: getInitialBarcode(),
    base_price: '',
    minimum_stock: props.product?.minimum_stock !== undefined && props.product?.minimum_stock !== null
        ? String(props.product.minimum_stock)
        : '',
    can_edit_initial_stock: true,
    initial_stock: '',
    purchase_price: '',
    initial_stocks: [],
    outlet_prices: [],
    outlets: [],
    variants: [],
    variant_combinations: [],
    recipe_items: getInitialRecipeItems(),
    recipes: [],
    modifier_group_ids: getInitialModifierGroupIds(),
    modifier_groups: [],
    images: props.product?.images || [],
})

const { handleCancel, forceClose } = useFormDirtyGuard({ form })

if (isEdit.value && props.product?.prices) {
    const bp = props.product.prices.find(p => !p.outlet_id)
    if (bp) form.base_price = String(bp.amount)
}

const outletPriceMap = ref({})
const outletStatusMap = ref({})
const variantOutletPriceMap = ref({})
const customizeVariantPrices = ref(false)
const customizeOutletPrices = ref(false)
const syncAllOutlets = ref(false)

const getComboKey = comboOptions => {
    return Object.keys(comboOptions)
        .sort()
        .map(k => `${k}:${comboOptions[k]}`)
        .join('|')
}

const generateCombinations = groups => {
    const activeGroups = groups.filter(
        g => g.name.trim() !== '' && g.options.some(o => o.name.trim() !== '')
    )
    if (activeGroups.length === 0) return []
    const results = []
    const helper = (groupIndex, currentCombo) => {
        if (groupIndex === activeGroups.length) {
            results.push(currentCombo)
            return
        }
        const group = activeGroups[groupIndex]
        group.options.forEach(opt => {
            if (opt.name.trim() !== '') {
                helper(groupIndex + 1, {
                    ...currentCombo,
                    [group.name]: opt.name,
                })
            }
        })
    }
    helper(0, {})
    return results
}

const generateSku = comboOptions => {
    const rawPrefix = form.code?.trim() || form.name?.trim().substring(0, 3).toUpperCase() || 'PRD'
    const baseCode = rawPrefix.toUpperCase().replace(/\s+/g, '-')
    const suffix = Object.values(comboOptions)
        .map(v => String(v).toUpperCase().replace(/\s+/g, ''))
        .join('-')
    return `${baseCode}-${suffix}`
}

const updateCombinations = () => {
    const newCombos = generateCombinations(form.variants)
    const existingMap = {}
    form.variant_combinations.forEach(combo => {
        existingMap[getComboKey(combo.options)] = combo
    })

    form.variant_combinations = newCombos.map(options => {
        const key = getComboKey(options)
        const existing = existingMap[key]
        if (!variantOutletPriceMap.value[key]) variantOutletPriceMap.value[key] = {}
        return {
            options: options,
            sku: existing ? existing.sku : generateSku(options),
            barcode: existing ? existing.barcode : '',
            price: existing ? String(existing.price) : String(form.base_price),
            initial_stock: existing ? existing.initial_stock : '',
            purchase_price: existing ? existing.purchase_price : '',
            can_edit_initial_stock: existing ? existing.can_edit_initial_stock : true,
            track_inventory: existing ? existing.track_inventory : Boolean(form.track_inventory),
            sellable: existing ? existing.sellable : Boolean(form.sellable),
            is_active: existing ? existing.is_active : true,
            is_show: existing ? existing.is_show : Boolean(form.is_show),
            image_url: existing ? existing.image_url : null,
        }
    })
}

const autoGenerateAllSkus = () => {
    form.variant_combinations.forEach(combo => {
        combo.sku = generateSku(combo.options)
    })
}

if (isEdit.value && props.product) {
    let hasCustomOutletPrices = false
    props.outlets.forEach(o => {
        const p = props.product.prices.find(pr => pr.outlet_id === o.id)
        if (p) {
            outletPriceMap.value[o.id] = p.amount
            hasCustomOutletPrices = true
        }
        const out = props.product.outlets?.find(out => out.id === o.id)
        outletStatusMap.value[o.id] = out ? out.pivot.is_enabled : true
    })
    if (hasCustomOutletPrices) customizeOutletPrices.value = true

    if (props.product.variant_groups && props.product.variant_groups.length > 0) {
        form.variants = props.product.variant_groups.map(vg => ({
            name: vg.name,
            options: vg.options.map(opt => ({ name: opt.name })),
        }))
        const items = props.product.product_items || props.product.inventory_items || []
        const variantItems = items.filter(item => item.item_type === 'variant_sku')

        const generatedCombos = generateCombinations(form.variants)
        let hasCustomPrices = false

        form.variant_combinations = generatedCombos.map(comboOptions => {
            const comboKey = getComboKey(comboOptions)
            const optionValues = Object.values(comboOptions)
            const comboSuffix = optionValues.join(' - ')
            const expectedFullName = `${props.product.name} - ${comboSuffix}`

            const matchingItem =
                variantItems.find(item => item.name === expectedFullName) ||
                variantItems.find(item => item.name && item.name.endsWith(comboSuffix)) ||
                variantItems.find(item => item.sku === generateSku(comboOptions))

            let price = form.base_price
            let imageUrl = null

            variantOutletPriceMap.value[comboKey] = {}

            if (matchingItem) {
                const priceObj = props.product.prices?.find(
                    p =>
                        (p.product_item_id === matchingItem.id ||
                            p.inventory_item_id === matchingItem.id) &&
                        !p.outlet_id
                )
                if (priceObj) {
                    price = priceObj.amount
                    if (Number(priceObj.amount) !== Number(form.base_price)) {
                        hasCustomPrices = true
                    }
                }

                const itemImage = props.product.images?.find(
                    img =>
                        img.product_item_id === matchingItem.id ||
                        img.inventory_item_id === matchingItem.id
                )
                if (itemImage) {
                    imageUrl = itemImage.image_url
                }

                props.outlets.forEach(outlet => {
                    const op = props.product.prices?.find(
                        p =>
                            (p.product_item_id === matchingItem.id ||
                                p.inventory_item_id === matchingItem.id) &&
                            p.outlet_id === outlet.id
                    )
                    if (op) {
                        variantOutletPriceMap.value[comboKey][outlet.id] = op.amount
                        hasCustomPrices = true
                    }
                })
            }

            return {
                options: comboOptions,
                sku: matchingItem ? matchingItem.sku || '' : generateSku(comboOptions),
                barcode: matchingItem ? matchingItem.barcode || '' : '',
                price: String(price),
                initial_stock: matchingItem?.initial_stock ? String(matchingItem.initial_stock) : '',
                purchase_price: matchingItem?.purchase_price ? String(matchingItem.purchase_price) : '',
                can_edit_initial_stock: matchingItem?.can_edit_initial_stock ?? true,
                track_inventory: matchingItem
                    ? Boolean(matchingItem.track_inventory)
                    : Boolean(form.track_inventory),
                sellable: matchingItem ? Boolean(matchingItem.sellable) : Boolean(form.sellable),
                is_active: matchingItem ? Boolean(matchingItem.is_active) : true,
                is_show: matchingItem ? Boolean(matchingItem.is_show) : Boolean(form.is_show),
                image_url: imageUrl,
            }
        })

        if (hasCustomPrices) customizeVariantPrices.value = true
    } else {
        form.variants = [{ name: '', options: [{ name: '' }] }]
        const defaultItem = props.product.product_items?.find(item => !item.variant_combination) || props.product.product_items?.[0]
        if (defaultItem) {
            form.can_edit_initial_stock = defaultItem.can_edit_initial_stock ?? true
            form.initial_stock = defaultItem.initial_stock ? String(defaultItem.initial_stock) : ''
            form.purchase_price = defaultItem.purchase_price ? String(defaultItem.purchase_price) : ''
        }
    }

    const defaultProductItem = props.product?.product_items?.find(item => !item.variant_combination) || props.product?.product_items?.[0]
    const stocksMap = defaultProductItem?.initial_stocks_by_outlet || {}

    form.initial_stocks = props.outlets.map(o => {
        const existingStock = stocksMap[o.id]
        return {
            outlet_id: o.id,
            outlet_name: o.name,
            initial_stock: existingStock?.initial_stock ? String(existingStock.initial_stock) : '',
            purchase_price: existingStock?.purchase_price ? String(existingStock.purchase_price) : '',
            can_edit: existingStock ? Boolean(existingStock.can_edit_initial_stock) : true,
        }
    })
} else {
    if (selectedOutlet.value) {
        props.outlets.forEach(o => {
            outletStatusMap.value[o.id] = o.id === selectedOutlet.value.id
        })
    } else {
        props.outlets.forEach(o => {
            outletStatusMap.value[o.id] = true
        })
    }
    form.variants = [{ name: '', options: [{ name: '' }] }]

    form.initial_stocks = props.outlets.map(o => ({
        outlet_id: o.id,
        outlet_name: o.name,
        initial_stock: '',
        purchase_price: '',
        can_edit: true,
    }))
}

provide('productForm', form)
provide('isEdit', isEdit)
provide('originalProduct', props.product)
provide(
    'categories',
    computed(() => props.categories)
)
provide(
    'outlets',
    computed(() => props.outlets)
)
provide(
    'uoms',
    computed(() => props.uoms)
)
provide(
    'modifierGroups',
    computed(() => props.modifierGroups)
)
provide(
    'rawMaterials',
    computed(() => props.rawMaterials)
)
provide('outletPriceMap', outletPriceMap)
provide('outletStatusMap', outletStatusMap)
provide('variantOutletPriceMap', variantOutletPriceMap)
provide('customizeVariantPrices', customizeVariantPrices)
provide('customizeOutletPrices', customizeOutletPrices)
provide('syncAllOutlets', syncAllOutlets)
provide('getComboKey', getComboKey)
provide('autoGenerateAllSkus', autoGenerateAllSkus)

watch(
    () => [
        syncAllOutlets.value,
        form.initial_stocks?.[0]?.initial_stock,
        form.initial_stocks?.[0]?.purchase_price,
    ],
    ([sync, firstStock, firstPrice]) => {
        if (sync && Array.isArray(form.initial_stocks) && form.initial_stocks.length > 1) {
            form.initial_stocks.slice(1).forEach(item => {
                if (item.can_edit) {
                    item.initial_stock = firstStock
                    item.purchase_price = firstPrice
                }
            })
        }
    }
)

watch(
    () => form.sellable,
    isSellable => {
        if (!isSellable) {
            form.is_show = false
            if (Array.isArray(form.variant_combinations)) {
                form.variant_combinations.forEach(combo => {
                    combo.sellable = false
                    combo.is_show = false
                })
            }
        }
    }
)

watch(
    () => form.is_show,
    isShow => {
        if (isShow && !form.sellable) {
            form.is_show = false
        }
    }
)

watch(
    () => form.variants,
    () => {
        updateCombinations()
    },
    { deep: true }
)

watch(
    () => form.track_inventory,
    track => {
        if (!track) form.uom_id = ''
    }
)

watch(
    () => form.base_price,
    newVal => {
        form.variant_combinations.forEach(combo => {
            if (!combo.price || combo.price == 0) combo.price = newVal
        })
    }
)

const showPricingMatrix = computed(() => {
    return (props.outlets && props.outlets.length > 1) || Boolean(form.has_variant)
})

// Progressive Disclosure Cards State
const isInventoryOpen = ref(Boolean(form.track_inventory))
const isVariantOpen = ref(Boolean(form.has_variant))
const isPricingOpen = ref(Boolean(customizeOutletPrices.value || customizeVariantPrices.value))
const isRecipeOpen = ref(Boolean(form.has_recipe))
const isModifierOpen = ref(Boolean(form.has_modifier))

const scrollToElement = id => {
    const el = document.getElementById(id)
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
}

const applyTargetStep = targetId => {
    if (!targetId || targetId === 'basic') return

    if (targetId === 'inventory') {
        isInventoryOpen.value = true
        nextTick(() => scrollToElement('card-inventory'))
    } else if (targetId === 'variant') {
        isVariantOpen.value = true
        nextTick(() => scrollToElement('card-variant'))
    } else if (targetId === 'pricing') {
        isPricingOpen.value = true
        nextTick(() => scrollToElement('card-pricing'))
    } else if (targetId === 'recipe') {
        isRecipeOpen.value = true
        nextTick(() => scrollToElement('card-recipe'))
    } else if (targetId === 'modifier') {
        isModifierOpen.value = true
        nextTick(() => scrollToElement('card-modifier'))
    }
}

watch(
    () => props.targetStepId,
    newId => {
        if (newId) {
            applyTargetStep(newId)
        }
    }
)

onMounted(() => {
    isMounted.value = true
    if (props.targetStepId) {
        setTimeout(() => {
            applyTargetStep(props.targetStepId)
        }, 150)
    }
})

// Auto-expand card on Server Validation Error (422)
watch(
    () => form.errors,
    errors => {
        if (!errors || Object.keys(errors).length === 0) return

        let firstTarget = null

        if (errors.uom_id || errors.track_inventory) {
            isInventoryOpen.value = true
            if (!firstTarget) firstTarget = 'card-inventory'
        }
        if (
            errors.variants ||
            errors.variant_combinations ||
            Object.keys(errors).some(k => k.startsWith('variant_combinations.'))
        ) {
            isVariantOpen.value = true
            if (!firstTarget) firstTarget = 'card-variant'
        }
        if (errors.outlet_prices || Object.keys(errors).some(k => k.includes('outlet_prices'))) {
            isPricingOpen.value = true
            if (!firstTarget) firstTarget = 'card-pricing'
        }
        if (
            errors.recipes ||
            errors.recipe_items ||
            Object.keys(errors).some(k => k.startsWith('recipes.'))
        ) {
            isRecipeOpen.value = true
            if (!firstTarget) firstTarget = 'card-recipe'
        }
        if (
            errors.modifier_groups ||
            errors.modifier_group_ids ||
            Object.keys(errors).some(k => k.startsWith('modifier_groups.'))
        ) {
            isModifierOpen.value = true
            if (!firstTarget) firstTarget = 'card-modifier'
        }

        if (firstTarget) {
            nextTick(() => scrollToElement(firstTarget))
        }
    },
    { deep: true }
)

const submit = () => {
    if (!form.has_variant) {
        if (customizeOutletPrices.value) {
            form.outlet_prices = Object.keys(outletPriceMap.value)
                .filter(k => outletPriceMap.value[k])
                .map(k => ({
                    outlet_id: k,
                    amount: outletPriceMap.value[k],
                }))
        } else {
            form.outlet_prices = []
        }
        form.variants = []
        form.variant_combinations = []
    } else {
        form.outlet_prices = []
        if (customizeVariantPrices.value) {
            form.variant_combinations.forEach(combo => {
                const key = getComboKey(combo.options)
                const map = variantOutletPriceMap.value[key] || {}
                combo.outlet_prices = Object.keys(map)
                    .filter(oId => map[oId] !== undefined && map[oId] !== null && map[oId] !== '')
                    .map(oId => ({ outlet_id: oId, amount: map[oId] }))
            })
        } else {
            form.variant_combinations.forEach(combo => {
                combo.price = form.base_price
                combo.outlet_prices = []
            })
        }
    }

    form.outlets = Object.keys(outletStatusMap.value).map(k => ({
        outlet_id: k,
        is_enabled: outletStatusMap.value[k],
        is_available: outletStatusMap.value[k],
    }))

    if (Array.isArray(form.initial_stocks) && form.initial_stocks.length > 0) {
        form.initial_stocks = form.initial_stocks
            .filter(s => outletStatusMap.value[s.outlet_id] !== false)
            .map(s => ({
                outlet_id: s.outlet_id,
                qty: s.initial_stock !== '' && s.initial_stock !== null ? Number(s.initial_stock) : null,
                initial_stock: s.initial_stock !== '' && s.initial_stock !== null ? Number(s.initial_stock) : null,
                purchase_price: s.purchase_price !== '' && s.purchase_price !== null ? Number(s.purchase_price) : null,
                unit_cost: s.purchase_price !== '' && s.purchase_price !== null ? Number(s.purchase_price) : null,
            }))

        const firstStock = form.initial_stocks[0]
        if (firstStock) {
            form.initial_stock = firstStock.initial_stock
            form.purchase_price = firstStock.purchase_price
        }
    }

    // Prepare modifier groups payload
    form.has_modifier = Boolean(form.has_modifier)
    if (form.has_modifier) {
        form.modifier_groups = (form.modifier_group_ids || []).map(id => ({
            modifier_group_id: id,
        }))
    } else {
        form.modifier_groups = []
        form.modifier_group_ids = []
    }

    // Prepare recipe items (BOM) payload
    form.has_recipe = Boolean(form.has_recipe)
    if (form.has_recipe) {
        form.recipes = (form.recipe_items || [])
            .filter(item => item.product_item_id || item.qty)
            .map(item => ({
                product_item_id: item.product_item_id,
                qty: item.qty,
                uom: item.uom,
            }))
    } else {
        form.recipes = []
    }

    if (isEdit.value && props.product?.id) {
        form.transform(data => ({ ...data, _method: 'PUT' })).post(
            route('master.products.update', props.product.id),
            {
                onSuccess: () => forceClose(),
            }
        )
    } else {
        form.post(route('master.products.store'), {
            onSuccess: () => forceClose(),
        })
    }
}
</script>
