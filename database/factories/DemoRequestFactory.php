<?php

namespace Database\Factories;

use App\Models\DemoRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DemoRequest> */
class DemoRequestFactory extends Factory
{
    protected $model = DemoRequest::class;

    public function definition(): array
    {
        return [
            'name' => 'سارا محمدی',
            'salon_name' => 'سالن رزگلد',
            'phone' => '0912'.fake()->unique()->numerify('#######'),
            'salon_type' => 'hair',
            'ip' => '127.0.0.1',
        ];
    }
}
