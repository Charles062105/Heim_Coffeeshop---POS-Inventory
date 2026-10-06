<?php

namespace App\Support;

use Carbon\CarbonImmutable;

class BusinessDateRange
{
    public static function startUtc(string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date, config('app.business_timezone', 'Asia/Manila'))
            ->startOfDay()
            ->utc();
    }

    public static function endExclusiveUtc(string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date, config('app.business_timezone', 'Asia/Manila'))
            ->startOfDay()
            ->addDay()
            ->utc();
    }
}
