<?php

namespace Tests;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Roles y permisos se siembran una sola vez, junto con la migración de RefreshDatabase (que es una por ejecución:
     * cada test corre dentro de una transacción). Antes se sembraban en cada setUp (~0,2 s por test).
     */
    protected $seeder = RolesAndPermissionsSeeder::class;
}
