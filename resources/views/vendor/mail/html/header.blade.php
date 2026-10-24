@props(['url'])
{{-- Cabecera de los emails: el logo del sistema embebido (CID) o, sin logo, el nombre. El mailable adjunta la imagen con BrandingService::embedLogo(). --}}
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (app(\App\Services\BrandingService::class)->hasLogo())
<img src="cid:{{ \App\Services\BrandingService::MAIL_LOGO_CID }}" alt="{{ trim($slot) }}" style="height: 48px; max-height: 48px; width: auto;">
@else
{!! $slot !!}
@endif
</a>
</td>
</tr>
