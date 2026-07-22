<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepSeekClient
{
    /**
     * Envía una conversación a DeepSeek y devuelve la respuesta del asistente.
     *
     * @param  string  $systemPrompt  Instrucciones de contexto (rol, datos del cliente, etc.)
     * @param  array<int, array{role: string, content: string}>  $messages  Historial user/assistant
     * @return string|null  La respuesta del modelo, o null si falló.
     */
    public function chat(string $systemPrompt, array $messages): ?string
    {
        $apiKey = config('services.deepseek.key');

        if (! $apiKey) {
            Log::warning('DeepSeek: falta DEEPSEEK_API_KEY en .env');

            return null;
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post('https://api.deepseek.com/chat/completions', [
                    'model' => config('services.deepseek.model', 'deepseek-chat'),
                    'messages' => array_merge(
                        [['role' => 'system', 'content' => $systemPrompt]],
                        $messages
                    ),
                    'temperature' => 0.6,
                    'max_tokens' => 600,
                ]);

            if ($response->failed()) {
                Log::error('DeepSeek API error', ['status' => $response->status(), 'body' => $response->body()]);

                return null;
            }

            return $response->json('choices.0.message.content');
        } catch (\Throwable $e) {
            Log::error('DeepSeek exception: '.$e->getMessage());

            return null;
        }
    }
}
