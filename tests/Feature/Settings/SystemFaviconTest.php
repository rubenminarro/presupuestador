<?php

namespace Tests\Feature\Settings;

use App\Models\SystemSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Parts\PartsTestCase;

class SystemFaviconTest extends PartsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function upload(UploadedFile $file): TestResponse
    {
        return $this->post('/api/system-settings/favicon', ['favicon' => $file], ['Accept' => 'application/json']);
    }

    private function pngContent(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }

    /** ICO de una sola imagen con el PNG embebido (formato que aceptan los navegadores desde Windows Vista). */
    private function ico(int $size): UploadedFile
    {
        $png = $this->pngContent($size, $size);
        $dimension = $size >= 256 ? 0 : $size;
        $content = pack('vvv', 0, 1, 1) . pack('CCCCvvVV', $dimension, $dimension, 0, 0, 1, 32, strlen($png), 22) . $png;

        return UploadedFile::fake()->createWithContent('favicon.ico', $content);
    }

    private function svg(string $body = '<circle cx="16" cy="16" r="16" fill="#206bc4"/>'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'favicon.svg',
            '<?xml version="1.0" encoding="UTF-8"?><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">' . $body . '</svg>'
        );
    }

    public function test_branding_starts_without_favicon(): void
    {
        $this->getJson('/api/branding')->assertOk()->assertJsonPath('data.favicon_url', null);
    }

    public function test_admin_uploads_png_ico_and_svg(): void
    {
        $this->actingAsAdmin();

        $png = $this->upload(UploadedFile::fake()->image('favicon.png', 64, 64))->assertOk()->json('data.favicon_url');
        $this->assertStringEndsWith('.png', $png);
        $this->getJson('/api/branding')->assertJsonPath('data.favicon_url', $png);

        // Cada reemplazo borra el archivo anterior; el logo no se toca.
        $ico = $this->upload($this->ico(32))->assertOk()->json('data.favicon_url');
        $this->assertStringEndsWith('.ico', $ico);

        $svg = $this->upload($this->svg())->assertOk()->assertJsonPath('data.logo_url', null)->json('data.favicon_url');
        $this->assertStringEndsWith('.svg', $svg);

        $this->assertCount(1, Storage::disk('public')->allFiles('branding'));
        $this->assertSame(1, SystemSetting::where('key', SystemSetting::FAVICON_PATH)->count());
    }

    public function test_admin_deletes_the_favicon(): void
    {
        $this->actingAsAdmin();
        $this->upload(UploadedFile::fake()->image('favicon.png', 32, 32))->assertOk();

        $this->deleteJson('/api/system-settings/favicon')->assertOk()->assertJsonPath('data.favicon_url', null);
        $this->assertSame([], Storage::disk('public')->allFiles('branding'));

        // Sin favicon, borrar de nuevo no falla.
        $this->deleteJson('/api/system-settings/favicon')->assertOk();
    }

    public function test_validation(): void
    {
        $this->actingAsAdmin();

        $this->post('/api/system-settings/favicon', [], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonPath('errors.favicon.0', 'Seleccioná el archivo del favicon.');

        $this->upload(UploadedFile::fake()->image('favicon.gif', 32, 32))
            ->assertStatus(422)
            ->assertJsonPath('errors.favicon.0', 'El favicon debe ser una imagen PNG, ICO o SVG.');

        $this->upload(UploadedFile::fake()->image('favicon.png', 64, 32))
            ->assertStatus(422)
            ->assertJsonPath('errors.favicon.0', 'El favicon debe ser cuadrado (la imagen mide 64×32 px).');

        $this->upload(UploadedFile::fake()->image('favicon.png', 8, 8))
            ->assertStatus(422)
            ->assertJsonPath('errors.favicon.0', 'El favicon debe medir entre 16 y 1024 px de lado.');

        $this->upload(UploadedFile::fake()->image('favicon.png', 32, 32)->size(600))
            ->assertStatus(422)
            ->assertJsonPath('errors.favicon.0', 'El favicon no debe superar los 512 KB.');

        foreach (['<script>alert(1)</script>', '<rect width="32" height="32" onload="alert(1)"/>', '<a href="javascript:alert(1)"><rect width="32" height="32"/></a>'] as $body) {
            $this->upload($this->svg($body))
                ->assertStatus(422)
                ->assertJsonPath('errors.favicon.0', 'El SVG no puede contener scripts, eventos ni enlaces javascript.');
        }

        $this->assertNull(SystemSetting::get(SystemSetting::FAVICON_PATH));
    }

    public function test_only_the_administrator_changes_the_favicon(): void
    {
        foreach (['recepcionista', 'jefe-taller', 'cajero', 'deposito', 'mechanic'] as $role) {
            $this->actingAsRole($role);

            $this->upload(UploadedFile::fake()->image('favicon.png', 32, 32))->assertForbidden()->assertJsonPath('error_code', 'FORBIDDEN');
            $this->deleteJson('/api/system-settings/favicon')->assertForbidden();
        }
    }
}
