<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

trait ReordersCourseStructure
{
    protected function moveSortOrder(Relation|Builder $scope, Model $item, string $direction): bool
    {
        $direction = $direction === 'down' ? 'down' : 'up';

        $neighborQuery = (clone $this->reorderQuery($scope))->where('id', '!=', $item->id);

        if ($direction === 'up') {
            $neighbor = $neighborQuery
                ->where('sort_order', '<', $item->sort_order)
                ->orderByDesc('sort_order')
                ->first();
        } else {
            $neighbor = $neighborQuery
                ->where('sort_order', '>', $item->sort_order)
                ->orderBy('sort_order')
                ->first();
        }

        if (! $neighbor) {
            return false;
        }

        $currentOrder = $item->sort_order;
        $item->update(['sort_order' => $neighbor->sort_order]);
        $neighbor->update(['sort_order' => $currentOrder]);

        return true;
    }

    protected function reorderQuery(Relation|Builder $scope): Builder
    {
        return $scope instanceof Relation ? $scope->getQuery() : $scope;
    }
}
