<template>
    <ActionBar>
        <template #filters>
            <!-- Status Filter -->
            <FilterDropdown
                v-model="filterForm.status"
                label="Status Pembayaran"
                :options="statusOptions"
                all-option-label="Semua Status"
                @change="updateQuery"
            />
        </template>

        <template #search>
            <FilterSearch
                v-model="filterForm.search"
                placeholder="Cari nomor invoice..."
                @clear="updateQuery"
            />
        </template>

        <template #create>
            <Link
                v-if="!subscription || subscription.status !== $enums.SubscriptionStatus.Active"
                :href="route('settings.billing.plans')"
                class="btn btn-main btn-sm h-[30px] inline-flex items-center gap-1.5 cursor-pointer"
            >
                <FontAwesomeIcon :icon="faGem" />
                <span>Pilih Paket</span>
            </Link>
            <Link
                v-else
                :href="route('settings.billing.plans')"
                class="btn btn-outline-main btn-sm h-[30px] inline-flex items-center gap-1.5 cursor-pointer"
            >
                <span>Ubah Paket</span>
            </Link>
        </template>
    </ActionBar>
</template>

<script setup>
import { computed, reactive, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faGem } from '@fortawesome/free-solid-svg-icons'
import ActionBar from '@/Components/UI/ActionBar/ActionBar.vue'
import FilterDropdown from '@/Components/UI/Filter/FilterDropdown.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'
import { useEnum } from '@/Composable/useEnum'

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
    subscription: {
        type: Object,
        default: null,
    },
})

const { getOptions } = useEnum()

const filterForm = reactive({
    search: props.filters?.search || '',
    status: props.filters?.status || '',
})

const statusOptions = computed(() => {
    return getOptions('InvoiceStatus')
})

watch(
    () => filterForm.search,
    debounce(() => {
        updateQuery()
    }, 500)
)

const updateQuery = () => {
    const query = {
        ...route().params,
        search: filterForm.search || undefined,
        status: filterForm.status || undefined,
        page: 1,
    }

    router.get(location.pathname, query, {
        preserveState: true,
        preserveScroll: true,
    })
}
</script>
