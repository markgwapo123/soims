<?php

namespace App\Services;

use App\Models\InventoryLot;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class ExpirationService
{
    public function lots(?string $status = null): Collection
    {
        $today = CarbonImmutable::today();
        $warningDate = $today->addDays((int) config('soims.expiration_warning_days', 30));

        return InventoryLot::with('item')
            ->where('is_active', true)
            ->whereNotNull('expiration_date')
            ->when($status, fn ($query) => $query->where(function ($query) use ($status, $today, $warningDate) {
                match ($status) {
                    'expired' => $query->whereDate('expiration_date', '<', $today),
                    'near_expiration' => $query->whereDate('expiration_date', '>=', $today)->whereDate('expiration_date', '<=', $warningDate),
                    'valid' => $query->whereDate('expiration_date', '>', $warningDate),
                    default => $query->whereRaw('1 = 0'),
                };
            }))
            ->orderBy('expiration_date')
            ->get();
    }

    public function status(InventoryLot $lot): string
    {
        if (! $lot->expiration_date) {
            return 'not_applicable';
        }

        $today = CarbonImmutable::today();
        $expirationDate = CarbonImmutable::parse($lot->expiration_date);
        $warningDate = $today->addDays((int) config('soims.expiration_warning_days', 30));

        return match (true) {
            $expirationDate->isBefore($today) => 'expired',
            $expirationDate->lessThanOrEqualTo($warningDate) => 'near_expiration',
            default => 'valid',
        };
    }
}
