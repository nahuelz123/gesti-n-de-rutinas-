<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PexelsClient
{
    /**
     * Busca una foto en Pexels por término de búsqueda (en inglés funciona mejor).
     * Devuelve la URL de una imagen tamaño "large" (buena para mostrar en la app), o null si falla.
     */
    public function searchPhoto(string $query): ?string
    {
        $apiKey = config('services.pexels.key');

        if (! $apiKey) {
            Log::warning('Pexels: falta PEXELS_API_KEY en .env');

            return null;
        }

        try {
            $response = Http::withHeaders(['Authorization' => $apiKey])
                ->timeout(20)
                ->get('https://api.pexels.com/v1/search', [
                    'query' => $query,
                    'per_page' => 1,
                    'orientation' => 'landscape',
                ]);

            if ($response->failed()) {
                Log::error('Pexels API error', ['status' => $response->status(), 'body' => $response->body()]);

                return null;
            }

            return $response->json('photos.0.src.large');
        } catch (\Throwable $e) {
            Log::error('Pexels exception: '.$e->getMessage());

            return null;
        }
    }
}
