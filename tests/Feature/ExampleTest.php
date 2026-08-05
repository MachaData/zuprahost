<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Deliberadamente sin RefreshDatabase: no hay tablas.
     *
     * La portada es lo primero que ve quien está evaluando si contratarnos.
     * Si una caída de la base de datos la tumbara, perderíamos también la
     * página que explica quiénes somos y cómo contactarnos, justo cuando peor
     * viene. Pierde los precios; el resto se sostiene.
     */
    public function test_the_home_page_survives_without_a_database(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Todo lo que tu sitio necesita');
    }
}
