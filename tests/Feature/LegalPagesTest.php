<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_legal_pages_are_available_and_describe_ai_and_cookies(): void
    {
        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('Uso de inteligencia artificial')
            ->assertSee('Google Gemini');

        $this->get(route('legal.terms'))
            ->assertOk()
            ->assertSee('Condiciones del servicio')
            ->assertSee('debe revisar');

        $this->get(route('legal.cookies'))
            ->assertOk()
            ->assertSee('sessionStorage')
            ->assertSee('No instalamos cookies de publicidad');
    }
}
