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
    $text_html = '';
    $text_delta = '';
    foreach ($paragraphs as $paragraph) {
      $text_html .= '<p>' . $paragraph . '</p>';
      $text_delta .= $paragraph . '\n';
    }

    $is_published = (bool) random_int(0, 9);
    $is_checked = $is_published ? true : false;

    $is_deleted = $is_checked ? false : (bool) random_int(0, 1);

    return [
      'post_id' => Post::inRandomOrder()->first(),
      'user_id' => User::inRandomOrder()->first(),
      'text_raw' => '{"ops":[{"insert":"' . $text_delta . '"}]}',
      'text_html' => $text_html,
      'is_published' => $is_published,
      'is_checked' => $is_checked,

      'deleted_at' => $is_deleted ? now() : null,
      'deleted_by' => $is_deleted ? User::where('is_admin', true)->inRandomOrder()->first() : null,
    ];
  }
}