<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepSeekClient
{
    /**
     * Solo reintentamos cuando Gemini devuelve 503 ("modelo saturado, es
     * transitorio"). Un timeout real (ConnectionException) NO se reintenta:
     * ya usamos el margen completo de tiempo en el primer intento, y
     * reintentar ahí sí podría hacer que la request total supere el
     * max_execution_time de PHP y vuelva el error 500 que tuvimos antes.
     */
    private static function retryOnlyOnServiceUnavailable(): \Closure
    {
        return function (\Throwable $exception) {
            return $exception instanceof RequestException
                && $exception->response->status() === 503;
        };
    }

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
                ->connectTimeout(5)
                ->timeout(45)
                ->retry(3, fn (int $attempt) => $attempt * 1500, self::retryOnlyOnServiceUnavailable(), throw: false)
                ->post(config('services.deepseek.base_url'), [
                    'model' => config('services.deepseek.model', 'deepseek-chat'),
                    'messages' => array_merge(
                        [['role' => 'system', 'content' => $systemPrompt]],
                        $messages
                    ),
                    'temperature' => 0.6,
                    'max_tokens' => config('services.deepseek.max_tokens', 1000),
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

    /**
     * Igual que chat(), pero soporta "tools" (function calling) y devuelve
     * el mensaje completo del modelo (puede incluir tool_calls) en vez de
     * solo el texto. Se usa para el asistente del coach, que puede ejecutar
     * acciones (mandar notificaciones, crear rutinas, etc).
     *
     * @param  array<int, array>  $tools  Definiciones de funciones en formato OpenAI
     * @return array{content: ?string, tool_calls: array}|null
     */
    public function chatWithTools(string $systemPrompt, array $messages, array $tools = []): ?array
    {
        $apiKey = config('services.deepseek.key');

        if (! $apiKey) {
            Log::warning('DeepSeek: falta DEEPSEEK_API_KEY en .env');

            return null;
        }

        $payload = [
            'model' => config('services.deepseek.model', 'deepseek-chat'),
            'messages' => array_merge(
                [['role' => 'system', 'content' => $systemPrompt]],
                $messages
            ),
            'temperature' => 0.4,
            'max_tokens' => config('services.deepseek.max_tokens', 1000),
        ];

        if (! empty($tools)) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }

        try {
            $response = Http::withToken($apiKey)
                ->connectTimeout(5)
                ->timeout(45)
                ->retry(3, fn (int $attempt) => $attempt * 1500, self::retryOnlyOnServiceUnavailable(), throw: false)
                ->post(config('services.deepseek.base_url'), $payload);

            if ($response->failed()) {
                Log::error('DeepSeek API error', ['status' => $response->status(), 'body' => $response->body()]);

                return null;
            }

            $message = $response->json('choices.0.message');

            return [
                'content' => $message['content'] ?? null,
                'tool_calls' => $message['tool_calls'] ?? [],
            ];
        } catch (\Throwable $e) {
            Log::error('DeepSeek exception: '.$e->getMessage());

            return null;
        }
    }
}
