<template>
    <form class="space-y-2 p-1" @submit.prevent="submit">
        <TextField
            v-model="form.label"
            label="Nama Peran"
            placeholder="Contoh: Supervisor, Akuntan"
            :feedback="form.errors.label"
            :disabled="role?.is_default"
            required
        />
        <p v-if="role?.is_default" class="text-xs text-neutral-500">
            Nama peran bawaan sistem tidak dapat diubah.
        </p>

        <div class="border-t border-slate-100 pt-2 space-y-2">
            <div>
                <h3 class="text-sm font-semibold text-neutral-900">Matriks Hak Akses</h3>
                <p class="text-xs text-neutral-500">
                    Pilih izin yang ingin diberikan pada peran ini.
                </p>
            </div>

            <div
                v-if="isOwnerRole"
                class="p-2.5 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-700"
            >
                Peran Pemilik Usaha (Owner) memiliki hak akses penuh ke seluruh sistem dan tidak
                dapat dikurangi.
            </div>

            <div
                v-if="isLoadingPermissions"
                class="py-12 flex flex-col items-center justify-center gap-2 text-neutral-400"
            >
                <FontAwesomeIcon :icon="faSpinner" class="animate-spin text-2xl text-main" />
                <span class="text-xs">Memuat hak akses...</span>
            </div>

            <div v-else class="space-y-2">
                <div
                    v-for="group in permissionGroups"
                    :key="group.key"
                    class="bg-slate-50/70 border border-slate-200 rounded-xl p-3 space-y-2"
                >
                    <div class="flex items-center justify-between border-b border-slate-200 pb-1.5">
                        <span
                            class="font-semibold text-xs text-neutral-800 uppercase tracking-wider"
                        >
                            {{ group.label }}
                        </span>
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] text-neutral-400">
                                {{ getGroupActiveCount(group) }} /
                                {{ group.permissions.length }} Aktif
                            </span>
                            <button
                                v-if="!isOwnerRole"
                                type="button"
                                class="text-xs font-semibold text-main hover:underline select-none"
                                @click="toggleGroup(group)"
                            >
                                {{ isGroupAllSelected(group) ? 'Batalkan Semua' : 'Pilih Semua' }}
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div
                            v-for="permission in group.permissions"
                            :key="permission.value"
                            class="flex items-center justify-between p-2 rounded-lg border transition-colors duration-150"
                            :class="[
                                isPermissionChecked(permission.value)
                                    ? 'bg-blue-50/60 border-blue-200'
                                    : 'bg-white border-slate-200 hover:border-slate-300',
                                isOwnerRole ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer',
                            ]"
                            @click="!isOwnerRole && togglePermission(permission.value)"
                        >
                            <span
                                class="text-xs font-medium text-neutral-800 flex-1 pr-2 select-none"
                            >
                                {{ permission.label }}
                            </span>
                            <div class="pointer-events-none shrink-0">
                                <Switch
                                    :model-value="isPermissionChecked(permission.value)"
                                    :disabled="isOwnerRole"
                                    size="sm"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="form.errors.permissions" class="text-danger text-xs select-none">
                {{ form.errors.permissions }}
            </div>
        </div>

        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex items-center justify-end w-full gap-2">
                <button type="button" class="btn btn-flat" @click="popUpStore.close()">
                    Batal
                </button>
                <button
                    type="button"
                    class="btn btn-highlight-main"
                    :disabled="form.processing || isOwnerRole || isLoadingPermissions"
                    @click="submit"
                >
                    {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                </button>
            </div>
        </Teleport>
    </form>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import axios from 'axios'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faSpinner } from '@fortawesome/free-solid-svg-icons'
import { usePopUpStore } from '@/store/popup'
import { useEnum } from '@/Composable/useEnum'

import TextField from '@/Components/Form/TextField.vue'
import Switch from '@/Components/Form/Switch.vue'

const props = defineProps({
    role: {
        type: Object,
        default: null,
    },
})

const popUpStore = usePopUpStore()
const { enums, getGrouped } = useEnum()
const isMounted = ref(false)
const isLoadingPermissions = ref(false)

const isOwnerRole = computed(() => props.role?.name === enums.value?.RoleEnum?.OWNER)

const permissionGroups = computed(() => {
    return getGrouped('PermissionEnum')
})

const isPermissionChecked = value => {
    return form.permissions.includes(value)
}

const togglePermission = value => {
    const index = form.permissions.indexOf(value)
    if (index === -1) {
        form.permissions.push(value)
    } else {
        form.permissions.splice(index, 1)
    }
}

const getGroupActiveCount = group => {
    if (!group.permissions?.length) return 0
    return group.permissions.filter(p => form.permissions.includes(p.value)).length
}

const isGroupAllSelected = group => {
    if (!group.permissions?.length) return false
    return group.permissions.every(p => form.permissions.includes(p.value))
}

const toggleGroup = group => {
    const groupVals = group.permissions.map(p => p.value)
    if (isGroupAllSelected(group)) {
        form.permissions = form.permissions.filter(val => !groupVals.includes(val))
    } else {
        const merged = new Set([...form.permissions, ...groupVals])
        form.permissions = Array.from(merged)
    }
}

const form = useForm({
    label: props.role?.label || '',
    permissions: props.role?.permissions?.map(p => (typeof p === 'string' ? p : p.name)) || [],
})

onMounted(async () => {
    isMounted.value = true

    if (props.role?.id && (!props.role.permissions || props.role.permissions.length === 0)) {
        isLoadingPermissions.value = true
        try {
            const response = await axios.get(route('settings.roles.show', props.role.id))
            form.permissions = response.data.permissions || []
        } catch (error) {
            console.error('Gagal memuat hak akses peran:', error)
        } finally {
            isLoadingPermissions.value = false
        }
    }
})

const submit = () => {
    if (props.role) {
        form.put(route('settings.roles.update', props.role.id), {
            onSuccess: () => popUpStore.close(),
        })
    } else {
        form.post(route('settings.roles.store'), {
            onSuccess: () => popUpStore.close(),
        })
    }
}
</script>
