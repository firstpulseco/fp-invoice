<?php

namespace App\Models;

use Database\Factories\BusinessSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['business_name', 'logo_path', 'street_address', 'city', 'region', 'postal_code', 'phone', 'email', 'default_hourly_rate', 'payment_terms', 'payment_method_name', 'payment_details', 'payment_methods', 'invoice_footer'])]
class BusinessSetting extends Model
{
    /** @use HasFactory<BusinessSettingFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'default_hourly_rate' => 'decimal:2',
            'payment_methods' => 'array',
        ];
    }
}
