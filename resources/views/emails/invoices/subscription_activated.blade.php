<x-mail::message>
# Langganan Anda Telah Aktif

Halo, **{{ $business->owner_name ?? $business->name }}**!

Terima kasih atas pembayaran Anda. Kami ingin menginformasikan bahwa langganan paket **{{ $plan->name }}** untuk bisnis **{{ $business->name }}** telah berhasil diaktifkan.

<x-mail::panel>
**Detail Langganan:**
- **Paket:** {{ $plan->name }}
- **Masa Berlaku Hingga:** {{ $expiredAt }}
</x-mail::panel>

Sekarang Anda dapat menggunakan seluruh fitur yang tersedia pada paket ini. Jika Anda memiliki pertanyaan atau butuh bantuan, tim dukungan kami selalu siap membantu.

<x-mail::button :url="config('app.url')">
Masuk ke Dashboard
</x-mail::button>

Salam hangat,<br>
**Tim {{ config('app.name') }}**
</x-mail::message>
