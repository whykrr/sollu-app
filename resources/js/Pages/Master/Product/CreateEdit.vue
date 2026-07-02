<template>
    <PopUpPage :title="`Tambah Produk - ${currentStep.title}`" size="lg" @close="$emit('close')">
        <div class="p-3">
            <!-- Stepper indicator -->
            <div class="mb-4">
                <div class="flex items-center justify-between">
                    <template v-for="(step, index) in steps" :key="step.id">
                        <div class="flex flex-col items-center">
                            <div 
                                class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold transition-colors"
                                :class="[
                                    index < currentStepIndex ? 'bg-main text-white' : '',
                                    index === currentStepIndex ? 'bg-main text-white ring-2 ring-main/20' : '',
                                    index > currentStepIndex ? 'bg-neutral-100 text-neutral-500' : ''
                                ]"
                            >
                                {{ index + 1 }}
                            </div>
                            <span class="mt-2 text-xs font-medium text-neutral-500" :class="{'text-main': index === currentStepIndex}">
                                {{ step.title }}
                            </span>
                        </div>
                        <div v-if="index < steps.length - 1" class="h-1 flex-1 bg-neutral-100 mx-2 rounded-full overflow-hidden">
                            <div class="h-full bg-main transition-all" :style="{ width: index < currentStepIndex ? '100%' : '0%' }"></div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Dynamic Component -->
            <div class="min-h-[400px]">
                <component :is="currentStep.component" />
            </div>
        </div>

        <template #footer>
            <div class="flex justify-between items-center w-full">
                <button 
                    type="button" 
                    class="btn btn-outline-main btn-sm" 
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
                    class="btn btn-highlight-main btn-sm" 
                    @click="nextStep"
                >
                    Lanjut
                    <FontAwesomeIcon :icon="faChevronRight" class="ml-2" />
                </button>
                
                <button 
                    v-else
                    type="button" 
                    class="btn btn-success btn-sm" 
                    :disabled="form.processing"
                    @click="submit"
                >
                    <FontAwesomeIcon :icon="faSave" class="mr-2" />
                    Simpan Produk
                </button>
            </div>
        </template>
    </PopUpPage>
</template>

<script setup>
import { ref, computed, provide } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faChevronLeft, faChevronRight, faSave } from '@fortawesome/free-solid-svg-icons';
import PopUpPage from '@/Components/UI/PopUpPage.vue';

import StepBasicInfo from './Components/StepBasicInfo.vue';
import StepTypeSelection from './Components/StepTypeSelection.vue';
import StepVariantSetup from './Components/StepVariantSetup.vue';
import StepRecipeSetup from './Components/StepRecipeSetup.vue';
import StepBundleSetup from './Components/StepBundleSetup.vue';
import StepModifierSetup from './Components/StepModifierSetup.vue';
import StepPricing from './Components/StepPricing.vue';
import StepOutlet from './Components/StepOutlet.vue';

const emit = defineEmits(['close']);

const form = useForm({
    name: '',
    category_id: null,
    description: '',
    code: '',
    image: null,
    product_type: 'simple',
    
    variants: [],
    ingredients: [],
    bundle_items: [],
    modifiers: [],
    
    base_price: 0,
    variant_prices: [],
    outlet_prices: [],
    
    outlets: [],
});

provide('productForm', form);

const steps = computed(() => {
    let s = [
        { id: 'basic', component: StepBasicInfo, title: 'Informasi Dasar' },
        { id: 'type', component: StepTypeSelection, title: 'Tipe Produk' },
    ];
    
    if (form.product_type === 'variant') {
        s.push({ id: 'variant', component: StepVariantSetup, title: 'Setup Variant' });
    } else if (form.product_type === 'recipe') {
        s.push({ id: 'recipe', component: StepRecipeSetup, title: 'Setup Resep' });
    } else if (form.product_type === 'bundle') {
        s.push({ id: 'bundle', component: StepBundleSetup, title: 'Setup Bundle' });
    }
    
    s.push({ id: 'modifier', component: StepModifierSetup, title: 'Opsi Tambahan' });
    s.push({ id: 'pricing', component: StepPricing, title: 'Pengaturan Harga' });
    s.push({ id: 'outlet', component: StepOutlet, title: 'Pengaturan Outlet' });
    
    return s;
});

const currentStepIndex = ref(0);

const currentStep = computed(() => steps.value[currentStepIndex.value]);

const isFirstStep = computed(() => currentStepIndex.value === 0);
const isLastStep = computed(() => currentStepIndex.value === steps.value.length - 1);

const nextStep = () => {
    if (!isLastStep.value) {
        currentStepIndex.value++;
    }
};

const prevStep = () => {
    if (!isFirstStep.value) {
        currentStepIndex.value--;
    }
};

const submit = () => {
    form.post(route('masters.products.store'), {
        onSuccess: () => emit('close'),
    });
};
</script>
