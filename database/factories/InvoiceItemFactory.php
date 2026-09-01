<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\PricingType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'position' => 0,
            'description' => fake()->sentence(3),
            'pricing_type' => PricingType::Fixed,
            'amount' => fake()->randomFloat(2, 50, 2000),
        ];
    }
}
