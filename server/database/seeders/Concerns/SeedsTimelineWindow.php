<?php

namespace Database\Seeders\Concerns;

use Carbon\Carbon;

trait SeedsTimelineWindow
{
    protected function seedWindowStart(): Carbon
    {
        return Carbon::create(2023, 1, 1, 0, 0, 0);
    }

    protected function seedWindowEnd(): Carbon
    {
        return Carbon::create(2026, 3, 31, 23, 59, 59);
    }

    protected function pickWeightedDate(int $excludeTrailingDays = 0): Carbon
    {
        $rangeStart = $this->seedWindowStart()->copy();
        $rangeEnd = $this->seedWindowEnd()->copy()->subDays($excludeTrailingDays);

        if ($rangeEnd->lt($rangeStart)) {
            $rangeEnd = $rangeStart->copy();
        }

        $months = [];
        $cursor = $rangeStart->copy()->startOfMonth();
        $index = 0;

        while ($cursor->lte($rangeEnd)) {
            $monthStart = $cursor->copy()->startOfMonth();
            $monthEnd = $cursor->copy()->endOfMonth();

            if ($monthStart->lt($rangeStart)) {
                $monthStart = $rangeStart->copy();
            }

            if ($monthEnd->gt($rangeEnd)) {
                $monthEnd = $rangeEnd->copy();
            }

            $months[] = [
                'start' => $monthStart,
                'end' => $monthEnd,
                'weight' => 10 + ($index * 3),
            ];

            $cursor->addMonthNoOverflow()->startOfMonth();
            $index++;
        }

        $selected = $months[$this->pickWeightedIndex(array_column($months, 'weight'))];

        $day = random_int($selected['start']->day, $selected['end']->day);

        return $selected['start']->copy()
            ->day($day)
            ->setTime(random_int(9, 18), random_int(0, 59), random_int(0, 59));
    }

    protected function growthFactorForDate(Carbon $date, float $startFactor = 0.90, float $endFactor = 1.20): float
    {
        $start = $this->seedWindowStart()->copy()->startOfMonth();
        $end = $this->seedWindowEnd()->copy()->startOfMonth();
        $totalMonths = max($start->diffInMonths($end), 1);
        $position = min(max($start->diffInMonths($date->copy()->startOfMonth()) / $totalMonths, 0), 1);

        return $startFactor + (($endFactor - $startFactor) * $position);
    }

    protected function clampToSeedWindow(Carbon $date): Carbon
    {
        if ($date->lt($this->seedWindowStart())) {
            return $this->seedWindowStart()->copy();
        }

        if ($date->gt($this->seedWindowEnd())) {
            return $this->seedWindowEnd()->copy();
        }

        return $date;
    }

    protected function pickWeightedIndex(array $weights): int
    {
        $total = array_sum($weights);
        $roll = random_int(1, max($total, 1));

        foreach ($weights as $index => $weight) {
            $roll -= $weight;

            if ($roll <= 0) {
                return $index;
            }
        }

        return array_key_last($weights) ?? 0;
    }
}
