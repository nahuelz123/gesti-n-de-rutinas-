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
                ->connectTimeout(15)
                ->timeout(60)
                ->retry(2, 1500, throw: false)
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
                ->connectTimeout(15)
                ->timeout(60)
                ->retry(2, 1500, throw: false)
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
