@extends('pdf.layout')

@section('document-box')
    <h1>{{ mb_strtoupper($title) }}</h1>
    @if ($period)<div><span class="strong">Período:</span> {{ $period }}</div>@endif
@endsection

@section('content')
    @foreach ($sections as $section)
        <h2>{{ $section['title'] }}</h2>
        @if (count($section['rows']) === 0)
            <div class="muted">Sin datos en el período.</div>
        @else
            <table class="grid">
                <thead>
                    <tr>
                        @foreach ($section['columns'] as $column)
                            <th>{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($section['rows'] as $row)
                        <tr>
                            @foreach ($row as $cell)
                                <td @class(['num' => preg_match('/^-?[\d,]+$/', $cell)])>{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach
@endsection
