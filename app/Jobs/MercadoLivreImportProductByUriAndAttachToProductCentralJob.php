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
    public array $backoff = [60, 100, 300];

    public function __construct(ProductCentral $productCentral)
    {
        $this->productCentral = $productCentral;
    }

    public function timeout(): int
    {
        return 1500;
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
