@component('mail::message')
# Halo 👋

Email anda telah berhasil didaftarkan, berikut adalah detail akun Anda:

**Nama:** {{ $user->name }}  
**Merchant:** {{ $user->merchant->name }}  
**Email:** {{ $user->email }}  
**Password:** {{ $defaultPassword }}

<x-mail::button :url="$actionUrl" color="primary">
{{ $actionText }}
</x-mail::button>

Demi keamanan, silakan login dan segera ubah password Anda di menu Profil / Pengaturan Akun.

<strong>Salam hangat,<br> {{ config('app.name') }} </strong>
@endcomponent