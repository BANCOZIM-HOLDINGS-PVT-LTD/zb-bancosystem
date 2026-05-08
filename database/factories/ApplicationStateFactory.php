<?php

namespace Database\Factories;

use App\Models\ApplicationState;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ApplicationState>
 */
class ApplicationStateFactory extends Factory
{
    protected $model = ApplicationState::class;

    public function definition(): array
    {
        $sessionId = 'test_' . Str::random(16);

        return [
            'session_id' => $sessionId,
            'channel' => 'web',
            'user_identifier' => $this->faker->safeEmail(),
            'current_step' => 'language_selection',
            'form_data' => [],
            'metadata' => [],
            'expires_at' => now()->addDay(),
            'last_activity' => now(),
            'payment_type' => 'credit',
        ];
    }
}
