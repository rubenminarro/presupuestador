<?php

namespace Tests\Feature\Roles;

use Tests\Feature\Parts\PartsTestCase;

class PermissionsGroupedByModuleTest extends PartsTestCase
{
    public function test_admin_gets_permissions_grouped_by_module(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson('/api/roles/permissions-grouped-by-module')
            ->assertOk()
            ->assertJsonPath('success', true);

        $modules = collect($response->json('data'))->pluck('module');

        $this->assertContains('roles', $modules);
        $this->assertContains('work-orders', $modules);
    }

    public function test_mechanic_is_forbidden(): void
    {
        $this->actingAsMechanic();

        $this->getJson('/api/roles/permissions-grouped-by-module')
            ->assertForbidden()
            ->assertJsonPath('error_code', 'FORBIDDEN');
    }
}
