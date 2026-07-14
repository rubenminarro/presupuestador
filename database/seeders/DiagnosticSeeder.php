<?php

namespace Database\Seeders;

use App\Models\Diagnostic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Enums\Priority;
use App\Enums\DiagnosticStatus;

class DiagnosticSeeder extends Seeder
{
    public function run(): void
    {
        $diagnostics = [

            [
                'reception_id' => 1,
                'mechanic_id' => 1,
                'customer_complaint' => 'Ruido en suspensión delantera.',
                'diagnosis' => 'Amortiguador delantero derecho con pérdida de aceite.',
                'recommendation' => 'Cambiar ambos amortiguadores delanteros.',
                'priority' => Priority::HIGH->value,
                'status' => DiagnosticStatus::PENDING->value,
                'requires_parts' => true,
                'requires_repair' => true,
            ],

            [
                'reception_id' => 1,
                'mechanic_id' => 2,
                'customer_complaint' => 'Dirección vibrando.',
                'diagnosis' => 'Rótulas con desgaste.',
                'recommendation' => 'Reemplazar ambas rótulas.',
                'priority' => Priority::MEDIUM->value,
                'status' => DiagnosticStatus::IN_PROGRESS->value,
                'requires_parts' => true,
                'requires_repair' => true,
            ],

            [
                'reception_id' => 2,
                'mechanic_id' => 2,
                'customer_complaint' => 'Puerta trasera golpeada.',
                'diagnosis' => 'Daño superficial de chapa.',
                'recommendation' => 'Chapería y pintura.',
                'priority' => Priority::LOW->value,
                'status' => DiagnosticStatus::PENDING->value,
                'requires_parts' => false,
                'requires_repair' => true,
            ],

            [
                'reception_id' => 2,
                'mechanic_id' => 1,
                'customer_complaint' => 'Rayón profundo.',
                'diagnosis' => 'Necesita pintura completa del panel.',
                'recommendation' => 'Lijado, primer y pintura.',
                'priority' => Priority::LOW->value,
                'status' => DiagnosticStatus::COMPLETED->value,
                'requires_parts' => false,
                'requires_repair' => true,
            ],

            [
                'reception_id' => 3,
                'mechanic_id' => 1,
                'customer_complaint' => 'Golpes al pasar lomadas.',
                'diagnosis' => 'Amortiguadores traseros agotados.',
                'recommendation' => 'Cambio de amortiguadores.',
                'priority' => Priority::HIGH->value,
                'status' => DiagnosticStatus::PENDING->value,
                'requires_parts' => true,
                'requires_repair' => true,
            ],

            [
                'reception_id' => 3,
                'mechanic_id' => 2,
                'customer_complaint' => 'Paragolpes roto.',
                'diagnosis' => 'Fisura importante.',
                'recommendation' => 'Reparar o reemplazar.',
                'priority' => Priority::MEDIUM->value,
                'status' => DiagnosticStatus::IN_PROGRESS->value,
                'requires_parts' => false,
                'requires_repair' => true,
            ],

            [
                'reception_id' => 4,
                'mechanic_id' => 2,
                'customer_complaint' => 'Frena poco.',
                'diagnosis' => 'Pastillas desgastadas.',
                'recommendation' => 'Cambiar pastillas delanteras.',
                'priority' => Priority::HIGH->value,
                'status' => DiagnosticStatus::COMPLETED->value,
                'requires_parts' => true,
                'requires_repair' => true,
            ],

            [
                'reception_id' => 4,
                'mechanic_id' => 1,
                'customer_complaint' => 'Pedal muy bajo.',
                'diagnosis' => 'Discos rayados.',
                'recommendation' => 'Cambiar discos delanteros.',
                'priority' => Priority::HIGH->value,
                'status' => DiagnosticStatus::PENDING->value,
                'requires_parts' => true,
                'requires_repair' => true,
            ],

            [
                'reception_id' => 5,
                'mechanic_id' => 1,
                'customer_complaint' => 'Capó deformado.',
                'diagnosis' => 'Golpe frontal.',
                'recommendation' => 'Enderezar y pintar.',
                'priority' => Priority::MEDIUM->value,
                'status' => DiagnosticStatus::PENDING->value,
                'requires_parts' => false,
                'requires_repair' => true,
            ],

            [
                'reception_id' => 5,
                'mechanic_id' => 2,
                'customer_complaint' => 'Guardabarros roto.',
                'diagnosis' => 'No admite reparación.',
                'recommendation' => 'Reemplazar guardabarros.',
                'priority' => Priority::MEDIUM->value,
                'status' => DiagnosticStatus::IN_PROGRESS->value,
                'requires_parts' => true,
                'requires_repair' => true,
            ],

            [
                'reception_id' => 1,
                'mechanic_id' => 1,
                'customer_complaint' => 'Motor regula mal.',
                'diagnosis' => 'Bujías desgastadas.',
                'recommendation' => 'Cambio de bujías.',
                'priority' => Priority::LOW->value,
                'status' => DiagnosticStatus::COMPLETED->value,
                'requires_parts' => true,
                'requires_repair' => true,
            ],

            [
                'reception_id' => 2,
                'mechanic_id' => 2,
                'customer_complaint' => 'Consumo elevado.',
                'diagnosis' => 'Filtro de aire obstruido.',
                'recommendation' => 'Reemplazar filtro.',
                'priority' => Priority::LOW->value,
                'status' => DiagnosticStatus::COMPLETED->value,
                'requires_parts' => true,
                'requires_repair' => true,
            ],

            [
                'reception_id' => 3,
                'mechanic_id' => 1,
                'customer_complaint' => 'Aire acondicionado no enfría.',
                'diagnosis' => 'Carga de gas insuficiente.',
                'recommendation' => 'Recargar refrigerante.',
                'priority' => Priority::MEDIUM->value,
                'status' => DiagnosticStatus::PENDING->value,
                'requires_parts' => false,
                'requires_repair' => true,
            ],

            [
                'reception_id' => 4,
                'mechanic_id' => 2,
                'customer_complaint' => 'Batería descargada.',
                'diagnosis' => 'Alternador sin carga.',
                'recommendation' => 'Reparar alternador.',
                'priority' => Priority::HIGH->value,
                'status' => DiagnosticStatus::IN_PROGRESS->value,
                'requires_parts' => true,
                'requires_repair' => true,
            ],

            [
                'reception_id' => 5,
                'mechanic_id' => 1,
                'customer_complaint' => 'Luz ABS encendida.',
                'diagnosis' => 'Sensor delantero averiado.',
                'recommendation' => 'Cambiar sensor ABS.',
                'priority' => Priority::HIGH->value,
                'status' => DiagnosticStatus::PENDING->value,
                'requires_parts' => true,
                'requires_repair' => true,
            ],

            [
                'reception_id' => 1,
                'mechanic_id' => 2,
                'customer_complaint' => 'Pérdida de aceite.',
                'diagnosis' => 'Retén del cigüeñal con fuga.',
                'recommendation' => 'Reemplazar retén.',
                'priority' => Priority::HIGH->value,
                'status' => DiagnosticStatus::PENDING->value,
                'requires_parts' => true,
                'requires_repair' => true,
            ],

            [
                'reception_id' => 2,
                'mechanic_id' => 1,
                'customer_complaint' => 'Caja hace ruido.',
                'diagnosis' => 'Rodamiento deteriorado.',
                'recommendation' => 'Desarmar y reparar caja.',
                'priority' => Priority::HIGH->value,
                'status' => DiagnosticStatus::IN_PROGRESS->value,
                'requires_parts' => true,
                'requires_repair' => true,
            ],

            [
                'reception_id' => 3,
                'mechanic_id' => 2,
                'customer_complaint' => 'Volante duro.',
                'diagnosis' => 'Bomba hidráulica dañada.',
                'recommendation' => 'Reemplazar bomba.',
                'priority' => Priority::MEDIUM->value,
                'status' => DiagnosticStatus::PENDING->value,
                'requires_parts' => true,
                'requires_repair' => true,
            ],

            [
                'reception_id' => 4,
                'mechanic_id' => 1,
                'customer_complaint' => 'Motor se calienta.',
                'diagnosis' => 'Radiador obstruido.',
                'recommendation' => 'Limpiar o reemplazar radiador.',
                'priority' => Priority::HIGH->value,
                'status' => DiagnosticStatus::PENDING->value,
                'requires_parts' => true,
                'requires_repair' => true,
            ],

            [
                'reception_id' => 5,
                'mechanic_id' => 2,
                'customer_complaint' => 'Embrague patina.',
                'diagnosis' => 'Disco de embrague desgastado.',
                'recommendation' => 'Kit de embrague completo.',
                'priority' => Priority::HIGH->value,
                'status' => DiagnosticStatus::COMPLETED->value,
                'requires_parts' => true,
                'requires_repair' => true,
            ],

        ];

        foreach ($diagnostics as $data) {

            DB::transaction(function () use ($data) {

                Diagnostic::firstOrCreate(
                    [
                        'reception_id' => $data['reception_id'],
                        'customer_complaint' => $data['customer_complaint'],
                    ],
                    array_merge($data, [
                        'diagnosed_at' => now()->subDays(rand(1, 15)),
                    ])
                );

            });

        }
    }
}