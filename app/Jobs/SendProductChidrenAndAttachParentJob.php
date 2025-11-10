<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use Illuminate\Support\Facades\Storage;

class SendProductChidrenAndAttachParentJob implements ShouldQueue
{
    use Queueable;

    private $childrenProduct;
    private $parentProduct;
    private $consumer;
    private $configsParams;

    public function __construct(
        $childrenProduct,
        $parentProduct,
        $consumer,
        $configsParams
    ) {
        $this->childrenProduct = $childrenProduct;
        $this->parentProduct = $parentProduct;
        $this->consumer = $consumer;
        $this->configsParams = $configsParams;
    }


    public function handle(): void
    {


    }
}
