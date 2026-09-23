<x-mail::message>
# Pembayaran Invoice Ditolak

Halo,

Kami menginformasikan bahwa bukti pembayaran untuk invoice **{{ $invoiceNumber }}** telah ditolak.

**Alasan penolakan:**
> {{ $rejectionReason }}

Silakan unggah kembali bukti pembayaran yang benar melalui dashboard {{ config('app.name') }} Anda.

<x-mail::button :url="config('app.url').'/settings/billing'">
Unggah Ulang Bukti Pembayaran
</x-mail::button>

Terima kasih,<br>
Tim {{ config('app.name') }}
</x-mail::message>
