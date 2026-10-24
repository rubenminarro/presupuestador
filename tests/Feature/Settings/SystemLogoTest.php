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

    public function test_branding_is_public_and_starts_without_logo(): void
    {
        $this->getJson('/api/branding')
            ->assertOk()
            ->assertJsonPath('data.logo_url', null);
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
