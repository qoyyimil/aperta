<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PackageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'service' => 'Wisuda',
            'name' => 'Personal Session 2 (2 jam)',
            'duration_minutes' => 120,
            'price' => 750_000,
            'edit_quota' => 20,
            'extra_photo_price' => 25_000,
        ];
    }
}
