<?php

namespace App\Consumers;

use Illuminate\Support\Facades\Http;

class BlingErpConsumer
{
    private $baseApiPath;
    private $tokenAuth;
    private $config;
    private $authInstance;
    public function __construct($authInstance, array $config)
    {
        $this->baseApiPath = $config['base_path'];
        $this->config = $config;
        $this->authInstance = $authInstance;

        if ($config['auto_login'] ?? false) {
            $this->sendAuthApi();
        }

    }

    public function createProduct(array $params)
    {
        $response = Http::retry(3, 10)
                    ->withToken($this->tokenAuth)
                    ->post($this->baseApiPath.'/produtos');

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    public function getProduct($identify)
    {
        $response = Http::retry(3, 10)
                    ->withToken($this->tokenAuth)
                    ->get($this->baseApiPath.'/produtos/'.$identify);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    public function updateProduct($identify, array $params)
    {
        $response = Http::retry(3, 10)
                    ->withToken($this->tokenAuth)
                    ->put($this->baseApiPath.'/produtos/'.$identify);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    public function sendAuthApi()
    {
        $responseApi = $this->authInstance->byStoredRefreshToken();

        $this->tokenAuth = $responseApi['access_token'];
    }

}
