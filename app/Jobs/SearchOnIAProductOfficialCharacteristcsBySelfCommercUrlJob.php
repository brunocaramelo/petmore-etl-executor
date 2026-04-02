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
    private $entity;

    public function __construct(SearchOnIAProductOfficialCharacteristcsBySelfCommercUrlAction $action,
                                ProductSelfCommerceData $entity
    ) {

        $this->action = $action;
        $this->entity = $entity;
    }

    public function handle(): void
    {
        $this->action->execute($this->entity,[
            'search_and_storage_stage' => true,
            'send_to_self_ecommerce' => true,
        ]);
    }
}
