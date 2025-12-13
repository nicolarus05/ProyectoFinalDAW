<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Ejecutar migraciones de tenant para tests
        $this->initializeTenancyForTests();
    }
    
    /**
     * Inicializar tenancy para tests
     */
    protected function initializeTenancyForTests(): void
    {
        // Ejecutar migraciones de tenant en la base de datos de prueba
        Artisan::call('migrate', [
            '--path' => 'database/migrations/tenant',
            '--realpath' => true,
        ]);
    }
}
