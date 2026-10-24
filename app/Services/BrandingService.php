<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

/**
 * Logo del sistema: se guarda en el disco public (carpeta branding) y su ruta
 * en system_settings. Lo usan el frontend (login, barra de menú, ícono de la
 * pestaña), los PDF y los emails a clientes.
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
     *
     * @return array{logo_url: string|null}
     */
    public function publicData(): array
    {
        $path = $this->logoPath();

        return [
            'logo_url' => $path ? Storage::disk(self::DISK)->url($path) : null,
        ];
    }

    /**
     * Reemplaza el logo. Cada archivo tiene un nombre nuevo, así que la URL
     * cambia y ningún navegador muestra el anterior desde su caché.
     */
    public function storeLogo(UploadedFile $file): array
    {
        $previous = SystemSetting::get(SystemSetting::LOGO_PATH);
        $path = $file->store('branding', self::DISK);

        try {
            DB::transaction(fn () => SystemSetting::updateOrCreate(
                ['key' => SystemSetting::LOGO_PATH],
                ['value' => $path]
            ));
        } catch (\Throwable $e) {
            Storage::disk(self::DISK)->delete($path);

            throw $e;
        }

        if ($previous) {
            Storage::disk(self::DISK)->delete($previous);
        }

        return $this->publicData();
    }

    /**
     * Quita el logo (sin logo, cada lugar vuelve a mostrar el nombre).
     */
    public function deleteLogo(): array
    {
        $setting = SystemSetting::where('key', SystemSetting::LOGO_PATH)->first();

        if ($setting) {
            $path = $setting->value;
            $setting->delete();

            if ($path) {
                Storage::disk(self::DISK)->delete($path);
            }
        }

        return $this->publicData();
    }

    /**
     * Logo como data URI para los PDF: dompdf no lee del disco público por URL.
     */
    public function logoDataUri(): ?string
    {
        $logo = $this->logoFile();

        return $logo ? "data:{$logo['mime']};base64," . base64_encode($logo['content']) : null;
    }

    /**
     * Contenido y tipo del logo, o null si no hay uno cargado.
     *
     * @return array{content: string, mime: string}|null
     */
    public function logoFile(): ?array
    {
        $path = $this->logoPath();

        if ($path === null) {
            return null;
        }

        return [
            'content' => Storage::disk(self::DISK)->get($path),
            'mime' => Storage::disk(self::DISK)->mimeType($path) ?: 'image/png',
        ];
    }

    public function hasLogo(): bool
    {
        return $this->logoPath() !== null;
    }

    /**
     * Adjunta el logo como imagen inline del email, para la cabecera. Sin logo
     * no adjunta nada y la cabecera muestra el nombre.
     */
    public function embedLogo(Email $message): void
    {
        $logo = $this->logoFile();

        if ($logo) {
            $message->addPart(
                (new DataPart($logo['content'], 'logo', $logo['mime']))->asInline()->setContentId(self::MAIL_LOGO_CID)
            );
        }
    }

    private function logoPath(): ?string
    {
        $path = SystemSetting::get(SystemSetting::LOGO_PATH);

        return $path && Storage::disk(self::DISK)->exists($path) ? $path : null;
    }
}
