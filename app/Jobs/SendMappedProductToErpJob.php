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
    private $mappedProductArr;

    public function __construct(
        ProductCentral $productCentral,
        array $mappedProductArr
    ) {
        $this->productCentral = $productCentral;
        $this->mappedProductArr = $mappedProductArr;
    }


    public function handle(): void
    {
        app(SendMappedProductToErpAction::class)->execute($this->productCentral,
                                                                                    $this->mappedProductArr);

    }
}
