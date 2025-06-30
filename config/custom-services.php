<?php

return [
    'apis' => [
        'bling_erp' => [
            'base_path' => env('BLING_ERP_CLIENT_ID'),
            'client_id' => env('BLING_ERP_CLIENT_ID'),
            'client_secret' => env('BLING_ERP_CLIENT_SECRET'),
            'redirect_uri' => env('BLING_ERP_REDIRECT_URI'),
            'access_token' => env('BLING_ERP_ACCESS_TOKEN'),
            'refresh_token' => env('BLING_ERP_REFRESH_TOKEN'),
        ]
    ]

];