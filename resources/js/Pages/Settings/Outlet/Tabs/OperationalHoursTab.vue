<template>
    <div class="flex flex-col gap-4 p-4">
        <div>
            <h3 class="text-lg font-semibold text-slate-800">Jam Operasional</h3>
            <p class="text-sm text-slate-500 mb-4">Atur jadwal operasional outlet ini setiap harinya.</p>
        </div>

        <div class="space-y-2">
            <div
                v-for="(day, index) in form.hours"
                :key="index"
                class="flex items-center gap-4 p-2.5 border rounded-lg transition-colors"
                :class="{ 'bg-slate-50': day.is_closed }"
            >
                <div class="w-24 md:w-32 font-medium" :class="day.is_closed ? 'text-slate-400' : 'text-slate-700'">
                    {{ getDayName(day.day_of_week) }}
                </div>
                
                <div class="w-20 md:w-24">
                    <Switch :id="'closed_' + index" v-model="day.is_closed" size="sm" :labeling="day.is_closed ? 'Tutup' : 'Buka'" />
                </div>

                <div v-if="!day.is_closed" class="flex-1 flex items-center gap-2">
                    <TextField v-model="day.open_time" type="time" class="w-24 md:w-32" />
                    <span class="text-slate-400">-</span>
                    <TextField v-model="day.close_time" type="time" class="w-24 md:w-32" />
                </div>
            </div>
        </div>

        <div class="flex justify-end mt-4 pt-4 border-t border-slate-100">
            <button
                class="btn btn-main px-6 py-2 rounded-lg shadow-sm font-medium"
                :disabled="form.processing"
                @click="submitForm"
            >
                Simpan Jadwal
            </button>
        </div>
    </div>
</template>

<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Switch from '@/Components/Form/Switch.vue';
import TextField from '@/Components/Form/TextField.vue';

const props = defineProps({
    outlet: Object,
});

const form = useForm({
    hours: [
        { day_of_week: 0, open_time: '09:00', close_time: '22:00', is_closed: 0 },
        { day_of_week: 1, open_time: '09:00', close_time: '22:00', is_closed: 0 },
        { day_of_week: 2, open_time: '09:00', close_time: '22:00', is_closed: 0 },
        { day_of_week: 3, open_time: '09:00', close_time: '22:00', is_closed: 0 },
        { day_of_week: 4, open_time: '09:00', close_time: '22:00', is_closed: 0 },
        { day_of_week: 5, open_time: '09:00', close_time: '22:00', is_closed: 0 },
        { day_of_week: 6, open_time: '09:00', close_time: '22:00', is_closed: 0 },
    ]
});

const getDayName = (dayOfWeek) => {
    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    return days[dayOfWeek];
};

watch(
    () => props.outlet,
    (outlet) => {
        if (outlet && outlet.operational_hours && outlet.operational_hours.length > 0) {
            outlet.operational_hours.forEach(hour => {
                const index = form.hours.findIndex(h => h.day_of_week === hour.day_of_week);
                if (index !== -1) {
                    form.hours[index] = {
                        day_of_week: hour.day_of_week,
                        open_time: hour.open_time ? hour.open_time.substring(0, 5) : '09:00',
                        close_time: hour.close_time ? hour.close_time.substring(0, 5) : '22:00',
                        is_closed: hour.is_closed ? 1 : 0
                    };
                }
            });
        }
    },
    { immediate: true }
);

const submitForm = () => {
    if (!props.outlet) return;
    
    const payload = form.hours.map(h => ({
        ...h,
        is_closed: h.is_closed === 1
    }));

    form.transform(() => ({ hours: payload })).put(
        route('settings.outlets.operational-hours.update', { outlet: props.outlet.id }),
        {
            preserveScroll: true,
            preserveState: true,
        }
    );
};
</script>
