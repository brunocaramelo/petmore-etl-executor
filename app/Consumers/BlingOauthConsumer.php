<?php

namespace App\Consumers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;

class BlingOauthConsumer
{
    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $authorizationUrl;
    private $tokenUrl;
    private $refreshToken;

    public function __construct()
    {
        $this->authorizationUrl = config('services.bling_erp.client_id');
        $this->tokenUrl = config('services.bling_erp.client_id');
        $this->clientId = config('services.bling_erp.client_id');
        $this->clientSecret = config('services.bling_erp.client_secret');
        $this->redirectUri = config('services.bling_erp.redirect_uri');
        $this->refreshToken = config('custom-services.apis.bling_erp.refresh_token');

    }

    public function redirectToBling()
    {
        $params = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            // 'scope' => 'read:produtos write:produtos' // Exemplo de escopo, ajuste conforme necessário
        ];

        $fullAuthUrl = $this->authorizationUrl . '?' . http_build_query($params);

        Log::info('Redirecionando para o Bling para autorização.', ['url' => $fullAuthUrl]);

        return Redirect::away($fullAuthUrl);
    }

    public function handleBlingCallback(string $authorizationCode)
    {
        Log::info('Recebido código de autorização do Bling.',
        ['code' => $authorizationCode]
        );

        try {

            $response = Http::asForm()->post($this->tokenUrl, [
                'grant_type' => 'authorization_code',
                'code' => $authorizationCode,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri' => $this->redirectUri,
            ]);

            $data = $response->json();

            if (isset($data['access_token'])) {
                Log::info('Token de acesso do Bling obtido com sucesso.', [
                    'access_token_mask' => substr($data['access_token'], 0, 10) . '...', // Mascara o token no log
                    'expires_in' => $data['expires_in'],
                ]);
                return $data;
            }

            Log::error('Falha ao obter token de acesso do Bling.', [
                'status' => $response->status(),
                'response_body' => $response->body(),
                'error_details' => $response->json(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Erro ao conectar com a API do Bling para obter token.',
                    ['exception' => $e->getMessage()
            ]);

            return null;
        }
    }

    public function byStoredRefreshToken()
    {
        return $this->refreshToken($this->refreshToken);
    }

    public function refreshToken(string $refreshToken)
    {
        Log::info('Tentando refrescar token de acesso do Bling.');

        try {

            $response = Http::asForm()->post($this->tokenUrl, [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

            $data = $response->json();

            if (isset($data['access_token'])) {
                Log::info('Token de acesso do Bling refrescado com sucesso.');
                return $data;
            }

            Log::error('Falha ao refrescar token de acesso do Bling.', [
                'status' => $response->status(),
                'response_body' => $response->body(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Erro ao refrescar token de acesso do Bling.', ['exception' => $e->getMessage()]);
            return null;
        }
    }
}
