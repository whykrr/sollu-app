@props(['url'])
<tr>
    <td class="header">
        <a href="{{ $url }}" style="display: inline-block;">
            @if (trim($slot) === 'Laravel')
                <img src="https://laravel.com/img/notification-logo.png" class="logo" alt="Laravel Logo">
            @else
                <img src="{{ url('img/logo-fit-color.png') }}" class="logo" alt="Sollu Teknologi Indonesia">
                <br>
                {{ $slot }}
            @endif
        </a>
    </td>
</tr>
