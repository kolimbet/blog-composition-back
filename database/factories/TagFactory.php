<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tag>
 */
class TagFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition()
  {
    $name = $this->faker->unique()->word();
    return [
      'name' => $name,
      'slug' => Str::slug($name, '-'),
      'name_low_case' => Str::lower($name),
    ];
  }

  /**
   * Generate Tag by name
   */
  public function generateByName($name) {
    return $this->state(function () use ($name) {
      return [
      'name' => $name,
      'slug' => Str::slug($name, '-'),
      'name_low_case' => Str::lower($name),
      ];
    });
  }
}