<?php

namespace App\Http\Controllers;

use App\Services\CommercialCatalogWriter;
use App\Services\CommercialPricing;
use Illuminate\Http\Request;

class CommercialCatalogController extends Controller
{
    public function index(Request $request, CommercialPricing $pricing)
    {
        abort_unless($request->user()->role === 'super_admin', 403);

        return view('marketing.commercial.pricing', [
            'items' => $pricing->items(), 'promotion' => $pricing->promotion(), 'version' => $pricing->version(),
            'meta' => ['title' => 'Precios y promociones | VisionFit', 'description' => 'Administrá los precios y la oferta publicada.', 'path' => '/gestion-comercial/precios'],
        ]);
    }

    public function update(Request $request, CommercialCatalogWriter $writer)
    {
        $writer->save($request->user(), $request->all());

        return redirect('/gestion-comercial/precios')->with('status', 'Precios y detalles guardados. La web ya muestra el catálogo actualizado.');
    }

    public function promotion(Request $request, CommercialCatalogWriter $writer)
    {
        abort_unless($request->user()->role === 'super_admin', 403);
        $data = $request->validate(['enabled' => ['required', 'boolean'], 'catalog_version' => ['required', 'integer', 'min:0']]);
        $enabled = (bool) $data['enabled'];
        $writer->setEnabled($request->user(), $enabled, (int) $data['catalog_version']);

        return redirect('/gestion-comercial/precios')->with('status', $enabled ? 'Descuento activado en la web.' : 'Descuento desactivado. La web vuelve a mostrar los precios base.');
    }
}
