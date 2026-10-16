<?php

namespace App\Services;

use App\Exceptions\InvoiceException;
use App\Models\InvoiceStamp;
use Illuminate\Support\Facades\DB;

/**
 * Timbrados: solo uno activo por establecimiento y punto de expedición.
 */
class InvoiceStampService
{
    public function create(array $data): InvoiceStamp
    {
        return DB::transaction(function () use ($data) {

            $stamp = new InvoiceStamp([
                'next_number' => 1,
                'is_active' => true,
                ...$data,
            ]);

            $this->ensureSingleActive($stamp);

            $stamp->save();

            return $stamp->refresh();
        });
    }

    public function update(InvoiceStamp $stamp, array $data): InvoiceStamp
    {
        return DB::transaction(function () use ($stamp, $data) {

            $stamp = InvoiceStamp::whereKey($stamp->id)->lockForUpdate()->firstOrFail();

            $stamp->fill($data);

            if ($stamp->valid_until->lt($stamp->valid_from)) {
                throw new InvoiceException(
                    'La fecha de vencimiento del timbrado no puede ser anterior al inicio de vigencia.',
                    422,
                    'INVOICE_STAMP_INVALID_PERIOD'
                );
            }

            $this->ensureSingleActive($stamp);

            $stamp->save();

            return $stamp->refresh();
        });
    }

    private function ensureSingleActive(InvoiceStamp $stamp): void
    {
        if (!$stamp->is_active) {
            return;
        }

        $other = InvoiceStamp::active()
            ->where('establishment', $stamp->establishment)
            ->where('expedition_point', $stamp->expedition_point)
            ->when($stamp->exists, fn ($query) => $query->whereKeyNot($stamp->id))
            ->exists();

        if ($other) {
            throw new InvoiceException(
                "Ya hay un timbrado activo para {$stamp->establishment}-{$stamp->expedition_point}. Desactívelo primero.",
                409,
                'INVOICE_STAMP_OVERLAP'
            );
        }
    }
}
