<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use File;
use Illuminate\Database\Eloquent\Factories\Factory;
use Log;
use Storage;
use Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Image>
 */
class ImageFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition()
  {
    return [
      'user_id' =>  User::factory(),
      'post_id' => Post::factory(),
    ];
  }

  public function avatar(User $user)
  {
    return $this->generateImage($user);
  }

  public function postImage(User $user, Post $post)
  {
    return $this->generateImage($user, $post);
  }

  public function generateImage(User $user, Post|bool $post = false)
  {
    // Log::info("Image current parameters", [$user->id, $post]);
    if($post) {
      $seederPath = "posts";
      $targetPath = "posts/{$post->id}";
    } else {
      $seederPath = "avatars";
      $targetPath = "avatars/{$user->id}";
    }

    $fileList = Storage::disk('seeds')->files($seederPath);

    $filePath = $fileList[random_int(0, count($fileList) - 1)];
    $fileExtension = File::extension(Storage::disk('seeds')->path($filePath));
    $fileMimeType = Storage::disk('seeds')->mimeType($filePath);
    $fileFullName = Str::remove("{$seederPath}/", $filePath);
    $fileName = Str::remove(".{$fileExtension}", $fileFullName);
    $fileContent = Storage::disk('seeds')->get($filePath);

    $newFileName = $fileName . '.'. $fileExtension;
    if (Storage::disk('public')->exists($targetPath . '/' .$newFileName)) {
      do {
        $newFileName =  $fileName . random_int(1, 9999) .'.'. $fileExtension;
      } while (Storage::disk('public')->exists($targetPath . '/' .$newFileName));
    }

    Storage::disk('public')->put($targetPath . '/' .$newFileName, $fileContent);
    Log::info("ImageFactory: created new file: " . $targetPath . '/' .$newFileName);

    return $this->state(fn (array $attributes) => [
      'user_id' => $user->id,
      'post_id' => $post ? $post->id : null,
      'path' => $targetPath,
      'name' => $newFileName,
      'mime_type' => $fileMimeType,
    ]);
  }
}