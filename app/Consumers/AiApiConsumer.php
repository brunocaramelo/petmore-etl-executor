<?php

namespace App\Consumers;

use Illuminate\Support\Facades\Http;

class AiApiConsumer
{
    private $baseApiPath;
    private $apiKey;

    public function __construct(array $config)
    {
        $this->baseApiPath = $config['base_path'];
        $this->apiKey = $config['api_key'];
    }

    public function sendContentToModelAi(array $dataToSend)
    {
        $response = Http::retry(3, 10)
                    ->timeout(8999)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'X-goog-api-key' => $this->apiKey
                    ])
                    ->post($this->baseApiPath,
                    $dataToSend
                    );

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

}
