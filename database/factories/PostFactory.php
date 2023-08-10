<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition()
  {
    $title = $this->faker->unique()->sentence(random_int(2, 6));
    $paragraphs = $this->faker->paragraphs(random_int(3, 12));
    $text = '';
    foreach ($paragraphs as $paragraph) {
      $text .= '<p>' . $paragraph . '</p>';
    }
    $is_published = (bool) random_int(0, 4);

    return [
      'user_id' => User::where('is_admin', true)->inRandomOrder()->first(),
      'title' => $title,
      'slug' => Str::slug($title, '-'),
      'excerpt_raw' => '',
      'excerpt_html' => '<p>' . $this->faker->paragraph() . '</p>',
      'content_raw' => '',
      'content_html' => $text,
      'is_published' => $is_published,
      'published_at' => $is_published ? now() : null,
    ];
  }
}