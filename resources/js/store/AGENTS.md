---
trigger: always_on
---

# Wajib Perhatikan: Standar Penggunaan Pinia Stores Sollu App

Folder `resources/js/store` berisi *global state management* (Pinia) yang wajib digunakan untuk mengontrol elemen UI global seperti Pop-up (Drawer), Center Modals, dan Toast Notifications. DILARANG membuat *state* atau *store* duplikat untuk fungsi yang sudah ada.

Berikut panduan ketat penggunaan setiap *store*:

## 1. `usePopUpStore` (`@/store/popup`)
Digunakan untuk membuka **Side-Drawer Panel (PopUpPage)**. Komponen yang dimasukkan TIDAK BOLEH dibungkus dengan `<PopUpPage>` di templatenya.

**Cara Penggunaan:**
```javascript
import { usePopUpStore } from '@/store/popup';
import YourComponent from './YourComponent.vue';

const popUpStore = usePopUpStore();

// Membuka Drawer
popUpStore.open({
    title: 'Judul Drawer',
    subTitle: 'Sub-judul opsional', // Opsional
    size: 'md', // 'sm' | 'md' | 'lg' | 'xl' | '2xl'
    component: YourComponent, // Referensi komponen (bukan string)
    props: { id: 1 }, // Props yang dilempar ke YourComponent
    events: { 
        success: () => console.log('Event ditangkap') 
    },
});

// Menutup Drawer
popUpStore.close();
```

## 2. `useModalStore` (`@/store/notification`)
Digunakan HANYA untuk menampilkan dialog konfirmasi di tengah layar (Center Modal) secara ringkas (seperti peringatan Hapus, Alert, atau Info singkat).

**Cara Penggunaan:**
```javascript
import { useModalStore } from '@/store/notification';

const modalStore = useModalStore();

// Dialog Konfirmasi Cepat
modalStore.confirm({
    title: 'Hapus Data',
    message: 'Apakah Anda yakin ingin menghapus data ini?',
    type: 'danger', // 'info' | 'warning' | 'danger' | 'success'
    onConfirm: () => {
        // Lakukan aksi (misalnya router.delete)
        modalStore.close();
    }
});

// Dialog Peringatan (Alert)
modalStore.alert({
    title: 'Info',
    message: 'Proses berhasil dijalankan.',
    type: 'info'
});

// Menutup Modal Manual
modalStore.close();
```

## 3. `useToastStore` (`@/store/toast` atau dari `@/store/notification`)
Digunakan untuk memunculkan notifikasi *snack-bar* kecil (Toast) di layar.

**Cara Penggunaan:**
```javascript
import { useToastStore } from '@/store/toast';

const toastStore = useToastStore();

// Menampilkan Notifikasi Sukses
toastStore.success('Data berhasil disimpan');

// Menampilkan Error
toastStore.error('Terjadi kesalahan sistem');

// Method lainnya:
// toastStore.warning('Peringatan');
// toastStore.info('Informasi');
```

## 4. Aturan Penting (Anti-Hallucination)
- **Modal vs PopUp:** 
  - Butuh form input kompleks / sub-halaman / detail data? Gunakan `usePopUpStore` (Drawer Kanan).
  - Butuh konfirmasi Yes/No singkat? Gunakan `useModalStore` (Center Modal).
- **Import Path:** Pastikan `import` path diarahkan secara absolut menggunakan `@/store/...` bukan relatif.
- Jangan pernah meng-injeksi `<PopUpPage>` atau `<Modal>` sebagai *root tag* di komponen *child* jika *parent* sudah memanggil menggunakan *global store* ini. Biarkan *container* global yang membungkusnya.
