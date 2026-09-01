<?php

namespace App\Models;

use App\PricingType;
use Database\Factories\InvoiceItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['invoice_id', 'position', 'description', 'details', 'pricing_type', 'hourly_rate', 'hours', 'amount'])]
class InvoiceItem extends Model
{
    /** @use HasFactory<InvoiceItemFactory> */
    use HasFactory;

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    protected function casts(): array
    {
        return [
            'pricing_type' => PricingType::class,
            'hourly_rate' => 'decimal:2',
            'hours' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }
}
