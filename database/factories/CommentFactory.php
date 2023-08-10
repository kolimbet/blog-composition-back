<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition()
  {
    $paragraphs = $this->faker->paragraphs(random_int(1, 3));
    $text = '';
    foreach ($paragraphs as $paragraph) {
      $text .= '<p>' . $paragraph . '</p>';
    }

    $deleted = !((bool) random_int(0, 9));

    return [
      'post_id' => Post::inRandomOrder()->first(),
      'user_id' => User::inRandomOrder()->first(),
      'text_raw' => '',
      'text_html' => $text,
      'is_published' => (bool) random_int(0, 9),

      'deleted_at' => $deleted ? now() : null,
      'deleted_by' => $deleted ? User::where('is_admin', true)->inRandomOrder()->first() : null,
    ];
  }
}