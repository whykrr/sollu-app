---
trigger: always_on
---

# Wajib Perhatikan: Standar Komponen Kartu `@/Components/Cards/`

Panduan penggunaan komponen kartu di `@/Components/Cards/` dan `@/Components/UI/Card/`:

---

## 1. Komponen Kartu Tersedia

### A. `<Card>` (`@/Components/UI/Card/Card.vue`)

Kartu berbatas (_bordered card_) untuk mengelompokkan form input, widget ringkas, atau informasi detail entitas.

- **Props:** `title` (String), `image` (String).
- **Slots:** default slot, `#footer`.

```vue
<Card title="Pengaturan Umum">
    <TextField v-model="form.name" label="Nama" />
    <template #footer>
        <button class="btn btn-main">Simpan</button>
    </template>
</Card>
```

### B. `<CardTransparent>` (`@/Components/Cards/CardTransparent.vue`)

Container kartu berlatar belakang transparan dengan slot header & tombol.

```vue
<CardTransparent title="Riwayat Aktivitas">
    <template #buttons>
        <button class="btn btn-sm btn-outline-main">Refresh</button>
    </template>
    <p>Daftar log...</p>
</CardTransparent>
```

### C. `<CardFade>` (`@/Components/UI/Card/CardFade.vue`)

Kartu promosi atau banner dengan latar blur visual.

```vue
<CardFade :image="promoImage" title="Paket Hemat Ramadhan">
    <p>Diskon hingga 30% untuk semua menu.</p>
</CardFade>
```
