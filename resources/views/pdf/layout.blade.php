<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 28px 32px 40px 32px; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10px; color: #222; }
        h1 { font-size: 15px; margin: 0 0 4px 0; }
        h2 { font-size: 11px; margin: 14px 0 4px 0; text-transform: uppercase; color: #444; }
        table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: top; }
        .company-name { font-size: 14px; font-weight: bold; }
        .logo { height: 48px; margin-bottom: 4px; }
        .muted { color: #666; }
        .box { border: 1px solid #999; padding: 6px 8px; }
        .grid th, .grid td { border: 1px solid #bbb; padding: 3px 5px; }
        .grid th { background: #eee; text-align: left; }
        .num { text-align: right; white-space: nowrap; }
        .totals td { padding: 2px 5px; }
        .totals .label { text-align: right; }
        .strong { font-weight: bold; }
        .legend { margin-top: 10px; padding: 5px; border: 1px dashed #999; text-align: center; font-size: 9px; }
        .watermark {
            position: fixed; top: 38%; left: 0; width: 100%; text-align: center;
            font-size: 90px; font-weight: bold; color: #c00; opacity: 0.15;
            transform: rotate(-30deg);
        }
        .footer { position: fixed; bottom: -26px; left: 0; right: 0; font-size: 8px; color: #888; }
    </style>
</head>
<body>
@if ($watermark)
    <div class="watermark">{{ $watermark }}</div>
@endif

<table class="header">
    <tr>
        <td style="width: 60%;">
            @if ($logo)
                <img src="{{ $logo }}" class="logo" alt="">
            @endif
            <div class="company-name">{{ $company['legal_name'] ?: $company['name'] }}</div>
            @if ($company['legal_name'] && $company['legal_name'] !== $company['name'])
                <div>{{ $company['name'] }}</div>
            @endif
            @if ($company['activity'])<div class="muted">{{ $company['activity'] }}</div>@endif
            @if ($company['address'])<div>{{ $company['address'] }}@if ($company['city']) – {{ $company['city'] }}@endif</div>@endif
            @if ($company['phone'] || $company['email'])
                <div>{{ collect([$company['phone'], $company['email']])->filter()->implode(' · ') }}</div>
            @endif
        </td>
        <td style="width: 40%;" class="box">
            @if ($company['tax_id'])<div><span class="strong">RUC:</span> {{ $company['tax_id'] }}</div>@endif
            @yield('document-box')
        </td>
    </tr>
</table>

@yield('content')

<div class="footer">Generado el {{ $generatedAt->format('d/m/Y H:i') }}</div>
</body>
</html>
