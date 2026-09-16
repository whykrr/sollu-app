---
trigger: always_on
---

# Wajib Perhatikan: Standar Dialog Modal & Toast `@/Components/Notifications/`

Panduan penggunaan modal konfirmasi dan toast notification:

---

## 1. Center Modal Konfirmasi (`<Modal>` / `useModalStore()`)
- **Fungsi Utama:** HANYA untuk konfirmasi aksi singkat (Hapus Data, Pulihkan Data, Archive, Alert Peringatan).
- **Larangan Keras:** DILARANG menggunakan Center Modal untuk formulir panjang atau Create/Edit entity. Gunakan `<PopUpPage>` (side drawer) untuk formulir.
- **Cara Pemanggilan di `<script setup>`:**
  ```javascript
  import { useModalStore } from '@/store/notification'

  const modalStore = useModalStore()

  // Buka konfirmasi hapus soft-delete
  modalStore.openModalSoftDelete(route('products.destroy', id))

  // Buka konfirmasi hapus permanen
  modalStore.openModalDelete(route('products.force-delete', id))
  ```

---

## 2. Toast Notifications (`<Toast>` / `useToastStore()`)
- **Fungsi Utama:** Menampilkan notifikasi melayang sementara (Auto-dismiss) untuk memberitahu hasil aksi pengguna.
- **Cara Pemanggilan di `<script setup>`:**
  ```javascript
  import { useToastStore } from '@/store/notification'

  const toastStore = useToastStore()

  toastStore.addToast({
      type: 'success', // 'success' | 'error' | 'warning' | 'info'
      message: 'Data produk berhasil diperbarui!',
      duration: 3000,
  })
  ```
