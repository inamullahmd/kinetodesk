<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;

trait AppliesMultiColumnSorting
{
    /**
     * Supports:
     * ?sort=displayName:asc,totalRevenue:desc
     *
     * $allowed examples:
     *
     * [
     *   'displayName' => 'customers.business_name',
     *   'totalRevenue' => 'total_revenue',
     *   'location' => fn ($query, $direction) => $query->orderBy('customers.state', $direction),
     * ]
     */
    protected function applySorts(
        Builder|QueryBuilder $query,
        Request $request,
        array $allowed,
        array $default = []
    ): Builder|QueryBuilder {
        $sorts = $this->parseSorts($request, $allowed, $default);

        foreach ($sorts as $sort) {
            $field = $sort['field'];
            $direction = $sort['direction'];

            $resolver = $allowed[$field] ?? null;

            if (is_callable($resolver)) {
                $resolver($query, $direction);
                continue;
            }

            if (is_string($resolver)) {
                $query->orderBy($resolver, $direction);
            }
        }

        return $query;
    }

    protected function parseSorts(
        Request $request,
        array $allowed,
        array $default = []
    ): array {
        $raw = trim((string) $request->query('sort', ''));

        if ($raw === '') {
            return $default;
        }

        $parsed = [];

        foreach (explode(',', $raw) as $part) {
            $part = trim($part);

            if ($part === '') {
                continue;
            }

            [$field, $direction] = array_pad(explode(':', $part, 2), 2, 'asc');

            $field = trim($field);
            $direction = strtolower(trim($direction));

            if (! array_key_exists($field, $allowed)) {
                continue;
            }

            if (! in_array($direction, ['asc', 'desc'], true)) {
                $direction = 'asc';
            }

            $parsed[$field] = $direction;
        }

        if (count($parsed) === 0) {
            return $default;
        }

        return collect($parsed)
            ->map(fn (string $direction, string $field) => [
                'field' => $field,
                'direction' => $direction,
            ])
            ->values()
            ->all();
    }

    protected function orderByNullableDate(
        Builder|QueryBuilder $query,
        string $column,
        string $direction
    ): void {
        $query
            ->orderByRaw("CASE WHEN {$column} IS NULL THEN 1 ELSE 0 END ASC")
            ->orderBy($column, $direction);
    }

    protected function orderByCustomerDisplayName(
        Builder|QueryBuilder $query,
        string $direction
    ): void {
        $query->orderByRaw(
            "COALESCE(
                NULLIF(customers.business_name, ''),
                NULLIF(TRIM(CONCAT(COALESCE(customers.first_name, ''), ' ', COALESCE(customers.last_name, ''))), ''),
                customers.email,
                customers.phone,
                customers.id
            ) {$direction}"
        );
    }

    protected function orderByCustomerContactName(
        Builder|QueryBuilder $query,
        string $direction
    ): void {
        $query->orderByRaw(
            "NULLIF(TRIM(CONCAT(COALESCE(customers.first_name, ''), ' ', COALESCE(customers.last_name, ''))), '') {$direction}"
        );
    }

    protected function orderByCustomerLocation(
        Builder|QueryBuilder $query,
        string $direction
    ): void {
        $query
            ->orderBy('customers.state', $direction)
            ->orderBy('customers.city', $direction);
    }

    protected function orderByProductStatus(
    Builder|QueryBuilder $query,
    string $direction,
    string $stockExpression = 'stock_qty',
    string|int|float $lowStockThreshold = 5
): void {
    $threshold = is_numeric($lowStockThreshold)
        ? (string) $lowStockThreshold
        : $lowStockThreshold;

    $query->orderByRaw("
        CASE
            WHEN COALESCE({$stockExpression}, 0) <= 0 THEN 0
            WHEN COALESCE({$stockExpression}, 0) <= {$threshold} THEN 1
            ELSE 2
        END {$direction}
    ");
}
}