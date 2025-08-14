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
                    ->timeout(8999)
                    ->post($this->baseApiPath.'/produtos', $params)
                    ->throw();

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    public function createCustomField(array $params)
    {
        $response = Http::retry(3, 10)
                    ->withToken($this->tokenAuth)
                    ->timeout(8999)
                    ->post($this->baseApiPath.'/campos-customizados', $params)
                    ->throw();

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    public function updateCustomField($id, array $params)
    {
        $response = Http::retry(3, 10)
                    ->withToken($this->tokenAuth)
                    ->timeout(8999)
                    ->put($this->baseApiPath.'/campos-customizados/'.$id, $params)
                    ->throw();

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    public function getProduct($identify)
    {
        $response = Http::retry(3, 10)
                    ->withToken($this->tokenAuth)
                    ->timeout(8999)
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
                    ->timeout(8999)
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
