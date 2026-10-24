<?php

namespace Tests\Feature\Preferences;

use App\Models\UserPreference;
use App\Support\UserPreferences;
use Tests\Feature\Parts\PartsTestCase;

class UserPreferenceTest extends PartsTestCase
{
    public function test_without_saved_preferences_returns_the_defaults(): void
    {
        $this->actingAsMechanic();

        $this->getJson('/api/me/preferences')
            ->assertOk()
            ->assertJsonPath('data', UserPreferences::DEFAULTS);

        $this->getJson('/api/me')->assertOk()->assertJsonPath('data.preferences', UserPreferences::DEFAULTS);
    }

    public function test_partial_update_keeps_the_other_values(): void
    {
        $user = $this->actingAsRole('cajero');

        $this->patchJson('/api/me/preferences', ['theme' => 'dark', 'theme_primary' => 'green'])
            ->assertOk()
            ->assertJsonPath('data.theme', 'dark')
            ->assertJsonPath('data.theme_primary', 'green')
            ->assertJsonPath('data.sidebar', 'default');

        $this->patchJson('/api/me/preferences', ['theme_radius' => 0.5, 'sidebar' => 'folded'])
            ->assertOk()
            ->assertJsonPath('data.theme', 'dark')
            ->assertJsonPath('data.theme_radius', '0.5')
            ->assertJsonPath('data.sidebar', 'folded');

        // Solo se guarda lo que difiere del predeterminado.
        $this->assertSame(
            ['theme' => 'dark', 'theme_primary' => 'green', 'theme_radius' => '0.5', 'sidebar' => 'folded'],
            UserPreference::where('user_id', $user->id)->sole()->settings
        );

        // GET /me las devuelve para aplicarlas al iniciar sesión.
        $this->getJson('/api/me')
            ->assertJsonPath('data.preferences.theme', 'dark')
            ->assertJsonPath('data.preferences.layout', 'default');
    }

    public function test_validation(): void
    {
        $this->actingAsMechanic();

        $this->patchJson('/api/me/preferences', ['theme' => 'sepia', 'layout' => ''])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonPath('errors.theme.0', 'El modo de color debe ser auto, light, dark.')
            ->assertJsonValidationErrors(['theme', 'layout']);

        $this->patchJson('/api/me/preferences', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['preferences']);

        $this->patchJson('/api/me/preferences', ['theme' => 'dark', 'currency' => 'USD'])
            ->assertStatus(422)
            ->assertJsonPath('errors.currency.0', 'La preferencia currency no existe.');

        $this->getJson('/api/me/preferences')->assertJsonPath('data.theme', 'auto');
    }

    public function test_reset_restores_the_defaults(): void
    {
        $user = $this->actingAsRole('deposito');

        $this->patchJson('/api/me/preferences', ['theme' => 'dark', 'navbar_position' => 'horizontal'])->assertOk();

        $this->deleteJson('/api/me/preferences')
            ->assertOk()
            ->assertJsonPath('data', UserPreferences::DEFAULTS);

        $this->assertDatabaseMissing('user_preferences', ['user_id' => $user->id]);
        $this->getJson('/api/me/preferences')->assertJsonPath('data', UserPreferences::DEFAULTS);
    }

    public function test_each_user_has_their_own_preferences(): void
    {
        $this->actingAsRole('recepcionista');
        $this->patchJson('/api/me/preferences', ['theme' => 'dark'])->assertOk();

        $this->actingAsRole('jefe-taller');
        $this->getJson('/api/me/preferences')->assertJsonPath('data.theme', 'auto');
        $this->deleteJson('/api/me/preferences')->assertOk();

        $this->assertSame(1, UserPreference::count());
    }

    public function test_stored_values_outside_the_catalog_fall_back_to_the_default(): void
    {
        $user = $this->actingAsMechanic();

        UserPreference::create(['user_id' => $user->id, 'settings' => ['theme' => 'dark', 'theme_primary' => 'gold', 'old' => 'x']]);

        $this->getJson('/api/me/preferences')
            ->assertJsonPath('data.theme', 'dark')
            ->assertJsonPath('data.theme_primary', 'blue')
            ->assertJsonMissingPath('data.old');
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/me/preferences')->assertUnauthorized();
        $this->patchJson('/api/me/preferences', ['theme' => 'dark'])->assertUnauthorized();
        $this->deleteJson('/api/me/preferences')->assertUnauthorized();
    }
}
