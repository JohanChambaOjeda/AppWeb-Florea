<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    /**
     * Los tests no dependen del build del frontend.
     *
     * La vista raíz carga el chunk de cada página por separado, así que sin
     * esto una pantalla nueva revienta con "Unable to locate file in Vite
     * manifest" hasta que alguien corra npm run build.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
