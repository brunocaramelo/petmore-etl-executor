<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use App\Models\ProductCentral;

use App\Actions\CreateRewritedProductAction;

class CreateContentWithoutCopyrightJob implements ShouldQueue
{
    use Queueable;

    private $action;
    private $entityCentral;
    public array $backoff = [60, 100, 300];

    public function __construct(CreateRewritedProductAction $action, ProductCentral $entityCentral)
    {
        $this->action = $action;
        $this->entityCentral = $entityCentral;
    }

    public function timeout(): int
    {
        return 5500;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->action->execute($this->entityCentral);
    }
}
