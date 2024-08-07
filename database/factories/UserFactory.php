<?php

namespace Database\Factories;

use App\Models\User;
use Hash;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition()
  {
    return [
      'name' => fake()->name(),
      'email' => fake()->unique()->safeEmail(),
      'email_verified_at' => now(),
      'password' => Hash::make('123456'), // password
      'remember_token' => Str::random(10),
    ];
  }

  /**
   * Indicate that the model's email address should be unverified.
   *
   * @return static
   */
  public function unverified()
  {
    return $this->state(fn (array $attributes) => [
      'email_verified_at' => null,
    ]);
  }

  public function admin()
  {
    return $this->state(function (array $attributes) {
      return [
        'is_admin' => true,
        'is_tested' => true,
      ];
    });
  }

  public function simple_user()
  {
    return $this->state(function (array $attributes) {
      return [
        'is_admin' => false,
        'is_tested' => (bool) random_int(0, 2),
        'pre_moderation' => !(bool) random_int(0, 5),
      ];
    });
  }

  public function banned()
  {
    return $this->state(function (array $attributes) {
      return [
        'is_banned' => true,
        'banned_by' => User::where('is_admin', true)->inRandomOrder()->first(),
        'ban_time' => now(),
        'ban_comment' => $this->faker->sentence(7),
      ];
    });
  }
}
