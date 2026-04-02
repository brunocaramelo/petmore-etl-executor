<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use Illuminate\Support\Facades\Storage;

use App\UseCases\CreateProductChildSelfEcommerceUseCase;

use App\Models\{ProductRewrited,
                ProductDto
                };

class SendProductChidrenAndAttachParentJob implements ShouldQueue
{
    use Queueable;

    private $currentProduct;
    private $parentProduct;
    private $consumer;
    private $configsParams;

    // public int $tries = 3;
    // public int $maxExceptions = 3;
    // public int $backoff = 60;
    // public int $timeout = 120;

    public function __construct(
        ProductDto $currentProduct,
        ProductRewrited $parentProduct,
        $consumer,
        $configsParams
    ) {
        $this->currentProduct = $currentProduct;
        $this->parentProduct = $parentProduct;
        $this->consumer = $consumer;
        $this->configsParams = $configsParams;
    }


    public function handle(): void
    {
        (new CreateProductChildSelfEcommerceUseCase(
                $this->consumer,
                $this->currentProduct,
                $this->parentProduct,
                $this->configsParams
        ))->handle();
    }
}
