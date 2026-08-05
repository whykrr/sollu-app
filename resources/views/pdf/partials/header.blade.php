<div style="width: 100%; border-bottom: 2px solid #ccc; padding-bottom: 10px; margin-bottom: 20px;">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="vertical-align: top;">
                @if (isset($business) && $business->logo)
                    @php
                        $path = \Illuminate\Support\Facades\Storage::disk('public')->path($business->logo);
                        $type = file_exists($path) ? pathinfo($path, PATHINFO_EXTENSION) : '';
                        $data = file_exists($path) ? file_get_contents($path) : '';
                        $base64 = $data ? 'data:image/' . $type . ';base64,' . base64_encode($data) : '';
                    @endphp
                    @if ($base64)
                        <img src="{{ $base64 }}" alt="{{ $business->name }}" style="max-width: 150px; max-height: 80px; object-fit: contain;">
                    @else
                        <h2 style="margin: 0; color: #333;">{{ $business->name ?? 'Sollu App' }}</h2>
                    @endif
                @else
                    @if(isset($business))
                        <h2 style="margin: 0; color: #333;">{{ $business->name }}</h2>
                    @else
                        @php
                            $solluPath = public_path('img/logo-colored.png');
                            $solluType = file_exists($solluPath) ? pathinfo($solluPath, PATHINFO_EXTENSION) : '';
                            $solluData = file_exists($solluPath) ? file_get_contents($solluPath) : '';
                            $solluBase64 = $solluData ? 'data:image/' . $solluType . ';base64,' . base64_encode($solluData) : '';
                        @endphp
                        @if ($solluBase64)
                            <img src="{{ $solluBase64 }}" alt="Sollu App" style="max-width: 150px; max-height: 80px; object-fit: contain;">
                        @else
                            <h2 style="margin: 0; color: #333;">Sollu App</h2>
                        @endif
                    @endif
                @endif
                
                @if(isset($outlet) && $outlet)
                    <div style="margin-top: 10px; font-size: 13px; color: #555;">
                        <strong style="display: block;">{{ $outlet->name }}</strong>
                        @if($outlet->address)
                            <span>{{ $outlet->address }}</span><br>
                        @endif
                        @if($outlet->phone)
                            <span>Telp: {{ $outlet->phone }}</span>
                        @endif
                    </div>
                @endif
            </td>
            <td style="text-align: right; vertical-align: top;">
                <div style="font-size: 24px; font-weight: bold; color: #555; text-transform: uppercase;">
                    {{ $title ?? 'DOKUMEN' }}
                </div>
                @if(isset($subtitle) && $subtitle)
                    <div style="font-size: 16px; color: #777; margin-top: 5px;">
                        {{ $subtitle }}
                    </div>
                @endif
            </td>
        </tr>
    </table>
</div>
