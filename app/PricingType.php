<?php

namespace App;

enum PricingType: string
{
    case Hourly = 'hourly';
    case Fixed = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::Hourly => 'Hourly',
            self::Fixed => 'Fixed price',
        };
    }
}
