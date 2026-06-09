<?php

namespace App\Http\Controllers\Concerns;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

trait AppliesLastThreeMonthsFilter
{
    protected function applyLastThreeMonthsFilter(Builder $query, string $column = 'created_at'): Builder
    {
        return $query->where($column, '>=', $this->lastThreeMonthsFrom());
    }

    protected function lastThreeMonthsFrom(): Carbon
    {
        return Carbon::today()->subMonths(3)->startOfDay();
    }

    /**
     * @return array{date_from: string, date_to: string}
     */
    protected function lastThreeMonthsDateRange(): array
    {
        return [
            'date_from' => $this->lastThreeMonthsFrom()->toDateString(),
            'date_to' => Carbon::today()->toDateString(),
        ];
    }
}
