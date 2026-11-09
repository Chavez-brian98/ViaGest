<?php

namespace Tests;

use App\Service\Configuracion\ConfiguracionService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        ConfiguracionService::limpiarCache();
    }
}
