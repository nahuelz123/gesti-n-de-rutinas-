<?php

namespace App\Http\Controllers;

use App\Support\CommercialCatalog;
use Illuminate\Http\Request;

class MarketingController extends Controller
{
    public function show(Request $request, string $page = 'home')
    {
        $meta = CommercialCatalog::pages()[$page] ?? abort(404);

        return view('marketing.page', compact('page', 'meta') + [
            'plans' => CommercialCatalog::plans(), 'faqs' => CommercialCatalog::faqs(), 'guides' => CommercialCatalog::guides(),
        ]);
    }

    public function guide(string $slug)
    {
        $guide = CommercialCatalog::guides()[$slug] ?? abort(404);

        return view('marketing.guide', compact('guide') + ['meta' => $guide + ['path' => '/recursos/'.$slug]]);
    }

    public function sitemap()
    {
        $paths = array_column(CommercialCatalog::pages(), 'path');
        foreach (array_keys(CommercialCatalog::guides()) as $slug) {
            $paths[] = '/recursos/'.$slug;
        }
        $origin = rtrim(config('commerce.public_url'), '/');
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($paths as $path) {
            $xml .= '<url><loc>'.htmlspecialchars($origin.$path, ENT_XML1, 'UTF-8').'</loc></url>';
        }

        return response($xml.'</urlset>')->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots()
    {
        $rules = "User-agent: *\n";
        foreach (['/app', '/admin', '/login', '/register', '/mi-suscripcion', '/empezar', '/webhooks', '/gestion-comercial', '/experiencia', '/unirse', '/auth'] as $path) {
            $rules .= 'Disallow: '.$path."\n";
        }
        if (! config('commerce.indexable')) {
            $rules = "User-agent: *\nDisallow: /\n";
        }

        return response($rules.'Sitemap: '.rtrim(config('commerce.public_url'), '/')."/sitemap.xml\n")->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
