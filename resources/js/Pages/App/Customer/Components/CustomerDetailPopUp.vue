<template>
    <div class="space-y-3 flex-1 overflow-y-auto">
        <!-- Header / Profil Section -->
        <div
            class="bg-white p-4 rounded-xl border border-slate-200 flex flex-col sm:flex-row gap-4 items-start sm:items-center"
        >
            <div
                class="w-14 h-14 bg-main/10 text-main rounded-full flex items-center justify-center text-xl font-bold shrink-0"
            >
                {{ getInitials(detail?.name) }}
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="text-base font-bold text-slate-800 truncate">
                    {{ detail?.name || '-' }}
                </h3>
                <div class="text-slate-500 mt-1 flex flex-wrap items-center gap-3 text-xs">
                    <span class="flex items-center gap-1">
                        <FontAwesomeIcon :icon="faPhone" class="text-slate-400" />
                        {{ detail?.phone || '-' }}
                    </span>
                    <span v-if="detail?.email" class="flex items-center gap-1">
                        <FontAwesomeIcon :icon="faEnvelope" class="text-slate-400" />
                        {{ detail.email }}
                    </span>
                </div>
            </div>
            <div v-if="detail?.is_active !== undefined">
                <span v-if="detail.is_active" class="badge badge-success">Aktif</span>
                <span v-else class="badge badge-neutral-500">Tidak Aktif</span>
            </div>
        </div>

        <!-- Detail Info Grid -->
        <div class="bg-white p-4 rounded-xl border border-slate-200">
            <h4 class="font-semibold text-xs text-slate-700 uppercase tracking-wider mb-3">
                Informasi Detail
            </h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                <div>
                    <span class="text-slate-500 block text-[11px]">Tanggal Lahir / Umur</span>
                    <span class="font-medium text-slate-800">
                        {{ detail?.birthdate ? formatDateID(detail.birthdate) : '-' }}
                        <span v-if="detail?.age" class="text-slate-500 font-normal">
                            ({{ detail.age }} tahun)
                        </span>
                    </span>
                </div>
                <div>
                    <span class="text-slate-500 block text-[11px]">Jenis Kelamin</span>
                    <span class="font-medium text-slate-800">{{ genderLabel }}</span>
                </div>
                <div class="sm:col-span-2">
                    <span class="text-slate-500 block text-[11px]">Alamat Lengkap</span>
                    <span class="font-medium text-slate-800">{{ detail?.address || '-' }}</span>
                </div>
                <div class="sm:col-span-2">
                    <span class="text-slate-500 block text-[11px]">Catatan Khusus</span>
                    <span class="font-medium text-slate-800">{{ detail?.notes || '-' }}</span>
                </div>
            </div>
        </div>

        <!-- Ringkasan Belanja (KPI Cards) -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
            <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                <div class="text-slate-500 text-[11px] mb-1">Total Kunjungan</div>
                <div class="font-bold text-sm text-slate-800">
                    {{ formatNumberID(detail?.summary?.total_transactions || 0) }} Kali
                </div>
            </div>
            <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                <div class="text-slate-500 text-[11px] mb-1">Total Belanja</div>
                <div class="font-bold text-sm text-slate-800">
                    {{ formatIDR(detail?.summary?.total_spent || 0) }}
                </div>
            </div>
            <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                <div class="text-slate-500 text-[11px] mb-1">Rata-rata Belanja</div>
                <div class="font-bold text-sm text-slate-800">
                    {{ formatIDR(detail?.summary?.average_spent || 0) }}
                </div>
            </div>
            <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                <div class="text-slate-500 text-[11px] mb-1">Terakhir Belanja</div>
                <div class="font-bold text-sm text-slate-800">
                    {{
                        detail?.summary?.last_transaction_date
                            ? formatDateID(detail.summary.last_transaction_date)
                            : '-'
                    }}
                </div>
            </div>
        </div>

        <!-- Riwayat Transaksi Terakhir -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div
                class="p-3 border-b border-slate-200 bg-slate-50 flex items-center justify-between"
            >
                <h4 class="font-semibold text-xs text-slate-700 uppercase tracking-wider">
                    Riwayat Transaksi Terakhir
                </h4>
                <span class="text-[11px] text-slate-500">Maks. 10 transaksi</span>
            </div>
            <div class="divide-y divide-slate-100">
                <div
                    v-if="!detail?.recent_transactions?.length"
                    class="p-6 text-center text-xs text-slate-400"
                >
                    Belum ada riwayat transaksi
                </div>
                <div
                    v-for="tx in detail?.recent_transactions"
                    :key="tx.id"
                    class="p-3 flex items-center justify-between hover:bg-slate-50 transition text-xs"
                >
                    <div>
                        <div class="font-semibold text-slate-800">{{ tx.invoice_number }}</div>
                        <div class="text-[11px] text-slate-500 mt-0.5">
                            {{ tx.date }} • {{ tx.outlet_name || '-' }}
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-slate-900">{{ formatIDR(tx.grand_total) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <Teleport v-if="isMounted" to="#popUpFooter">
        <div class="flex items-center justify-end w-full gap-2">
            <button type="button" class="btn btn-flat" @click="popUpStore.close()">Tutup</button>
            <button type="button" class="btn btn-outline-main" @click="handleEdit">
                Ubah Data
            </button>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { usePopUpStore } from '@/store/popup'
import { useEnum } from '@/Composable/useEnum'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faPhone, faEnvelope } from '@fortawesome/free-solid-svg-icons'
import { formatDateID } from '@/Composable/date'
import { formatIDR } from '@/Composable/currency-format'
import { formatNumberID } from '@/Composable/useNumberFormat'
import axios from 'axios'

const props = defineProps({
    customer: {
        type: Object,
        default: () => ({}),
    },
})

const emit = defineEmits(['edit'])

const popUpStore = usePopUpStore()
const { getLabel } = useEnum()
const isMounted = ref(false)
const detail = ref({ ...props.customer })
const isLoading = ref(true)

onMounted(async () => {
    isMounted.value = true
    if (props.customer?.id) {
        try {
            const response = await axios.get(route('customers.show', props.customer.id))
            detail.value = response.data.data
        } catch (e) {
            console.error('Failed to fetch customer details:', e)
        } finally {
            isLoading.value = false
        }
    }
})

const genderLabel = computed(() => {
    if (!detail.value?.gender) return '-'
    return getLabel('CustomerGender', detail.value.gender) || detail.value.gender
})

const getInitials = name => {
    if (!name) return '?'
    return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .substring(0, 2)
        .toUpperCase()
}

const handleEdit = () => {
    emit('edit', detail.value)
}
</script>
