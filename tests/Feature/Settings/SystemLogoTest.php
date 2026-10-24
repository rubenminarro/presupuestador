<?php

namespace Tests\Feature\Settings;

use App\Mail\ClientNotificationMail;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Services\BrandingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Parts\PartsTestCase;

class SystemLogoTest extends PartsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function uploadLogo(string $name = 'logo.png')
    {
        return $this->post('/api/system-settings/logo', [
            'logo' => UploadedFile::fake()->image($name, 300, 100),
        ], ['Accept' => 'application/json']);
    }

    /**
     * PNG de 400×200 con fondo transparente y un rectángulo blanco de 200×60 en (100, 70): como un logo blanco con
     * márgenes.
     */
    private function transparentLogo(): UploadedFile
    {
        $image = imagecreatetruecolor(400, 200);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
        imagefilledrectangle($image, 100, 70, 299, 129, imagecolorallocate($image, 255, 255, 255));
        ob_start();
        imagepng($image);

        return UploadedFile::fake()->createWithContent('logo.png', (string) ob_get_clean());
    }

    private function storedImage(string $url): \GdImage
    {
        $path = SystemSetting::query()->whereIn('key', [SystemSetting::LOGO_PATH, SystemSetting::LOGO_BLACK_PATH])
            ->pluck('value')
            ->first(fn (string $path) => str_ends_with($url, $path));

        return imagecreatefromstring(Storage::disk('public')->get($path));
    }

    public function test_branding_is_public_and_starts_without_logo(): void
    {
        $this->getJson('/api/branding')
            ->assertOk()
            ->assertJsonPath('data.logo_url', null)
            ->assertJsonPath('data.logo_black_url', null);
    }

    public function test_a_transparent_png_is_trimmed_and_gets_a_black_version(): void
    {
        $this->actingAsAdmin();

        $data = $this->post('/api/system-settings/logo', ['logo' => $this->transparentLogo()], ['Accept' => 'application/json'])
            ->assertOk()
            ->json('data');

        $this->assertNotSame($data['logo_url'], $data['logo_black_url']);

        // Sin márgenes: queda solo el rectángulo, blanco en el logo y negro en la versión negra.
        $logo = $this->storedImage($data['logo_url']);
        $black = $this->storedImage($data['logo_black_url']);

        foreach ([$logo, $black] as $image) {
            $this->assertSame([200, 60], [imagesx($image), imagesy($image)]);
        }
        $this->assertSame(0xFFFFFF, imagecolorat($logo, 100, 30) & 0xFFFFFF);
        $this->assertSame(0x000000, imagecolorat($black, 100, 30) & 0xFFFFFF);
        $this->assertSame(0, (imagecolorat($black, 100, 30) >> 24) & 0x7F);

        // Al quitarlo se borran los dos archivos.
        $this->deleteJson('/api/system-settings/logo')->assertOk()->assertJsonPath('data.logo_black_url', null);
        $this->assertSame([], Storage::disk('public')->allFiles('branding'));
    }

    public function test_an_opaque_logo_keeps_its_size_and_documents_use_it(): void
    {
        $this->actingAsAdmin();

        $this->post('/api/system-settings/logo', ['logo' => $this->transparentLogo()], ['Accept' => 'application/json'])->assertOk();

        // Un JPG (sin transparencia) se usa tal cual y reemplaza también a la versión negra anterior.
        $data = $this->uploadLogo('logo.jpg')->assertOk()->json('data');

        $this->assertSame($data['logo_url'], $data['logo_black_url']);
        $this->assertSame([300, 100], [imagesx($this->storedImage($data['logo_url'])), imagesy($this->storedImage($data['logo_url']))]);
        $this->assertNull(SystemSetting::get(SystemSetting::LOGO_BLACK_PATH));
        $this->assertCount(1, Storage::disk('public')->allFiles('branding'));
    }

    public function test_admin_uploads_and_replaces_the_logo(): void
    {
        $this->actingAsAdmin();

        $first = $this->uploadLogo()->assertOk()->json('data.logo_url');
        $firstPath = SystemSetting::get(SystemSetting::LOGO_PATH);

        $this->assertNotNull($first);
        $this->assertStringStartsWith('branding/', $firstPath);
        Storage::disk('public')->assertExists($firstPath);
        $this->getJson('/api/branding')->assertJsonPath('data.logo_url', $first);

        // Reemplazo: URL nueva (sin caché del navegador) y el archivo anterior se borra.
        $second = $this->uploadLogo('nuevo.jpg')->assertOk()->json('data.logo_url');

        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($firstPath);
        $this->assertSame(1, SystemSetting::where('key', SystemSetting::LOGO_PATH)->count());

        // El cambio queda en la auditoría.
        $this->assertTrue(AuditLog::where('auditable_type', 'system_setting')->exists());
    }

    public function test_admin_deletes_the_logo(): void
    {
        $this->actingAsAdmin();
        $this->uploadLogo()->assertOk();
        $path = SystemSetting::get(SystemSetting::LOGO_PATH);

        $this->deleteJson('/api/system-settings/logo')
            ->assertOk()
            ->assertJsonPath('data.logo_url', null);

        Storage::disk('public')->assertMissing($path);
        $this->getJson('/api/branding')->assertJsonPath('data.logo_url', null);

        // Sin logo, borrar de nuevo no falla.
        $this->deleteJson('/api/system-settings/logo')->assertOk();
    }

    public function test_validation(): void
    {
        $this->actingAsAdmin();

        $this->post('/api/system-settings/logo', [], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonPath('errors.logo.0', 'Seleccioná el archivo del logo.');

        $this->post('/api/system-settings/logo', [
            'logo' => UploadedFile::fake()->image('logo.gif'),
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('errors.logo.0', 'El logo debe ser una imagen PNG o JPG.');

        $this->post('/api/system-settings/logo', [
            'logo' => UploadedFile::fake()->image('logo.png')->size(3000),
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('errors.logo.0', 'El logo no debe superar los 2 MB.');
    }

    public function test_only_the_administrator_changes_the_logo(): void
    {
        foreach (['recepcionista', 'jefe-taller', 'cajero', 'deposito', 'mechanic'] as $role) {
            $this->actingAsRole($role);

            $this->uploadLogo()->assertForbidden()->assertJsonPath('error_code', 'FORBIDDEN');
            $this->deleteJson('/api/system-settings/logo')->assertForbidden();
        }

        $this->assertNull(SystemSetting::get(SystemSetting::LOGO_PATH));
    }

    public function test_pdfs_include_the_logo(): void
    {
        $this->actingAsAdmin();

        $withoutLogo = $this->get('/api/reports/sales?format=pdf')->assertOk()->getContent();
        $this->assertStringNotContainsString('/Subtype /Image', $withoutLogo);

        $this->uploadLogo()->assertOk();

        $withLogo = $this->get('/api/reports/sales?format=pdf')->assertOk()->getContent();
        $this->assertStringContainsString('/Subtype /Image', $withLogo);
    }

    public function test_with_a_logo_pdfs_omit_the_company_name(): void
    {
        config(['company.name' => 'Taller Ejemplo', 'company.legal_name' => 'Taller Ejemplo S.A.', 'company.tax_id' => '80000001-1']);
        $render = fn () => view('pdf.report', [
            'company' => config('company'),
            'logo' => app(BrandingService::class)->logoDataUri(),
            'generatedAt' => now(),
            'watermark' => null,
            'title' => 'Reporte de ventas',
            'period' => 'Octubre 2026',
            'sections' => [],
        ])->render();

        $this->assertStringContainsString('Taller Ejemplo S.A.', $render());

        $this->actingAsAdmin();
        $this->uploadLogo()->assertOk();

        // Con logo, el nombre no se repite; el RUC y los demás datos siguen.
        $html = $render();
        $this->assertStringNotContainsString('Taller Ejemplo', $html);
        $this->assertStringContainsString('80000001-1', $html);
        $this->assertStringContainsString('data:image/', $html);
    }

    public function test_client_emails_embed_the_logo(): void
    {
        $mail = new ClientNotificationMail([
            'subject' => 'Aviso',
            'greeting' => 'Hola',
            'lines' => ['Tu vehículo está listo.'],
            'rows' => [],
        ]);

        $this->assertStringNotContainsString('cid:' . BrandingService::MAIL_LOGO_CID, $mail->render());

        $this->actingAsAdmin();
        $this->uploadLogo()->assertOk();

        $this->assertStringContainsString('cid:' . BrandingService::MAIL_LOGO_CID, $mail->render());

        // La imagen viaja como parte inline del mensaje (el mailer de los tests es "array").
        $sent = null;
        Event::listen(MessageSent::class, function (MessageSent $event) use (&$sent) {
            $sent = $event->message;
        });
        Mail::to('cliente@test.local')->send($mail);

        $inline = collect($sent->getAttachments())->first(fn ($part) => $part->getDisposition() === 'inline');
        $this->assertNotNull($inline);
        $this->assertSame(BrandingService::MAIL_LOGO_CID, $inline->getContentId());
    }
}
