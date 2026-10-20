@extends('pdf.layout')

@section('document-box')
    <h1>PRESUPUESTO</h1>
    <div><span class="strong">N°:</span> {{ $budget->code }}</div>
    <div><span class="strong">Fecha:</span> {{ $budget->created_at?->format('d/m/Y') }}</div>
    <div><span class="strong">Estado:</span> {{ $budget->status->label() }}</div>
@endsection

@section('content')
    @include('pdf.partials.parties')

    <h2>Detalle</h2>
    <table class="grid">
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Descripción</th>
                <th class="num">Cantidad</th>
                <th class="num">Precio unit. (Gs.)</th>
                <th class="num">Importe (Gs.)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td>{{ $item['type'] }}</td>
                    <td>{{ $item['description'] }}</td>
                    <td class="num">{{ $item['quantity'] }}</td>
                    <td class="num">{{ $item['unit_price'] }}</td>
                    <td class="num">{{ $item['total'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals" style="width: 45%; margin-left: 55%; margin-top: 6px;">
        <tr><td class="label">Subtotal (sin IVA)</td><td class="num">{{ \App\Support\Format::money($budget->subtotal) }}</td></tr>
        <tr><td class="label">IVA {{ $taxRate }}%</td><td class="num">{{ \App\Support\Format::money($budget->tax) }}</td></tr>
        <tr class="strong"><td class="label">Total</td><td class="num">{{ \App\Support\Format::money($budget->total) }}</td></tr>
    </table>

    @if ($budget->notes)
        <h2>Observaciones</h2>
        <div>{{ $budget->notes }}</div>
    @endif

    <div class="legend">Presupuesto sujeto a revisión al desarmar. Los precios pueden variar según disponibilidad de repuestos.</div>
@endsection
