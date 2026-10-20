<table style="margin-top: 10px;">
    <tr>
        <td style="width: 50%; vertical-align: top; padding-right: 6px;">
            <h2>Cliente</h2>
            @if ($client)
                <div class="strong">{{ $client['name'] }}</div>
                @if ($client['document'])<div>Documento: {{ $client['document'] }}</div>@endif
                <div>{{ collect([$client['phone'], $client['email']])->filter()->implode(' · ') }}</div>
            @else
                <div class="muted">—</div>
            @endif
        </td>
        <td style="width: 50%; vertical-align: top;">
            <h2>Vehículo</h2>
            @if ($vehicle)
                <div class="strong">{{ $vehicle['name'] }}@if ($vehicle['year']) ({{ $vehicle['year'] }})@endif</div>
                <div>{{ $vehicle['plate'] ? 'Placa: ' . $vehicle['plate'] : 'Chasis: ' . $vehicle['chassis'] }}</div>
            @else
                <div class="muted">—</div>
            @endif
        </td>
    </tr>
</table>
