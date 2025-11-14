<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use App\Actions\SendMappedProductToSelfEcommerceAction;

use App\Models\ProductCentral;

class SendMappedProductToSelfEcommerceJob implements ShouldQueue
{
    use Queueable;

    // public int $tries = 3;

    // public int $maxExceptions = 3;

    // public int $backoff = 60;

    // public int $timeout = 120;

    private $productCentral;
    public function __construct(
        ProductCentral $productCentral,
    ) {
        $this->productCentral = $productCentral;
    }


    public function handle(): void
    {
        app(SendMappedProductToSelfEcommerceAction::class)
        ->execute($this->productCentral);

    }
}
