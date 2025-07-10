<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use App\Actions\SendMappedProductToErpAction;

use App\Models\ProductCentral;
class SendMappedProductToErpJob implements ShouldQueue
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
        app(SendMappedProductToErpAction::class)->execute($this->productCentral);

    }
}
