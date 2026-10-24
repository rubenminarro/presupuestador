<?php

namespace App\Services;

use App\Models\SystemSetting;
use App\Support\LogoImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

/**
 * Logo del sistema: se guarda en el disco public (carpeta branding) y su ruta
 * en system_settings, junto con la versión negra que se genera al subirlo
 * (App\Support\LogoImage). Lo usan el frontend (login, barra de menú, ícono de
 * la pestaña), los PDF y los emails a clientes; los documentos usan la negra.
 */
class BrandingService
{
    private const DISK = 'public';

    /**
     * Content-ID del logo embebido en los emails (cabecera en resources/views/vendor/mail/html/header.blade.php).
     */
    public const MAIL_LOGO_CID = 'logo@synctaller';

    /**
     * Datos públicos (sin sesión): el login los necesita antes de autenticar.
     * logo_black_url es la versión para fondos claros; sin versión negra, el
     * mismo logo.
     *
     * @return array{logo_url: string|null, logo_black_url: string|null}
     */
    public function publicData(): array
    {
        $logo = $this->path(SystemSetting::LOGO_PATH);
        $black = $logo ? ($this->path(SystemSetting::LOGO_BLACK_PATH) ?? $logo) : null;

        return [
            'logo_url' => $logo ? Storage::disk(self::DISK)->url($logo) : null,
            'logo_black_url' => $black ? Storage::disk(self::DISK)->url($black) : null,
        ];
    }

    /**
     * Reemplaza el logo. Un PNG con transparencia se recorta y genera su
     * versión negra. Cada archivo tiene un nombre nuevo, así que la URL cambia
     * y ningún navegador muestra el anterior desde su caché.
     */
    public function storeLogo(UploadedFile $file): array
    {
        $previous = array_filter([
            SystemSetting::get(SystemSetting::LOGO_PATH),
            SystemSetting::get(SystemSetting::LOGO_BLACK_PATH),
        ]);

        $processed = LogoImage::process($file->getContent());
        $extension = $processed['black'] === null ? ($file->guessExtension() ?: 'png') : 'png';
        $paths = [SystemSetting::LOGO_PATH => $this->put($processed['logo'], $extension)];

        if ($processed['black'] !== null) {
            $paths[SystemSetting::LOGO_BLACK_PATH] = $this->put($processed['black'], 'png');
        }

        try {
            DB::transaction(function () use ($paths) {
                SystemSetting::updateOrCreate(['key' => SystemSetting::LOGO_PATH], ['value' => $paths[SystemSetting::LOGO_PATH]]);

                // Sin versión negra (JPG o PNG opaco), la anterior ya no corresponde.
                isset($paths[SystemSetting::LOGO_BLACK_PATH])
                    ? SystemSetting::updateOrCreate(['key' => SystemSetting::LOGO_BLACK_PATH], ['value' => $paths[SystemSetting::LOGO_BLACK_PATH]])
                    : SystemSetting::where('key', SystemSetting::LOGO_BLACK_PATH)->first()?->delete();
            });
        } catch (\Throwable $e) {
            Storage::disk(self::DISK)->delete(array_values($paths));

            throw $e;
        }

        Storage::disk(self::DISK)->delete(array_values($previous));

        return $this->publicData();
    }

    /**
     * Quita el logo y su versión negra (sin logo, cada lugar vuelve a mostrar el nombre).
     */
    public function deleteLogo(): array
    {
        $settings = SystemSetting::whereIn('key', [SystemSetting::LOGO_PATH, SystemSetting::LOGO_BLACK_PATH])->get();

        foreach ($settings as $setting) {
            $setting->delete();

            if ($setting->value) {
                Storage::disk(self::DISK)->delete($setting->value);
            }
        }

        return $this->publicData();
    }

    /**
     * Logo para los PDF (la versión negra) como data URI: dompdf no lee del disco público por URL.
     */
    public function logoDataUri(): ?string
    {
        $logo = $this->documentLogo();

        return $logo ? "data:{$logo['mime']};base64," . base64_encode($logo['content']) : null;
    }

    public function hasLogo(): bool
    {
        return $this->path(SystemSetting::LOGO_PATH) !== null;
    }

    /**
     * Adjunta el logo (la versión negra: el email tiene fondo claro) como imagen inline, para la cabecera. Sin
     * logo no adjunta nada y la cabecera muestra el nombre.
     */
    public function embedLogo(Email $message): void
    {
        $logo = $this->documentLogo();

        if ($logo) {
            $message->addPart(
                (new DataPart($logo['content'], 'logo', $logo['mime']))->asInline()->setContentId(self::MAIL_LOGO_CID)
            );
        }
    }

    /**
     * Contenido y tipo del logo para documentos (fondo blanco): la versión negra o, sin ella, el original.
     *
     * @return array{content: string, mime: string}|null
     */
    private function documentLogo(): ?array
    {
        $path = $this->path(SystemSetting::LOGO_BLACK_PATH) ?? $this->path(SystemSetting::LOGO_PATH);

        if ($path === null) {
            return null;
        }

        return [
            'content' => Storage::disk(self::DISK)->get($path),
            'mime' => Storage::disk(self::DISK)->mimeType($path) ?: 'image/png',
        ];
    }

    private function put(string $content, string $extension): string
    {
        $path = 'branding/' . Str::random(40) . '.' . $extension;
        Storage::disk(self::DISK)->put($path, $content);

        return $path;
    }

    private function path(string $key): ?string
    {
        $path = SystemSetting::get($key);

        return $path && Storage::disk(self::DISK)->exists($path) ? $path : null;
    }
}
