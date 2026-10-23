<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_is_a_public_commercial_page_with_app_access(): void
    {
        $this->get('/')->assertOk()->assertSee('Entrená con un plan')
            ->assertSee('Entrar a la app')->assertSee('/asesorias-online', false);
    }

    public function test_audience_and_content_pages_have_unique_titles_and_canonicals(): void
    {
        foreach (['asesorias-online', 'software-entrenadores-personales', 'software-gimnasios', 'planes', 'preguntas-frecuentes', 'recursos', 'contacto'] as $path) {
            $response = $this->get('/'.$path.'?utm_source=test')->assertOk();
            $response->assertSee('rel="canonical"', false)->assertDontSee('utm_source', false);
            $this->assertSame(1, substr_count($response->getContent(), '<h1'));
        }
        $this->get('/planes')->assertSee('14.900')->assertSee('69.900');
        $this->get('/recursos/como-registrar-tu-progreso')->assertOk()->assertSee('series');
        $this->get('/recursos/inexistente')->assertNotFound();
    }

    public function test_sitemap_is_public_only_and_robots_points_to_it(): void
    {
        $this->assertFileDoesNotExist(public_path('robots.txt'), 'Un archivo estático evita ejecutar las reglas de indexación configurables.');
        $response = $this->get('/sitemap.xml')->assertOk()->assertSee('<urlset', false);
        $response->assertSee('/asesorias-online')->assertDontSee('/app')->assertDontSee('/admin')->assertDontSee('/login');
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap:')->assertSee('Disallow: /app');
        config(['commerce.indexable' => false]);
        $this->get('/robots.txt')->assertOk()->assertSee("Disallow: /\n", false)->assertDontSee('Disallow: /app');
    }
}
