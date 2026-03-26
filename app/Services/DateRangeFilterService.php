<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DateRangeFilterService
{
    /**
     * Apply date range filter to a query
     *
     * @param Builder $query
     * @param Request $request
     * @param string $dateColumn - The date column to filter on (e.g., 'date', 'created_at')
     * @return Builder
     */
    public function applyDateFilter(Builder $query, Request $request, string $dateColumn = 'created_at'): Builder
    {
        $dateFilter = $request->get('date_filter');
        $dateColumn = $request->get('date_column', $dateColumn);
        $customStartDate = $request->get('custom_start_date');
        $customEndDate = $request->get('custom_end_date');

        if (!$dateFilter && !$customStartDate) {
            return $query;
        }

        switch ($dateFilter) {
            case 'today':
                $query->whereDate($dateColumn, Carbon::today());
                break;

            case 'yesterday':
                $query->whereDate($dateColumn, Carbon::yesterday());
                break;

            case 'this_week':
                $query->whereBetween($dateColumn, [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek()
                ]);
                break;

            case 'last_week':
                $query->whereBetween($dateColumn, [
                    Carbon::now()->subWeek()->startOfWeek(),
                    Carbon::now()->subWeek()->endOfWeek()
                ]);
                break;

            case 'this_month':
                $query->whereMonth($dateColumn, Carbon::now()->month)
                      ->whereYear($dateColumn, Carbon::now()->year);
                break;

            case 'last_month':
                $query->whereMonth($dateColumn, Carbon::now()->subMonth()->month)
                      ->whereYear($dateColumn, Carbon::now()->subMonth()->year);
                break;

            case 'this_year':
                $query->whereYear($dateColumn, Carbon::now()->year);
                break;

            case 'last_year':
                $query->whereYear($dateColumn, Carbon::now()->subYear()->year);
                break;

            case 'custom':
                if ($customStartDate && $customEndDate) {
                    $query->whereBetween($dateColumn, [
                        Carbon::parse($customStartDate)->startOfDay(),
                        Carbon::parse($customEndDate)->endOfDay()
                    ]);
                } elseif ($customStartDate) {
                    $query->whereDate($dateColumn, '>=', Carbon::parse($customStartDate));
                } elseif ($customEndDate) {
                    $query->whereDate($dateColumn, '<=', Carbon::parse($customEndDate));
                }
                break;
        }

        return $query;
    }

    /**
     * Apply user/agent filter to a query
     *
     * @param Builder $query
     * @param Request $request
     * @param string $userColumn - The user column to filter on (e.g., 'created_by')
     * @return Builder
     */
    public function applyUserFilter(Builder $query, Request $request, string $userColumn = 'created_by'): Builder
    {
        $createdBy = $request->get('created_by');

        if ($createdBy) {
            if (is_array($createdBy)) {
                $query->whereIn($userColumn, $createdBy);
            } else {
                $query->where($userColumn, $createdBy);
            }
        }

        return $query;
    }

    /**
     * Apply status filter to a query
     *
     * @param Builder $query
     * @param Request $request
     * @param string $statusColumn - The status column to filter on
     * @return Builder
     */
    public function applyStatusFilter(Builder $query, Request $request, string $statusColumn = 'status'): Builder
    {
        $status = $request->get('status');

        if ($status) {
            if (is_array($status)) {
                $query->whereIn($statusColumn, $status);
            } else {
                $query->where($statusColumn, $status);
            }
        }

        return $query;
    }

    /**
     * Apply search filter to a query
     *
     * @param Builder $query
     * @param Request $request
     * @param array $searchColumns - Columns to search in
     * @return Builder
     */
    public function applySearchFilter(Builder $query, Request $request, array $searchColumns): Builder
    {
        $search = $request->get('search');

        if ($search && !empty($searchColumns)) {
            $query->where(function ($q) use ($search, $searchColumns) {
                foreach ($searchColumns as $column) {
                    $q->orWhere($column, 'like', '%' . $search . '%');
                }
            });
        }

        return $query;
    }

    /**
     * Apply all common filters to a query
     *
     * @param Builder $query
     * @param Request $request
     * @param array $options
     * @return Builder
     */
    public function applyFilters(Builder $query, Request $request, array $options = []): Builder
    {
        $dateColumn = $options['date_column'] ?? 'created_at';
        $userColumn = $options['user_column'] ?? 'created_by';
        $statusColumn = $options['status_column'] ?? 'status';
        $searchColumns = $options['search_columns'] ?? [];

        // Apply date filter
        $query = $this->applyDateFilter($query, $request, $dateColumn);

        // Apply user filter
        $query = $this->applyUserFilter($query, $request, $userColumn);

        // Apply status filter
        $query = $this->applyStatusFilter($query, $request, $statusColumn);

        // Apply search filter
        if (!empty($searchColumns)) {
            $query = $this->applySearchFilter($query, $request, $searchColumns);
        }

        return $query;
    }

    /**
     * Get available date filter options
     *
     * @return array
     */
    public static function getDateFilterOptions(): array
    {
        return [
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'this_week' => 'This Week',
            'last_week' => 'Last Week',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'this_year' => 'This Year',
            'last_year' => 'Last Year',
            'custom' => 'Custom Range'
        ];
    }

    /**
     * Get available date columns for filtering
     *
     * @param string $module
     * @return array
     */
    public static function getDateColumns(string $module): array
    {
        $columns = [
            'appointments' => [
                'date' => 'Appointment Date',
                'created_at' => 'Created Date',
                'updated_at' => 'Updated Date'
            ],
            'followup' => [
                'created_at' => 'Created Date',
                'updated_at' => 'Updated Date'
            ],
            'quality' => [
                'created_at' => 'Created Date',
                'updated_at' => 'Updated Date'
            ]
        ];

        return $columns[$module] ?? $columns['followup'];
    }
}
