<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiKeyPool
{
    public static function getKeys(): array
    {
        $rawKeys = env('GEMINI_API_KEYS', env('GEMINI_API_KEY', ''));
        $keys = array_values(array_filter(array_map('trim', explode(',', (string) $rawKeys))));

        if (empty($keys)) {
            $fallback = env('GEMINI_API_KEY');
            if (! empty($fallback)) {
                $keys = [trim((string) $fallback)];
            }
        }

        return $keys;
    }

    public static function executeWithFailover(callable $callback)
    {
        $keys = self::getKeys();
        if (empty($keys)) {
            throw new \RuntimeException('No GEMINI_API_KEY configured. Please set GEMINI_API_KEY in environment.');
        }

        $count = count($keys);
        $startIndex = $count > 1 ? (Cache::increment('gemini_pool_idx', 1) % $count) : 0;

        for ($i = 0; $i < $count; $i++) {
            $key = $keys[($startIndex + $i) % $count];

            try {
                $result = $callback($key);
                if ($result !== null) {
                    return $result;
                }
            } catch (\Throwable $e) {
                Log::warning("GeminiKeyPool attempt {$i} failed: " . $e->getMessage());
                if ($i === $count - 1) {
                    throw $e;
                }
            }
        }

        return null;
    }

    /**
     * Call Google AI Studio Gemini REST API with fast model & fallback support.
     */
    public static function generateContent(
        array $contents,
        string $systemInstruction,
        bool $expectJson = true
    ): ?string {
        $primaryModel = env('GEMINI_MODEL', 'gemini-3.5-flash-lite');
        $models = array_values(array_unique([$primaryModel, 'gemini-3.1-flash-lite', 'gemini-flash-latest']));

        return self::executeWithFailover(function (string $key) use ($models, $contents, $systemInstruction, $expectJson) {
            $lastError = null;

            foreach ($models as $model) {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";

                $generationConfig = [
                    'temperature' => 0.2,
                    'maxOutputTokens' => 1400,
                ];
                if ($expectJson) {
                    $generationConfig['responseMimeType'] = 'application/json';
                }

                $body = [
                    'contents' => $contents,
                    'system_instruction' => [
                        'parts' => [
                            ['text' => $systemInstruction],
                        ],
                    ],
                    'generationConfig' => $generationConfig,
                ];

                $response = Http::timeout(18)->post($url, $body);

                if ($response->status() === 429) {
                    throw new \RuntimeException('Gemini rate limit (429) reached.');
                }

                if ($response->successful()) {
                    $data = $response->json();
                    $parts = $data['candidates'][0]['content']['parts'] ?? [];
                    $textParts = array_map(fn ($p) => $p['text'] ?? '', $parts);
                    return trim(implode('', $textParts));
                }

                $errMsg = $response->json('error.message') ?? substr($response->body(), 0, 200);
                $lastError = "HTTP {$response->status()} ({$model}): {$errMsg}";

                // Retry next fallback model only on transient 503/404 model unavailability
                if (! in_array($response->status(), [404, 503], true)) {
                    throw new \RuntimeException("Gemini API error: {$lastError}");
                }
            }

            throw new \RuntimeException("All Gemini models failed: {$lastError}");
        });
    }
}
