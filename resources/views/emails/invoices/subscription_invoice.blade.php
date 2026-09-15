<x-mail::message>
# Pembayaran Invoice Terverifikasi

Halo, **{{ $business->owner_name ?? $business->name }}**!

Terima kasih. Pembayaran untuk tagihan langganan bisnis **{{ $business->name }}** telah berhasil diverifikasi.

<x-mail::panel>
**Ringkasan Pembayaran:**
- **No. Invoice:** #{{ $invoice->invoice_number }}
- **Tanggal Pembayaran:** {{ $invoice->paid_at ? $invoice->paid_at->translatedFormat('d F Y H:i') : now()->translatedFormat('d F Y H:i') }}
- **Total Pembayaran:** Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
- **Metode Pembayaran:** {{ $payment ? ucwords(str_replace('_', ' ', $payment->payment_method)) : 'Transfer / Otomatis' }}
</x-mail::panel>

### Rincian Item Tagihan:

<x-mail::table>
| Deskripsi | Qty | Subtotal |
| :--- | :---: | :---: |
@foreach ($invoice->items as $item)
| {{ $item->description }} | {{ $item->quantity }} | Rp {{ number_format($item->subtotal, 0, ',', '.') }} |
@endforeach
| **Total** | | **Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}** |
</x-mail::table>

Salinan dokumen invoice resmi dalam format PDF telah kami lampirkan pada email ini untuk keperluan arsip dan pembukuan bisnis Anda.

<x-mail::button :url="config('app.url').'/settings/billing'">
Buka Dashboard Billing
</x-mail::button>

Jika Anda membutuhkan bantuan atau memiliki pertanyaan lebih lanjut, silakan hubungi tim dukungan kami.

Salam hangat,<br>
**Tim {{ config('app.name') }}**
</x-mail::message>
