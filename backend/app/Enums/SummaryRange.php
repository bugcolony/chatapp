<?php

namespace App\Enums;

use Carbon\CarbonImmutable;

enum SummaryRange: string
{
    case LAST_FIFTY = 'last_50';
    case TODAY = 'today';
    case YESTERDAY = 'yesterday';

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}|null
     */
    public function window(?string $timezone): ?array
    {
        $now = CarbonImmutable::now($timezone ?? 'UTC');

        return match ($this) {
            self::LAST_FIFTY => null,
            self::TODAY => [$now->startOfDay(), $now],
            self::YESTERDAY => [$now->subDay()->startOfDay(), $now->startOfDay()],
        };
    }

    public function limit(): int
    {
        return $this === self::LAST_FIFTY ? 50 : 200;
    }
}
