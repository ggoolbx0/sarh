<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $gender = fake()->randomElement(['male', 'female']);

        return [
            // Identity
            'employee_id'      => 'SARH-' . now()->format('y') . '-' . str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'name_ar'          => fake('ar_SA')->name($gender),
            'name_en'          => fake()->name($gender),
            'email'            => fake()->unique()->safeEmail(),
            'email_verified_at'=> now(),
            'password'         => static::$password ??= Hash::make('password'),
            'phone'            => fake()->phoneNumber(),
            'national_id'      => fake()->unique()->numerify('##########'),
            'gender'           => $gender,
            'date_of_birth'    => fake()->dateTimeBetween('-50 years', '-20 years'),

            // Organizational
            'job_title_ar'     => fake('ar_SA')->jobTitle(),
            'job_title_en'     => fake()->jobTitle(),
            'hire_date'        => fake()->dateTimeBetween('-5 years', 'now'),
            'employment_type'  => 'full_time',
            'status'           => 'active',

            // Financial
            'basic_salary'           => fake()->randomElement([5000, 6000, 7000, 8000, 9000, 10000, 12000, 15000]),
            'housing_allowance'      => fake()->randomElement([1500, 2000, 2500, 3000]),
            'transport_allowance'    => fake()->randomElement([300, 500, 700]),
            'other_allowances'       => fake()->randomElement([0, 200, 500]),
            'working_days_per_month' => 22,
            'working_hours_per_day'  => 8,

            // Gamification
            'total_points'   => fake()->numberBetween(0, 500),
            'current_streak' => fake()->numberBetween(0, 30),
            'longest_streak' => fake()->numberBetween(0, 60),

            // Preferences
            'locale'   => 'ar',
            'timezone' => 'Asia/Riyadh',

            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create a super admin user.
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'basic_salary' => 25000,
        ])->afterCreating(function (User $user) {
            $user->promoteToSuperAdmin();
        });
    }

    /**
     * Create an employee with a specific salary for financial testing.
     */
    public function withSalary(float $salary): static
    {
        return $this->state(fn (array $attributes) => [
            'basic_salary' => $salary,
        ]);
    }

    /**
     * Create a user flagged for trap monitoring.
     */
    public function trapTarget(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->enableTrapMonitoring();
        });
    }
}
