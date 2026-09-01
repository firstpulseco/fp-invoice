<?php

namespace Database\Factories;

use App\Models\BusinessSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessSetting>
 */
class BusinessSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_name' => fake()->company(),
            'street_address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'region' => fake()->stateAbbr(),
            'postal_code' => fake()->postcode(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'default_hourly_rate' => 100,
            'payment_terms' => 'Due within 30 days',
            'payment_method_name' => 'Bank transfer',
            'payment_details' => 'Payment instructions',
            'payment_methods' => [
                ['name' => 'Bank transfer', 'details' => 'Payment instructions'],
            ],
        ];
    }
}
