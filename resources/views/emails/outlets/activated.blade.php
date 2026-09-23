<x-mail::message>
# Penambahan Outlet Berhasil Aktif

Halo, **{{ $outlet->business->owner_name ?? $outlet->business->name }}**!

Terima kasih atas pembayaran Anda. Kami ingin menginformasikan bahwa pembayaran tagihan penambahan outlet telah berhasil divalidasi, dan **Outlet {{ $outlet->name }}** kini telah aktif sepenuhnya.

<x-mail::panel>
**Detail Outlet Baru Anda:**
- **Nama Outlet:** {{ $outlet->name }}
- **Alamat:** {{ $outlet->address ?? '-' }}
- **Nomor Telepon:** {{ $outlet->phone ?? '-' }}
</x-mail::panel>

Sekarang Anda dapat langsung mengelola produk, transaksi, dan stok pada outlet baru tersebut melalui Dashboard {{ config('app.name') }}.

<x-mail::button :url="config('app.url').'/settings/outlets'">
Kelola Outlet
</x-mail::button>

Jika Anda membutuhkan bantuan teknis atau panduan, tim kami selalu siap membantu Anda.

Salam hangat,<br>
**Tim {{ config('app.name') }}**
</x-mail::message>
