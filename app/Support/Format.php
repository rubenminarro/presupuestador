<?php

namespace App\Support;

/**
 * Formato de presentación (emails, comprobantes y reportes exportados).
 * Solo para mostrar: los cálculos siguen en Decimal/BCMath.
 */
class Format
{
    /**
     * Guaraníes sin decimales con separador de miles: "Gs. 1.250.000".
     */
    public static function money(int|float|string|null $amount): string
    {
        return 'Gs. ' . self::integer($amount);
    }

    /**
     * Entero redondeado (half-up) con separador de miles.
     */
    public static function integer(int|float|string|null $amount): string
    {
        return number_format((int) Decimal::round(Decimal::of($amount, 2), 0), 0, ',', '.');
    }

    /**
     * Cantidad sin ceros decimales sobrantes y con coma decimal: "1,5".
     */
    public static function quantity(int|float|string|null $quantity, int $scale = 3): string
    {
        $value = Decimal::of($quantity, $scale);

        if (str_contains($value, '.')) {
            $value = rtrim(rtrim($value, '0'), '.');
        }

        return str_replace('.', ',', $value);
    }
}
