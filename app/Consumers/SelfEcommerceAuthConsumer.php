<?php

namespace App\Consumers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SelfEcommerceAuthConsumer
{
    private $baseUrl;
    private $currentToken;
    private $authPassword;
    private $authUsername;

    public function __construct(string $username, string  $password)
    {
        $this->baseUrl = config('custom-services.apis.self_ecommerce.base_url');
        $this->authUsername = $username;
        $this->authPassword = $password;

    }

    public function getCurrentToken()
    {
        return $this->currentToken;
    }

    public function byStoredToken()
    {
        return cache()->remember('SelfCommerceCurrentToken', 3600, function () {
            return $this->generateToken();
        });
    }

    public function generateToken()
    {
        Log::info('Tentando token de acesso do Self Ecommerce.');

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($this->baseUrl.'/integration/admin/token', [
                'username' => $this->authUsername,
                'password' => $this->authPassword
            ])->throw();

            $this->currentToken = str_replace('"','', $response->body());

            return $this->currentToken;

        } catch (\Exception $e) {
            Log::error('Erro ao refrescar token de acesso do Self ecommerce.', ['exception' => $e->getMessage()]);
            return null;
        }
    }
}
