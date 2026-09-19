<template>
    <div class="space-y-3">
        <!-- Header Info & Kategori Filter -->
        <div class="space-y-2">
            <p class="text-xs text-neutral-600 leading-relaxed">
                Pilih template peran standar industri untuk membuat hak akses staf secara instan.
                Anda dapat langsung menggunakannya dalam 1 klik atau menyesuaikan daftar izinnya
                terlebih dahulu.
            </p>

            <!-- Segmented Category Filters -->
            <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar py-0.5 select-none">
                <button
                    v-for="cat in categories"
                    :key="cat.key"
                    type="button"
                    class="btn btn-sm h-[30px] text-xs font-medium px-2.5 rounded-lg border transition-all cursor-pointer text-nowrap"
                    :class="[
                        activeCategory === cat.key
                            ? 'bg-main text-white border-main font-semibold'
                            : 'bg-white text-neutral-700 border-slate-200 hover:bg-slate-50',
                    ]"
                    @click="activeCategory = cat.key"
                >
                    <FontAwesomeIcon v-if="cat.icon" :icon="cat.icon" class="text-xs" />
                    <span>{{ cat.label }}</span>
                    <span
                        v-if="cat.count"
                        class="text-[10px] px-1.5 py-0.2 rounded-full"
                        :class="
                            activeCategory === cat.key
                                ? 'bg-white/20 text-white'
                                : 'bg-slate-100 text-neutral-500'
                        "
                    >
                        {{ cat.count }}
                    </span>
                </button>
            </div>
        </div>

        <!-- Template Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5 max-h-[60vh] overflow-y-auto pr-1">
            <div
                v-for="template in filteredTemplates"
                :key="template.key"
                class="bg-white border rounded-xl p-3 flex flex-col justify-between transition-colors duration-150"
                :class="[
                    isRecommended(template)
                        ? 'border-main/40 bg-blue-50/20'
                        : 'border-slate-200 hover:border-slate-300',
                ]"
            >
                <!-- Card Header -->
                <div class="space-y-1.5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <div
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-sm"
                                :class="[
                                    isRecommended(template)
                                        ? 'bg-main/10 text-main'
                                        : 'bg-slate-100 text-neutral-700',
                                ]"
                            >
                                <FontAwesomeIcon :icon="getTemplateIcon(template)" />
                            </div>
                            <div class="min-w-0">
                                <h4 class="text-xs font-semibold text-neutral-900 truncate">
                                    {{ template.label }}
                                </h4>
                                <span class="text-[11px] text-neutral-400">
                                    {{ template.category_label }}
                                </span>
                            </div>
                        </div>

                        <div class="shrink-0 flex items-center gap-1">
                            <span
                                v-if="isRecommended(template)"
                                class="badge badge-primary text-[10px] font-semibold"
                            >
                                Rekomendasi
                            </span>
                            <span class="badge badge-info text-[10px] font-semibold">
                                {{ template.permissions_count }} Izin
                            </span>
                        </div>
                    </div>

                    <p class="text-xs text-neutral-600 line-clamp-2 leading-relaxed">
                        {{ template.description }}
                    </p>

                    <!-- Preview Grup Izin Utama -->
                    <div class="flex flex-wrap gap-1 pt-1">
                        <span
                            v-for="(grp, idx) in template.summary_groups.slice(0, 3)"
                            :key="idx"
                            class="text-[10px] text-neutral-600 bg-slate-100 border border-slate-200/60 px-1.5 py-0.5 rounded"
                        >
                            {{ grp.label }} ({{ grp.count }})
                        </span>
                        <span
                            v-if="template.summary_groups.length > 3"
                            class="text-[10px] text-neutral-400 bg-slate-50 px-1 py-0.5 rounded"
                        >
                            +{{ template.summary_groups.length - 3 }} lainnya
                        </span>
                    </div>
                </div>

                <!-- Card Actions -->
                <div class="flex items-center justify-between gap-2 pt-3 mt-2 border-t border-slate-100">
                    <button
                        type="button"
                        class="btn btn-flat btn-sm text-xs font-medium text-neutral-600 hover:text-neutral-900"
                        @click="handleCustomize(template)"
                    >
                        <FontAwesomeIcon :icon="faPencil" class="text-xs" />
                        <span>Kustomisasi</span>
                    </button>

                    <button
                        type="button"
                        class="btn btn-highlight-main btn-sm text-xs font-semibold"
                        :disabled="applyingKey === template.key"
                        @click="handleApply(template)"
                    >
                        <FontAwesomeIcon
                            :icon="applyingKey === template.key ? faSpinner : faBolt"
                            :class="{ 'animate-spin': applyingKey === template.key }"
                            class="text-xs"
                        />
                        <span>{{
                            applyingKey === template.key ? 'Menerapkan...' : 'Gunakan Template'
                        }}</span>
                    </button>
                </div>
            </div>
        </div>

        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex items-center justify-between w-full">
                <span class="text-xs text-neutral-500 hidden sm:inline">
                    Pilih template untuk langsung diterapkan atau sesuaikan dari nol.
                </span>
                <button type="button" class="btn btn-flat btn-sm" @click="popUpStore.close()">
                    Tutup
                </button>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faBolt,
    faPencil,
    faSpinner,
    faStar,
    faUtensils,
    faCartShopping,
    faScissors,
    faCalculator,
    faUserTie,
    faBoxesStacked,
    faKitchenSet,
    faUserCheck,
    faChartLine,
    faCashRegister,
} from '@fortawesome/free-solid-svg-icons'
import { usePopUpStore } from '@/store/popup'
import RoleFormPopUp from './RoleFormPopUp.vue'

const props = defineProps({
    templates: {
        type: Array,
        default: () => [],
    },
    businessType: {
        type: String,
        default: 'general',
    },
})

const popUpStore = usePopUpStore()
const isMounted = ref(false)
const applyingKey = ref(null)
const activeCategory = ref('recommended')

onMounted(() => {
    isMounted.value = true
})

const isRecommended = template => {
    if (!props.businessType || props.businessType === 'general') {
        return true
    }
    return template.business_types.includes(props.businessType)
}

const recommendedCount = computed(() => {
    return props.templates.filter(t => isRecommended(t)).length
})

const categories = computed(() => {
    const list = [
        {
            key: 'recommended',
            label: '⭐ Rekomendasi',
            count: recommendedCount.value,
            icon: faStar,
        },
        {
            key: 'fnb',
            label: 'F&B / Kuliner',
            count: props.templates.filter(t => t.category === 'fnb').length,
            icon: faUtensils,
        },
        {
            key: 'retail',
            label: 'Retail & Toko',
            count: props.templates.filter(t => t.category === 'retail').length,
            icon: faCartShopping,
        },
        {
            key: 'service',
            label: 'Jasa & Layanan',
            count: props.templates.filter(t => t.category === 'service').length,
            icon: faScissors,
        },
        {
            key: 'all',
            label: 'Semua Template',
            count: props.templates.length,
            icon: null,
        },
    ]

    return list
})

const filteredTemplates = computed(() => {
    if (activeCategory.value === 'recommended') {
        return props.templates.filter(t => isRecommended(t))
    }
    if (activeCategory.value === 'all') {
        return props.templates
    }
    return props.templates.filter(t => t.category === activeCategory.value)
})

const getTemplateIcon = template => {
    switch (template.key) {
        case 'cashier_fnb':
        case 'cashier_retail':
            return faCashRegister
        case 'waiter':
            return faUtensils
        case 'kitchen_barista':
            return faKitchenSet
        case 'manager_fnb':
        case 'store_manager_retail':
        case 'service_manager':
            return faUserTie
        case 'supervisor_fnb':
            return faUserCheck
        case 'inventory_staff':
            return faBoxesStacked
        case 'shop_assistant':
        case 'service_staff':
            return faScissors
        case 'finance_accounting':
            return faChartLine
        default:
            return faCalculator
    }
}

const handleApply = template => {
    applyingKey.value = template.key
    router.post(
        route('settings.roles.template'),
        {
            template_key: template.key,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                applyingKey.value = null
            },
            onSuccess: () => {
                popUpStore.close()
            },
        }
    )
}

const handleCustomize = template => {
    popUpStore.open({
        title: `Kustomisasi Peran: ${template.label}`,
        size: '2xl',
        component: RoleFormPopUp,
        props: {
            initialData: {
                label: template.label,
                permissions: template.permissions,
            },
        },
    })
}
</script>
