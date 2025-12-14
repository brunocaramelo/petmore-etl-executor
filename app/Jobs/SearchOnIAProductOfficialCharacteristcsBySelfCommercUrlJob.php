<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use App\Models\ProductSelfCommerceData;

use App\Actions\SearchOnIAProductOfficialCharacteristcsBySelfCommercUrlAction;

class SearchOnIAProductOfficialCharacteristcsBySelfCommercUrlJob implements ShouldQueue
{
    use Queueable;

    private $action;
    private $entityCentral;

    public function __construct(SearchOnIAProductOfficialCharacteristcsBySelfCommercUrlAction $action,
                                ProductSelfCommerceData $entityCentral
    ) {
        $this->action = $action;
        $this->entityCentral = $entityCentral;
    }

    public function handle(): void
    {
        $this->action->execute($this->entityCentral);
    }
}
