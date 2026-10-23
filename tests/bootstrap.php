<?php

require __DIR__ . '/../vendor/autoload.php';

/*
 * Base de los tests en un archivo SQLite (ignorado por git). Con `:memory:` RefreshDatabase volvía a correr todas las
 * migraciones en cada test (~0,4 s); con un archivo migra una vez por ejecución y aísla cada test en una transacción.
 * La ruta se fija absoluta para que no dependa del directorio desde el que se lanza PHPUnit.
 */
$database = __DIR__ . '/../database/testing.sqlite';

if (! file_exists($database)) {
    touch($database);
}

$database = realpath($database);
putenv("DB_DATABASE={$database}");
$_ENV['DB_DATABASE'] = $_SERVER['DB_DATABASE'] = $database;
