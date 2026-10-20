@extends('pdf.layout')

@section('document-box')
    <h1>ENTREGA DE VEHÍCULO</h1>
    <div><span class="strong">N°:</span> {{ $delivery->delivery_number }}</div>
    <div><span class="strong">Fecha:</span> {{ $delivery->delivered_at?->format('d/m/Y H:i') }}</div>
@endsection

@section('content')
    @include('pdf.partials.parties')

    <h2>Datos de la entrega</h2>
    <table class="grid">
        <tr><th style="width: 35%;">Kilometraje de entrega</th><td>{{ \App\Support\Format::integer($delivery->final_mileage) }} km</td></tr>
        <tr><th>Combustible</th><td>{{ $delivery->fuel_level?->label() ?? '—' }}</td></tr>
        <tr><th>Retira</th><td>{{ $delivery->received_by_name }} (Doc. {{ $delivery->received_by_document }})</td></tr>
        <tr><th>Conformidad del cliente</th><td>{{ $delivery->customer_accepted ? 'Sí' : 'No' }}</td></tr>
        <tr><th>Entregado por</th><td>{{ $delivery->deliverer?->name }}</td></tr>
        @if ($delivery->observations)<tr><th>Observaciones</th><td>{{ $delivery->observations }}</td></tr>@endif
    </table>

    @if ($workOrders->isNotEmpty())
        <h2>Trabajos realizados</h2>
        <table class="grid">
            <thead><tr><th>Orden de trabajo</th><th>Factura(s)</th></tr></thead>
            <tbody>
                @foreach ($workOrders as $workOrder)
                    <tr><td>{{ $workOrder['code'] }}</td><td>{{ $workOrder['invoices'] ?: '—' }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($delivery->cancellation_reason)
        <h2>Anulación</h2>
        <div>{{ $delivery->cancelled_at?->format('d/m/Y H:i') }} — {{ $delivery->cancellation_reason }}</div>
    @endif

    <table style="margin-top: 30px;">
        <tr>
            <td style="width: 50%;"></td>
            <td style="width: 50%; text-align: center;">
                @if ($signature)
                    <img src="{{ $signature }}" style="max-height: 70px; max-width: 220px;">
                @else
                    <div style="height: 60px;"></div>
                @endif
                <div style="border-top: 1px solid #444; padding-top: 4px;">Firma de quien retira</div>
            </td>
        </tr>
    </table>
@endsection
