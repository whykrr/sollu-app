---
trigger: always_on
---

# Composable Reference & Standards (Sollu App)

Direktori `resources/js/Composable/` berisi fungsi-fungsi composable Vue 3 Composition API terstandarisasi untuk mengelola auth, permissions, enum, feature plan gating, dan lifecycle form di seluruh aplikasi.

Saat membuat atau memodifikasi komponen Vue, AI Agent **WAJIB** menggunakan composable resmi berikut:

---

## 1. `useFormDirtyGuard` (Standar Wajib Penanganan Form Dirty & Batal)

Composable resmi untuk menangani lifecycle form, pelacakan perubahan data yang belum tersimpan (`form.isDirty`), serta dialog konfirmasi pembatalan.

### A. Aturan Wajib

- **Setiap form Create / Edit** di dalam drawer `<PopUpPage>` atau dialog modal **WAJIB** menggunakan `useFormDirtyGuard({ form })`.
- Tombol **Batal** pada form **WAJIB** memanggil `handleCancel` (atau `handleCancel()`), **DILARANG KERAS** memanggil `popUpStore.close()` secara langsung di template.
- Pada callback `onSuccess` saat submit form berhasil, **WAJIB** memanggil `forceClose()` untuk menutup drawer secara bersih tanpa memicu peringatan dirty.

### B. Contoh Penggunaan Standar:

```vue
<template>
    <form class="space-y-2" @submit.prevent="submit">
        <TextField v-model="form.name" label="Nama Entitas" :feedback="form.errors.name" required />
        <DropdownField v-model="form.status" :options="statusOptions" label="Status" required />

        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex items-center justify-end gap-2 w-full">
                <!-- Gunakan handleCancel dari useFormDirtyGuard -->
                <button
                    type="button"
                    class="btn btn-flat"
                    :disabled="form.processing"
                    @click="handleCancel"
                >
                    Batal
                </button>
                <button type="submit" class="btn btn-main" :disabled="form.processing">
                    Simpan
                </button>
            </div>
        </Teleport>
    </form>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useFormDirtyGuard } from '@/Composable/useFormDirtyGuard'
import TextField from '@/Components/Form/TextField.vue'
import DropdownField from '@/Components/Form/DropdownField.vue'

const isMounted = ref(false)
onMounted(() => {
    isMounted.value = true
})

const form = useForm({
    name: '',
    status: '',
})

// Pasang dirty guard
const { handleCancel, forceClose, isDirty } = useFormDirtyGuard({ form })

const submit = () => {
    form.post(route('entity.store'), {
        preserveScroll: true,
        onSuccess: () => forceClose(), // Force close drawer on success
    })
}
</script>
```

---

## 2. `useAuth` (User, Multi-Outlet & RBAC Permissions)

Mengakses konteks autentikasi user aktif, outlet aktif, peran (roles), dan hak akses (permissions).

```javascript
import { useAuth } from '@/Composable/useAuth'

const {
    user, // Ref<User>
    business, // Ref<Business>
    outlets, // Ref<Outlet[]>
    selectedOutlet, // Ref<Outlet|null>
    roles, // Ref<string[]>
    isOwner, // ComputedRef<boolean>
    can, // (permission: string) => boolean
    canAny, // (permissions: string[]) => boolean
} = useAuth()
```

---

## 3. `useEnum` (Single Source of Truth PHP Enums)

Mengakses Enum PHP backend tanpa magic strings di Vue:

```javascript
import { useEnum } from '@/Composable/useEnum'

const { enums, getOptions, getLabel, getColor } = useEnum()

// Mengambil opsi untuk DropdownField
const statusOptions = getOptions('AdjustmentStatus')

// Membaca label & warna badge
const label = getLabel('AdjustmentStatus', item.status)
const color = getColor('AdjustmentStatus', item.status)
```

---

## 4. Feature Plan Gating (`v-feature` & `useAuth`)

Gating fitur paket langganan SaaS (Fitur vs RBAC):

- **Di Template (Utama):** Selalu gunakan directive `v-feature="$enums.FeatureEnum.NAME"` (atau modifier `v-feature.hide` khusus sidebar/navigasi). Modal upsell `FeatureLockedModal` dibuka secara otomatis di belakang layar pada fase capture saat elemen masked diklik.
- **Di Script Setup (Hanya untuk sanitasi data sebelum submit):** Gunakan `useAuth().hasFeature(featureName)`:

```javascript
import { useAuth } from '@/Composable/useAuth'
import { useEnum } from '@/Composable/useEnum'

const { enums } = useEnum()
const { hasFeature } = useAuth()

const canUseRecipes = hasFeature(enums.FeatureEnum.PRODUCT_BOM)
```
