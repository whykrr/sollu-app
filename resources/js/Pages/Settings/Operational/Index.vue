<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Jam Operasional Outlet">
                <SettingOutletSelector
                    v-if="outlets && outlets.length > 1"
                    :outlets="outlets"
                    :model-value="selectedOutlet?.id"
                    @update:model-value="changeOutlet"
                />
            </MainPageHeader>
        </template>

        <div class="max-w-4xl flex flex-col gap-6 pb-12">
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4 mb-4">
                    <div>
                        <h3 class="text-base font-semibold text-slate-800 flex items-center gap-2">
                            <FontAwesomeIcon :icon="faClock" class="text-main" />
                            Jadwal Buka / Tutup Mingguan
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Atur jam buka dan tutup untuk outlet <strong>{{ selectedOutlet?.name }}</strong> (Zona Waktu: {{ selectedOutlet?.timezone || 'WIB' }}).
                        </p>
                    </div>
                    <button
                        type="button"
                        class="btn btn-outline-main btn-sm text-xs rounded-lg px-3 py-1.5 self-start sm:self-auto"
                        @click="copyMondayToAll"
                    >
                        Samakan Semua Hari
                    </button>
                </div>

                <!-- Days Schedule Form -->
                <div class="flex flex-col divide-y divide-slate-100">
                    <div
                        v-for="day in form.hours"
                        :key="day.day_of_week"
                        class="py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-colors"
                        :class="{ 'opacity-60': day.is_closed }"
                    >
                        <div class="w-28 flex items-center gap-2">
                            <span class="font-semibold text-sm text-slate-800">
                                {{ dayNames[day.day_of_week] }}
                            </span>
                        </div>

                        <div class="flex items-center gap-4 flex-1">
                            <div class="flex items-center gap-2">
                                <Switch
                                    :id="'closed_' + day.day_of_week"
                                    :model-value="!day.is_closed"
                                    size="md"
                                    @update:model-value="day.is_closed = !$event"
                                />
                                <span class="text-xs font-medium" :class="day.is_closed ? 'text-rose-500' : 'text-emerald-600'">
                                    {{ day.is_closed ? 'Tutup / Libur' : 'Buka' }}
                                </span>
                            </div>

                            <div v-if="!day.is_closed" class="flex items-center gap-2 ml-auto">
                                <input
                                    v-model="day.open_time"
                                    type="time"
                                    class="form-input text-xs py-1.5 px-2.5 rounded-lg border-slate-300 focus:border-main focus:ring-main"
                                    required
                                />
                                <span class="text-slate-400 text-xs">-</span>
                                <input
                                    v-model="day.close_time"
                                    type="time"
                                    class="form-input text-xs py-1.5 px-2.5 rounded-lg border-slate-300 focus:border-main focus:ring-main"
                                    required
                                />
                            </div>
                            <div v-else class="text-xs text-slate-400 italic ml-auto">
                                Outlet tidak beroperasi pada hari ini
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end sticky bottom-4 z-10 bg-white/90 backdrop-blur-xs p-4 rounded-xl border border-slate-200 shadow-sm">
                <button
                    class="btn btn-main px-6 py-2.5 rounded-lg shadow-sm font-medium flex items-center gap-2"
                    :disabled="form.processing"
                    @click="submitForm"
                >
                    <FontAwesomeIcon :icon="faSave" />
                    <span>Simpan Jam Operasional</span>
                </button>
            </div>
        </div>
    </MainPage>
</template>

<script setup>
import { router, useForm } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faClock, faSave } from '@fortawesome/free-solid-svg-icons';

import MainPage from '@/Components/UI/MainPage.vue';
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue';
import SettingOutletSelector from '../Components/SettingOutletSelector.vue';
import Switch from '@/Components/Form/Switch.vue';

const props = defineProps({
    outlets: Array,
    selectedOutlet: Object,
    operationalHours: Array,
});

const dayNames = {
    0: 'Minggu',
    1: 'Senin',
    2: 'Selasa',
    3: 'Rabu',
    4: 'Kamis',
    5: 'Jumat',
    6: 'Sabtu',
};

// Initialize 7 days
const initialHours = [1, 2, 3, 4, 5, 6, 0].map((d) => {
    const existing = (props.operationalHours || []).find((h) => h.day_of_week === d);
    return {
        day_of_week: d,
        open_time: existing?.open_time ? existing.open_time.slice(0, 5) : '08:00',
        close_time: existing?.close_time ? existing.close_time.slice(0, 5) : '22:00',
        is_closed: existing ? !!existing.is_closed : false,
    };
});

const form = useForm({
    outlet_id: props.selectedOutlet?.id ?? '',
    hours: initialHours,
});

const changeOutlet = (newOutletId) => {
    router.visit(route('settings.operational.index', { outlet_id: newOutletId }), {
        preserveState: false,
        preserveScroll: true,
    });
};

const copyMondayToAll = () => {
    const monday = form.hours.find((h) => h.day_of_week === 1);
    if (!monday) return;

    form.hours.forEach((h) => {
        h.open_time = monday.open_time;
        h.close_time = monday.close_time;
        h.is_closed = monday.is_closed;
    });
};

const submitForm = () => {
    form.outlet_id = props.selectedOutlet?.id;
    form.put(route('settings.operational.update'), {
        preserveScroll: true,
    });
};
</script>
