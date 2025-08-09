<?php

namespace App\Resources\Search;
class ProductSpecificationSearcherWordSearch
{

    public function searchValueByProximity($searchTerm, array $listSpecificationComplete)
    {
        $listSpecification = [];

        foreach ($listSpecificationComplete as $itemSpecification) {
            $listSpecification[] = $itemSpecification['rows'];
        }

        $filteredRow = collect($listSpecification)
                    ->filter(fn ($item) => \Str::contains(strtolower($item['label']),
                                                                                            strtolower($searchTerm))
                            )
                    ->sortBy(function ($item) use ($searchTerm) {
                        return strpos(strtolower($item['label']),
                                strtolower($searchTerm)
                            );
                    });

        return $filteredRow->first();
    }

}
