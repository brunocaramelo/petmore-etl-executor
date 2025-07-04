<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use App\Actions\AttachMercadoLivreProductToProductCentralAction;

use App\Models\ProductCentral;

class MercadoLivreImportProductByUriAndAttachToProductCentralJob implements ShouldQueue
{
    use Queueable;

    private $productCentral;

    public function __construct(ProductCentral $productCentral)
    {
        $this->productCentral = $productCentral;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        (new AttachMercadoLivreProductToProductCentralAction())
                        ->execute($this->productCentral);
    }
}
