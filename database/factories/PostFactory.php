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

    $excerpt_paragraphs = $this->faker->paragraphs(random_int(1, 2));
    $excerpt_html = '';
    $excerpt_delta = '';
    foreach ($excerpt_paragraphs as $paragraph) {
      $excerpt_html .= '<p>' . $paragraph . '</p>';
      $excerpt_delta .= $paragraph . '\n';
    }

    $text_paragraphs = $this->faker->paragraphs(random_int(3, 12));
    $text_html = '';
    $text_delta = '';
    foreach ($text_paragraphs as $paragraph) {
      $text_html .= '<p>' . $paragraph . '</p>';
      $text_delta .= $paragraph . '\n';
    }

    $is_published = (bool) random_int(0, 4);

    return [
      'user_id' => User::where('is_admin', true)->inRandomOrder()->first(),
      'title' => $title,
      'slug' => Str::slug($title, '-'),
      'excerpt_raw' => '{"ops":[{"insert":"' . $excerpt_delta . '"}]}',
      'excerpt_html' => $excerpt_html,
      'content_raw' => '{"ops":[{"insert":"' . $text_delta . '"}]}',
      'content_html' => $text_html,
      'is_published' => $is_published,
      'published_at' => $is_published ? now() : null,
    ];
  }
}