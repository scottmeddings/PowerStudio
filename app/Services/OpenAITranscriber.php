<?php
// app/Services/OpenAITranscriber.php
namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class OpenAITranscriber
{
    public function transcribe(string $pathToAudio, array $params = []): array
    {
        $apiKey = config('services.openai.key');
        if (!$apiKey) {
            throw new \RuntimeException('Missing OPENAI_API_KEY');
        }

        // sensible defaults (override via config/services.php or env)
        $timeout         = (int) config('services.openai.timeout', 600); // 10 min
        $connectTimeout  = (int) config('services.openai.connect_timeout', 30);

        $client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'timeout' => $timeout,
            'connect_timeout' => $connectTimeout,
            'http_errors' => false,     // let us handle non-2xx
            'headers' => [
                'Authorization' => "Bearer {$apiKey}",
                'Accept'        => 'application/json',
                'Expect'        => '',   // avoid 100-continue stalls
            ],
            'curl' => [
                CURLOPT_TCP_KEEPALIVE   => 1,
                CURLOPT_LOW_SPEED_LIMIT => 1024,  // 1 KB/s
                CURLOPT_LOW_SPEED_TIME  => 60,    // for 60s
            ],
        ]);

        // IMPORTANT: open as stream
        $fp = fopen($pathToAudio, 'rb');

        $multipart = [
            ['name' => 'file', 'contents' => $fp, 'filename' => basename($pathToAudio)],
            ['name' => 'model', 'contents' => $params['model'] ?? 'whisper-1'],
            ['name' => 'response_format', 'contents' => 'json'],
            ['name' => 'temperature', 'contents' => '0'],
            // Optionally: 'prompt', 'language', etc
        ];

        $res = $client->post('audio/transcriptions', ['multipart' => $multipart]);

        fclose($fp);

        if ($res->getStatusCode() >= 400) {
            $body = (string) $res->getBody();
            Log::warning('OpenAI transcription error', ['status' => $res->getStatusCode(), 'body' => $body]);
            throw new \RuntimeException("OpenAI error {$res->getStatusCode()}: {$body}");
        }

        return json_decode((string) $res->getBody(), true) ?: [];
    }
}
