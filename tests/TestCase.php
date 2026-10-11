<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ninguna prueba debe llamar a un proveedor de IA real ni consumir tokens.
        Http::preventStrayRequests();
        // Las pruebas no dependen de que los recursos estén compilados ni de que Vite esté corriendo.
        $this->withoutVite();
    }
}
