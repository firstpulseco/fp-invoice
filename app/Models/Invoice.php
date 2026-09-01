<?php

namespace App\Models;

use App\InvoiceStatus;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['client_id', 'number', 'invoice_date', 'due_date', 'status', 'total', 'client_company_name', 'client_contact_name', 'client_email', 'client_phone', 'client_street_address', 'client_address_line_2', 'client_city', 'client_region', 'client_postal_code', 'business_name', 'business_logo_path', 'business_street_address', 'business_city', 'business_region', 'business_postal_code', 'business_phone', 'business_email', 'payment_terms', 'payment_method_name', 'payment_details', 'payment_methods', 'invoice_footer'])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('position');
    }

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'status' => InvoiceStatus::class,
            'total' => 'decimal:2',
            'payment_methods' => 'array',
        ];
    }
}
