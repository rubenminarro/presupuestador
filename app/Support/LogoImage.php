<?php

namespace App\Support;

use GdImage;

/**
 * Procesamiento del logo con GD. Solo los PNG con transparencia se procesan:
 * se recortan los márgenes transparentes y se genera una versión negra (cada
 * píxel visible pasa a negro conservando su transparencia), para fondos
 * claros, PDF y emails. Un JPG o un PNG sin transparencia se usa tal cual.
 */
final class LogoImage
{
    /** Lado máximo después de recortar: el logo se muestra a menos de 300 px. */
    private const MAX_SIZE = 1200;

    /** Alfa de GD: 0 opaco, 127 transparente. Por encima de este valor el píxel se considera fondo. */
    private const VISIBLE_ALPHA = 120;

    /**
     * @return array{logo: string, black: string|null} Contenidos PNG (o el original sin tocar y black null).
     */
    public static function process(string $content): array
    {
        $image = @imagecreatefromstring($content);

        if (!$image instanceof GdImage || !self::hasTransparency($image)) {
            return ['logo' => $content, 'black' => null];
        }

        // Se achica antes de recortar (recorrer píxel por píxel una imagen enorme es lento) y otra vez después.
        $image = self::limitSize(self::trim(self::limitSize($image, 2 * self::MAX_SIZE)), self::MAX_SIZE);

        return ['logo' => self::png($image), 'black' => self::png(self::blacken($image))];
    }

    private static function hasTransparency(GdImage $image): bool
    {
        if (imageistruecolor($image) === false) {
            imagepalettetotruecolor($image);
        }

        // Muestreo de una grilla: alcanza para distinguir un PNG con fondo transparente de uno opaco.
        $stepX = max(1, intdiv(imagesx($image), 100));
        $stepY = max(1, intdiv(imagesy($image), 100));

        for ($x = 0; $x < imagesx($image); $x += $stepX) {
            for ($y = 0; $y < imagesy($image); $y += $stepY) {
                if (self::alpha($image, $x, $y) > self::VISIBLE_ALPHA) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function trim(GdImage $image): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        [$left, $top, $right, $bottom] = [$width, $height, -1, -1];

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if (self::alpha($image, $x, $y) <= self::VISIBLE_ALPHA) {
                    $left = min($left, $x);
                    $right = max($right, $x);
                    $top = min($top, $y);
                    $bottom = max($bottom, $y);
                }
            }
        }

        // Todo transparente: no hay nada que recortar.
        if ($right < 0) {
            return $image;
        }

        return self::copy($image, $left, $top, $right - $left + 1, $bottom - $top + 1);
    }

    private static function limitSize(GdImage $image, int $maxSize): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if (max($width, $height) <= $maxSize) {
            return $image;
        }

        $scale = $maxSize / max($width, $height);

        $resized = self::canvas((int) round($width * $scale), (int) round($height * $scale));
        imagecopyresampled($resized, $image, 0, 0, 0, 0, imagesx($resized), imagesy($resized), $width, $height);

        return $resized;
    }

    private static function blacken(GdImage $image): GdImage
    {
        $black = self::canvas(imagesx($image), imagesy($image));

        for ($y = 0; $y < imagesy($image); $y++) {
            for ($x = 0; $x < imagesx($image); $x++) {
                imagesetpixel($black, $x, $y, imagecolorallocatealpha($black, 0, 0, 0, self::alpha($image, $x, $y)));
            }
        }

        return $black;
    }

    private static function copy(GdImage $image, int $x, int $y, int $width, int $height): GdImage
    {
        $copy = self::canvas($width, $height);
        imagecopy($copy, $image, 0, 0, $x, $y, $width, $height);

        return $copy;
    }

    /** Lienzo transparente que conserva el canal alfa al copiar y al guardar. */
    private static function canvas(int $width, int $height): GdImage
    {
        $canvas = imagecreatetruecolor($width, $height);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));

        return $canvas;
    }

    private static function alpha(GdImage $image, int $x, int $y): int
    {
        return (imagecolorat($image, $x, $y) >> 24) & 0x7F;
    }

    private static function png(GdImage $image): string
    {
        imagesavealpha($image, true);
        ob_start();
        imagepng($image, null, 9);

        return (string) ob_get_clean();
    }
}
