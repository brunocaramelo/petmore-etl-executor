<?php

namespace App\Consumers;

use Illuminate\Support\Facades\Http;

class MercadoLivreScrapperConsumer
{
    private $baseApiPath;
    private $tokenAuth;

    public function __construct(array $config)
    {
        $this->baseApiPath = $config['base_path'];
    }

    public function getProductByUrl(string $identifyParam)
    {
        $identify = base64_encode($identifyParam);

        $response = Http::retry(3, 10)
                    ->withToken($this->tokenAuth)
                    ->get($this->baseApiPath.'/products/by-url-encoded/'.$identify);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

}
