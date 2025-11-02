<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use App\Actions\SendMappedProductToSelfEcommerceAction;

use App\Models\ProductCentral;

class SendMappedProductToSelfEcommerceJob implements ShouldQueue
{
    use Queueable;

    private $productCentral;
    public function __construct(
        ProductCentral $productCentral,
    ) {
        $this->productCentral = $productCentral;
    }


    public function handle(): void
    {
        app(SendMappedProductToSelfEcommerceAction::class)->execute($this->productCentral);

    }
}
