<template>
    <div class="space-y-4 flex-1 overflow-y-auto">
        <!-- Header / Profil Section -->
        <div
             class="bg-white p-6 rounded-xl border border-slate-200 flex flex-col sm:flex-row gap-6 items-start sm:items-center">
            <div
                 class="w-20 h-20 bg-primary/10 text-primary rounded-full flex items-center justify-center text-2xl font-bold shrink-0">
                {{ getInitials(detail?.name) }}
            </div>
            <div class="flex-1">
                <h3
                    class="text-xl font-bold text-slate-800">
                    {{ detail?.name || '-' }}
                </h3>
                <div
                     class="text-slate-500 mt-1 flex items-center gap-4 text-sm">
                    <span
                          class="flex items-center gap-1">
                        <FontAwesomeIcon
:icon="faPhone"
                                         class="text-slate-400" />
                        {{ detail?.phone || '-'
                        }}
                    </span>
                    <span
v-if="detail?.email"
                          class="flex items-center gap-1">
                        <FontAwesomeIcon
:icon="faEnvelope"
                                         class="text-slate-400" />
                        {{ detail.email }}
                    </span>
                </div>
            </div>
            <div
                 v-if="detail?.is_active !== undefined">
                <span
v-if="detail.is_active"
                      class="badge badge-success px-3 py-1">Aktif</span>
                <span
v-else
                      class="badge badge-neutral-500 px-3 py-1">Tidak
                    Aktif</span>
            </div>
        </div>

        <!-- Detail Info Grid -->
        <div
             class="bg-white p-6 rounded-xl border border-slate-200">
            <h4
                class="font-semibold text-slate-700 mb-4">
                Informasi Detail</h4>
            <div
                 class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <span
                          class="text-slate-500 block text-xs">Tanggal
                        Lahir / Umur</span>
                    <span
                          class="font-medium text-slate-800">
                        {{ detail?.birthdate ||
                            '-' }}
                        <span
v-if="detail?.age"
                              class="text-slate-500 font-normal">({{
                                detail.age }}
                            tahun)</span>
                    </span>
                </div>
                <div>
                    <span
                          class="text-slate-500 block text-xs">Jenis
                        Kelamin</span>
                    <span
                          class="font-medium text-slate-800 capitalize">{{
                            genderLabel }}</span>
                </div>
                <div class="md:col-span-2">
                    <span
                          class="text-slate-500 block text-xs">Alamat
                        Lengkap</span>
                    <span
                          class="font-medium text-slate-800">{{
                            detail?.address || '-'
                        }}</span>
                </div>
                <div class="md:col-span-2">
                    <span
                          class="text-slate-500 block text-xs">Catatan
                        Khusus</span>
                    <span
                          class="font-medium text-slate-800">{{
                            detail?.notes || '-'
                        }}</span>
                </div>
            </div>
        </div>

        <!-- Ringkasan Belanja (Cards) -->
        <div
             class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div
                 class="bg-white p-4 rounded-xl border border-slate-200">
                <div
                     class="text-slate-500 text-xs mb-1">
                    Transaksi Berulang</div>
                <div
                     class="font-bold text-lg text-slate-800">
                    {{
                        detail?.summary?.total_transactions
                        || 0 }} kali</div>
            </div>
            <div
                 class="bg-white p-4 rounded-xl border border-slate-200">
                <div
                     class="text-slate-500 text-xs mb-1">
                    Total Belanja</div>
                <div
                     class="font-bold text-lg text-slate-800">
                    {{
                        formatCurrency(detail?.summary?.total_spent)
                    }}</div>
            </div>
            <div
                 class="bg-white p-4 rounded-xl border border-slate-200">
                <div
                     class="text-slate-500 text-xs mb-1">
                    Rata-rata Belanja</div>
                <div
                     class="font-bold text-lg text-slate-800">
                    {{
                        formatCurrency(detail?.summary?.average_spent)
                    }}</div>
            </div>
            <div
                 class="bg-white p-4 rounded-xl border border-slate-200">
                <div
                     class="text-slate-500 text-xs mb-1">
                    Transaksi Terakhir</div>
                <div
                     class="font-bold text-lg text-slate-800">
                    {{
                        formatDateID(detail?.summary?.last_transaction_date)
                        || '-' }}</div>
            </div>
        </div>

        <!-- Tabel Riwayat Transaksi -->
        <div
             class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div
                 class="p-4 border-b border-slate-200 bg-slate-50/50">
                <h4
                    class="font-semibold text-slate-700">
                    Riwayat Transaksi Terakhir
                </h4>
            </div>
            <div class="overflow-x-auto">
                <table
                       class="w-full text-left text-sm text-slate-600">
                    <thead
                           class="bg-slate-50 text-slate-500 text-xs uppercase font-medium">
                        <tr>
                            <th class="px-4 py-3">
                                No. Struk</th>
                            <th class="px-4 py-3">
                                Tanggal</th>
                            <th class="px-4 py-3">
                                Outlet</th>
                            <th
                                class="px-4 py-3 text-right">
                                Total Belanja</th>
                        </tr>
                    </thead>
                    <tbody
                           class="divide-y divide-slate-100">
                        <tr
                            v-if="!detail?.recent_transactions?.length">
                            <td
colspan="4"
                                class="px-4 py-8 text-center text-slate-400">
                                Belum ada riwayat
                                transaksi
                            </td>
                        </tr>
                        <tr
v-for="tx in detail?.recent_transactions"
                            :key="tx.id"
                            class="hover:bg-slate-50">
                            <td
                                class="px-4 py-3 font-medium text-slate-800">
                                {{
                                    tx.invoice_number
                                }}</td>
                            <td class="px-4 py-3">
                                {{ tx.date }}</td>
                            <td class="px-4 py-3">
                                {{ tx.outlet_name
                                    || '-' }}</td>
                            <td
                                class="px-4 py-3 text-right font-medium">
                                {{
                                    formatCurrency(tx.grand_total)
                                }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <Teleport
v-if="isMounted"
              to="#popUpFooter">
        <div
             class="flex items-center justify-end w-full gap-2">
            <button
type="button"
                    class="btn btn-flat"
                    @click="popUpStore.close()">
                Tutup
            </button>
            <button
type="button"
                    class="btn btn-outline-primary"
                    @click="handleEdit">
                Ubah Data
            </button>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { usePopUpStore } from '@/store/popup';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faPhone, faEnvelope } from '@fortawesome/free-solid-svg-icons';
import { formatDateID } from '@/Composable/date';
import axios from 'axios';

const props = defineProps({
    customer: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['edit']);

const popUpStore = usePopUpStore();
const isMounted = ref(false);
const detail = ref({ ...props.customer });
const isLoading = ref(true);

onMounted(async () => {
    isMounted.value = true;
    try {
        const response = await axios.get(route('customers.show', props.customer.id));
        detail.value = response.data.data;
    } catch (e) {
        console.error('Failed to fetch customer details:', e);
    } finally {
        isLoading.value = false;
    }
});

const genderLabel = computed(() => {
    if (detail.value?.gender === 'male') return 'Laki-laki';
    if (detail.value?.gender === 'female') return 'Perempuan';
    return '-';
});

const getInitials = (name) => {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
};

const formatCurrency = (amount) => {
    if (amount === undefined || amount === null) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
};

const handleEdit = () => {
    emit('edit', detail.value);
};
</script>
