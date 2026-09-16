<template>
    <div v-if="loading" class="flex justify-center items-center h-48">
        <div class="animate-pulse flex flex-col items-center gap-2">
            <div class="w-8 h-8 border-4 border-main border-t-transparent rounded-full animate-spin"></div>
            <span class="text-sm text-neutral-500">Memuat hak akses fitur paket...</span>
        </div>
    </div>

    <form v-else class="flex flex-col gap-3" @submit.prevent="submit">
        <!-- Header Info Card -->
        <div class="bg-white border border-slate-200 rounded-lg p-3">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-base font-bold text-slate-800">
                            {{ planDetails.name }}
                        </h3>
                        <span class="text-xs font-mono font-semibold px-2 py-0.5 bg-slate-100 rounded text-slate-600">
                            {{ planDetails.code }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Konfigurasi hak akses modul dan kapabilitas sistem yang aktif untuk paket ini.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-1 bg-main/10 text-main font-bold text-xs rounded-full">
                        {{ form.feature_ids.length }} / {{ allFeaturesList.length }} Fitur Terpilih
                    </span>
                </div>
            </div>

            <!-- Toolbar: Live Search & Bulk Selection -->
            <div class="mt-3 pt-3 border-t border-slate-100 flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-2">
                <div class="relative flex-1 max-w-sm">
                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                        <FontAwesomeIcon :icon="faSearch" class="text-xs" />
                    </div>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nama fitur, kode, atau modul..."
                        class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-md focus:bg-white focus:outline-none focus:border-main focus:ring-1 focus:ring-main text-slate-800 placeholder-slate-400"
                    />
                </div>

                <div class="flex items-center gap-1.5 self-end sm:self-auto">
                    <button
                        type="button"
                        class="btn btn-outline-main btn-xs"
                        @click="selectAllFeatures"
                    >
                        <FontAwesomeIcon :icon="faCheckDouble" class="mr-1" />
                        Pilih Semua
                    </button>
                    <button
                        type="button"
                        class="btn btn-outline-slate-400 btn-xs"
                        @click="deselectAllFeatures"
                    >
                        Hapus Semua
                    </button>
                </div>
            </div>
        </div>

        <!-- Grouped Features List -->
        <div v-if="Object.keys(groupedFeatures).length" class="space-y-3 max-h-[calc(100vh-280px)] overflow-y-auto pr-1">
            <div
                v-for="(features, groupName) in groupedFeatures"
                :key="groupName"
                class="bg-white border border-slate-200 rounded-lg overflow-hidden shadow-2xs"
            >
                <!-- Group Header -->
                <div class="flex items-center justify-between px-3 py-2 bg-slate-50 border-b border-slate-200/80">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-800">{{ groupName }}</span>
                        <span class="text-[11px] px-2 py-0.5 bg-slate-200 text-slate-700 rounded-full font-semibold">
                            {{ getSelectedCountInGroup(features) }} / {{ features.length }}
                        </span>
                    </div>
                    <button
                        type="button"
                        class="text-[11px] text-main hover:underline font-semibold"
                        @click="toggleGroup(features)"
                    >
                        {{ isGroupAllSelected(features) ? 'Batal Pilih Grup' : 'Pilih Semua di Grup' }}
                    </button>
                </div>

                <!-- Feature Items Grid -->
                <div class="p-2.5 grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <label
                        v-for="feat in features"
                        :key="feat.id"
                        class="flex items-start gap-2.5 p-2 rounded-lg border transition-all cursor-pointer select-none"
                        :class="form.feature_ids.includes(feat.id)
                            ? 'bg-main/5 border-main/30'
                            : 'bg-white border-slate-100 hover:border-slate-200 hover:bg-slate-50/50'"
                    >
                        <input
                            v-model="form.feature_ids"
                            type="checkbox"
                            :value="feat.id"
                            class="mt-0.5 rounded border-slate-300 text-main focus:ring-main h-4 w-4 shrink-0"
                        />
                        <div class="text-xs leading-tight flex-1">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="font-bold text-slate-800">{{ feat.name }}</span>
                                <span class="text-[10px] font-mono px-1.5 py-0.2 bg-slate-100 text-slate-500 rounded">
                                    {{ feat.code }}
                                </span>
                            </div>
                            <div v-if="feat.description" class="text-[11px] text-slate-500 mt-1 line-clamp-2">
                                {{ feat.description }}
                            </div>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <div v-else class="text-center py-10 bg-white border border-dashed border-slate-200 rounded-lg text-xs text-slate-400">
            Tidak ada fitur yang cocok dengan pencarian "{{ searchQuery }}"
        </div>

        <!-- Sticky Footer Teleport -->
        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex justify-between items-center w-full">
                <div class="text-xs font-semibold text-slate-600 hidden sm:block">
                    {{ form.feature_ids.length }} fitur terpilih untuk paket {{ planDetails.name }}
                </div>
                <div class="flex justify-end gap-2 w-full sm:w-auto">
                    <button
                        type="button"
                        class="btn btn-outline-slate-400"
                        :disabled="form.processing"
                        @click="close"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="btn btn-main"
                        :disabled="form.processing"
                        @click="submit"
                    >
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Hak Akses Fitur' }}
                    </button>
                </div>
            </div>
        </Teleport>
    </form>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faSearch, faCheckDouble } from '@fortawesome/free-solid-svg-icons';
import { usePopUpStore } from '@/store/popup';
import axios from 'axios';

const props = defineProps({
    planId: {
        type: String,
        required: true,
    },
    allFeatures: {
        type: Array,
        default: () => [],
    },
});

const popUpStore = usePopUpStore();
const isMounted = ref(false);
const loading = ref(true);
const searchQuery = ref('');
const planDetails = ref({
    id: props.planId,
    name: '',
    code: '',
});
const allFeaturesList = ref(props.allFeatures || []);

const form = useForm({
    feature_ids: [],
});

const filteredFeatures = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) {
        return allFeaturesList.value;
    }
    return allFeaturesList.value.filter((feat) => {
        const nameMatch = (feat.name || '').toLowerCase().includes(q);
        const codeMatch = (feat.code || '').toLowerCase().includes(q);
        const descMatch = (feat.description || '').toLowerCase().includes(q);
        const groupMatch = (feat.group_label || feat.group || '').toLowerCase().includes(q);
        return nameMatch || codeMatch || descMatch || groupMatch;
    });
});

const groupedFeatures = computed(() => {
    const groups = {};
    filteredFeatures.value.forEach((feat) => {
        const groupLabel = feat.group_label || feat.group || 'Umum';
        if (!groups[groupLabel]) {
            groups[groupLabel] = [];
        }
        groups[groupLabel].push(feat);
    });
    return groups;
});

const getSelectedCountInGroup = (features) => {
    const ids = features.map((f) => f.id);
    return form.feature_ids.filter((id) => ids.includes(id)).length;
};

const isGroupAllSelected = (features) => {
    return features.length > 0 && features.every((f) => form.feature_ids.includes(f.id));
};

const toggleGroup = (features) => {
    const allSelected = isGroupAllSelected(features);
    const featureIds = features.map((f) => f.id);

    if (allSelected) {
        form.feature_ids = form.feature_ids.filter((id) => !featureIds.includes(id));
    } else {
        const set = new Set([...form.feature_ids, ...featureIds]);
        form.feature_ids = Array.from(set);
    }
};

const selectAllFeatures = () => {
    form.feature_ids = allFeaturesList.value.map((f) => f.id);
};

const deselectAllFeatures = () => {
    form.feature_ids = [];
};

onMounted(async () => {
    isMounted.value = true;
    try {
        const response = await axios.get(route('cockpit.subscription-plans.show', props.planId));
        const data = response.data;
        planDetails.value = {
            id: data.id,
            name: data.name,
            code: data.code,
        };
        form.feature_ids = (data.system_features || []).map((f) => f.id);
        if (data.all_features && data.all_features.length) {
            allFeaturesList.value = data.all_features;
        }
    } catch (err) {
        console.error('Failed to load plan features data:', err);
    } finally {
        loading.value = false;
    }
});

const close = () => {
    popUpStore.close();
};

const submit = () => {
    form.put(route('cockpit.subscription-plans.update-features', props.planId), {
        preserveScroll: true,
        onSuccess: () => {
            popUpStore.close();
        },
    });
};
</script>
