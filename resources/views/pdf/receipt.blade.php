@extends('pdf.layout')

@section('document-box')
    <h1>RECIBO DE DINERO</h1>
    <div><span class="strong">N°:</span> {{ $payment->receipt_number }}</div>
    <div><span class="strong">Fecha:</span> {{ $payment->payment_date?->format('d/m/Y') }}</div>
    <div><span class="strong">Importe:</span> {{ \App\Support\Format::money($payment->amount) }}</div>
@endsection

@section('content')
    <div class="box" style="margin-top: 12px; font-size: 11px; line-height: 1.6;">
        Recibimos de <span class="strong">{{ $invoice->customer_name }}</span>
        @if ($invoice->customer_tax_id) (RUC / CI {{ $invoice->customer_tax_id }})@endif
        la suma de <span class="strong">{{ \App\Support\Format::money($payment->amount) }}</span>
        en concepto de pago de la factura <span class="strong">{{ $invoice->number }}</span>
        del {{ $invoice->issue_date?->format('d/m/Y') }}.
    </div>

    <table class="grid" style="margin-top: 10px;">
        <tr><th style="width: 35%;">Medio de pago</th><td>{{ $payment->method->label() }}</td></tr>
        @if ($payment->reference)<tr><th>Referencia</th><td>{{ $payment->reference }}</td></tr>@endif
        @if ($payment->bank)<tr><th>Banco</th><td>{{ $payment->bank }}</td></tr>@endif
        <tr><th>Total de la factura</th><td class="num">{{ \App\Support\Format::money($invoice->total) }}</td></tr>
        <tr><th>Saldo actual de la factura</th><td class="num">{{ \App\Support\Format::money($balance) }}</td></tr>
    </table>

    @if ($payment->cancellation_reason)
        <h2>Anulación</h2>
        <div>{{ $payment->cancelled_at?->format('d/m/Y H:i') }} — {{ $payment->cancellation_reason }}</div>
    @endif

    <table style="margin-top: 50px;">
        <tr>
            <td style="width: 50%;"></td>
            <td style="width: 50%; text-align: center; border-top: 1px solid #444; padding-top: 4px;">Firma y aclaración</td>
        </tr>
    </table>
@endsection
