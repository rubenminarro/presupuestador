<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Período de un reporte: por defecto, el mes en curso.
 */
class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'group_by' => ['nullable', 'in:day,month'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_from' => [
                'date_format' => 'La fecha desde debe tener el formato AAAA-MM-DD.',
            ],
            'date_to' => [
                'date_format' => 'La fecha hasta debe tener el formato AAAA-MM-DD.',
                'after_or_equal' => 'La fecha hasta debe ser igual o posterior a la fecha desde.',
            ],
            'group_by' => [
                'in' => 'La agrupación debe ser day o month.',
            ],
        ];
    }

    public function from(): CarbonInterface
    {
        return $this->filled('date_from') ? Carbon::parse($this->date_from) : now()->startOfMonth();
    }

    public function to(): CarbonInterface
    {
        return $this->filled('date_to') ? Carbon::parse($this->date_to) : now()->endOfMonth();
    }
}
