<x-mail::message>
# {{ $greeting }}

@foreach ($lines as $line)
{{ $line }}

@endforeach
@if (count($rows) > 0)
<x-mail::table>
| Detalle | Cantidad | Importe |
|:--------|---------:|--------:|
@foreach ($rows as $row)
| {{ $row['description'] }} | {{ $row['quantity'] }} | {{ $row['total'] }} |
@endforeach
</x-mail::table>
@endif

Saludos,<br>
{{ config('app.name') }}
</x-mail::message>
