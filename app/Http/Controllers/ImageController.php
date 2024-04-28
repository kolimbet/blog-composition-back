<?php

namespace App\Http\Controllers;

use App\Exceptions\DataConflictException;
use App\Exceptions\FailedDeletingDirectoryException;
use App\Exceptions\FailedDeletingFileException;
use App\Exceptions\FailedRequestDBException;
use App\Http\Requests\StoreImageRequest;
use App\Http\Resources\ImageResource;
use App\Models\Image;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Http\Request;
use Log;
use Nette\DirectoryNotFoundException;
use SebastianBergmann\CodeCoverage\Util\DirectoryCouldNotBeCreatedException;
use Storage;
use Str;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\File\Exception\CannotWriteFileException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ImageController extends Controller
{
  /**
   * Display a listing of the user avatar images.
   *
   * @param Request $request
   * @return \Illuminate\Http\Response
   */
  public function listOfAvatars(Request $request)
  {
    // return response()->json(["error" => 'test error'], 500);
    $user = $request->user();
    $images = $user->images()->where('attached_to_post', false)->get();
    return response()->json(ImageResource::collection($images), 200);
  }

  /**
   * Display a listing of the images for post.
   *
   * @param Request $request
   * @return \Illuminate\Http\Response
   */
  public function listForPost(Request $request, Post $post)
  {
    // return response()->json(["error" => 'test error'], 500);
    $user = $request->user();
    if (!$user->isAdmin()) throw new AccessDeniedHttpException('Access denied');

    $images = $post->images;
    // Log::info("ImageController->listForPost", [$post->id, $images]);
    return response()->json(ImageResource::collection($images), 200);
  }

  /**
   * Store a newly avatar for user.
   *
   * @param StoreImageRequest $request
   * @return \Illuminate\Http\Response
   */
  public function storeAvatar(StoreImageRequest $request)
  {
    // return response()->json(["error" => 'test error'], 500);
    $user = $request->user();
    ['image' => $uploadedImage, 'image_name' => $uploadedImageName] = $request->only('image', 'image_name');

    $imagePath = null;

    $imagePath = "avatars/{$user->id}";
    if (!Storage::disk('public')->exists($imagePath)) {
      if (!Storage::disk('public')->makeDirectory($imagePath)) {
        throw new DirectoryCouldNotBeCreatedException("Failed creating the directory {$imagePath}", 500);
      }
    }

    return $this->saveUploadedImage($user, $imagePath, $uploadedImage, $uploadedImageName);
  }

  /**
   * Store a newly image for Post
   *
   * @param StoreImageRequest $request
   * @return \Illuminate\Http\Response
   */
  public function storeAttachedToPost(StoreImageRequest $request)
  {
    // return response()->json(["error" => 'test error'], 500);
    Log::info("ImageController->storeAttachedToPost has started");
    $user = $request->user();
    if (!$user->isAdmin()) throw new AccessDeniedHttpException('Access denied');

    ['image' => $uploadedImage, 'image_name' => $uploadedImageName] = $request->only('image', 'image_name');

    $postId = null;
    $post = null;
    $imagePath = null;

    if ($request->has('image_path')) {
      $imagePath = $request->string('image_path');
    }

    if ($request->has('post_id')) {
      $postId = $request->integer('post_id');
      /** @var Post */
      $post = Post::firstOrFail('id', $postId);

      if ($post->image_path) {
        if (!$imagePath) {
          $imagePath = $post->image_path;
        } elseif ($post->image_path != $imagePath) {
          throw new DataConflictException("The received image_path does not match the one already recorded in the DB");
        }
      }
    }

    if ($imagePath) {
      if (!Storage::disk('public')->exists($imagePath)) {
        throw new DirectoryNotFoundException("Directory {$imagePath} not found", 404);
      }
    } else {
      do {
        $imageFolder = random_int(1, 999999999999);
      } while (Storage::disk('public')->exists("images/{$imageFolder}"));

      $imagePath = "images/{$imageFolder}";

      if (!Storage::disk('public')->makeDirectory($imagePath)) {
        throw new DirectoryCouldNotBeCreatedException("Failed creating the directory {$imagePath}", 500);
      }

      if ($post && !$post->image_path) {
        $post->image_path = $imagePath;
        if (!$post->save()) {
          $isCleared = Storage::disk('public')->deleteDirectory($imagePath);
          Log::warning("ImageController->storeAttachedToPost():
            Failed saving to the DB for a new image_path of Post->#{$post->id}.
            Unregistered directory {$imagePath} has been deleted: " + var_export($isCleared, true));

          throw new FailedRequestDBException("Failed saving to the DB for a new image_path of Post->#{$post->id}");
        }
      }
    }

    return $this->saveUploadedImage($user, $imagePath, $uploadedImage, $uploadedImageName, true, $postId);
  }

  /**
   * Saves the uploaded image to the storage and databases
   *
   * @param User $user
   * @param string $imagePath
   * @param [type] $uploadedImage
   * @param string $uploadedImageNam
   * @param boolean $attachedToPost
   * @param [type] $postId
   * @return \Illuminate\Http\Response
   */
  private function saveUploadedImage($user, $imagePath, $uploadedImage, $uploadedImageName, $attachedToPost = false, $postId = null)
  {
    $imageName = Str::remove(".{$uploadedImage->extension()}", Str::lower($uploadedImageName));
    // $imageName = urlencode($imageName); // не подходит для русских имён файлов
    $imageName = Str::slug($imageName);
    $generatedImageName =  $imageName;

    $fullFileName = "{$imagePath}/{$generatedImageName}.{$uploadedImage->extension()}";
    if (Storage::disk('public')->exists($fullFileName)) {
      do {
        Log::info("ImageController->store file {$fullFileName} already exists");
        $generatedImageName = $imageName . random_int(1, 9999);
        $fullFileName = "{$imagePath}/{$generatedImageName}.{$uploadedImage->extension()}";
      } while (Storage::disk('public')->exists($fullFileName));
    }

    if (!Storage::disk('public')->put($fullFileName, $uploadedImage->get())) {
      throw new CannotWriteFileException('ImageController->saveImageFile file saved error ' . $fullFileName);
    }

    $image = Image::create([
      'user_id' => $user->id,
      'attached_to_post' => $attachedToPost,
      'post_id' => $postId,
      'path' => $imagePath,
      'name' => "{$generatedImageName}.{$uploadedImage->extension()}",
      'mime_type' => $uploadedImage->extension(),
    ]);

    if (!$image) {
      $isCleared = Storage::disk('public')->delete($fullFileName);
      Log::warning("ImageController->saveUploadedImageFile: Failed saving to the DB for image {$fullFileName}.
        Unregistered file has been deleted: " . var_export($isCleared, true));
      throw new FailedRequestDBException("Failed saving to the DB for image {$fullFileName}");
    }

    Log::info("ImageController->saveUploadedImageFile: image {$fullFileName} was saved and registered in DB #{$image->id} by {$user->name} #{$user->id}");
    return response()->json([
      'image' => new ImageResource($image),
      'image_path' => $imagePath,
    ], 200);
  }

  /**
   * Remove a user's avatar image
   *
   * @param Request $request
   * @param [type] $id
   * @return \Illuminate\Http\Response
   */
  public function destroyAvatar(Request $request, $id)
  {
    // return response()->json(["error" => 'destroyAvatar test error'], 500);
    $user = $request->user();
    $image = $user->images()->firstWhere('id', $id);
    if (!$image) {
      throw new ModelNotFoundException('Image not found');
    }
    return $this->destroy($image, $id);
  }

  /**
   * Remove image
   *
   * @param Request $request
   * @param int $id
   * @return \Illuminate\Http\Response
   */
  public function destroyImage(Request $request, $id)
  {
    // return response()->json(["error" => 'test error'], 500);
    $user = $request->user();
    if (!$user->isAdmin()) throw new AccessDeniedHttpException('Access denied');

    $image = Image::firstWhere('id', $id);
    if (!$image) {
      throw new ModelNotFoundException('Image not found');
    }

    return $this->destroy($image, $id);
  }

  /**
   * Deleting image file and DB record
   *
   * @param Image $image
   * @param int $id
   * @return \Illuminate\Http\Response
   */
  private function destroy(Image $image, $id)
  {
    $fullFileName = "{$image->path}/{$image->name}";

    if (Storage::disk('public')->exists($fullFileName)) {
      $isFileDeleted = Storage::disk('public')->delete($fullFileName);
      if (!$isFileDeleted) {
        throw new FailedDeletingFileException("Failed deleting an image file");
      }
    }

    $isRecordDeleted = $image->delete();
    if (!$isRecordDeleted) {
      throw new FailedRequestDBException("Failed deleting an image DB record");
    }

    Log::info("Image #{$id} has been deleted");
    return response()->json("Image #{$id} has been deleted", 200);
  }

  /**
   * Allows you to delete images that are not attached to the post
   * from the created directory and the directory itself.
   *
   * @param Request $request
   * @return \Illuminate\Http\Response
   */
  public function clearNonAttached(Request $request)
  {
    // return response()->json(["error" => 'test error'], 500);
    // Log::info("ImageController->clearNonAttached has started");
    $imagePath = $request->string('image_path');
    if (!$imagePath) {
      Log::error("ImageController->clearNonAttached: image_path not received.");
      throw new BadRequestException("Bad request: image_path not received");
    }

    if (!$request->has('image_counter')) {
      Log::error("ImageController->clearNonAttached: image_counter not received.");
      throw new BadRequestException("Bad request: image_counter not received");
    }
    $imageCounter = $request->integer('image_counter');

    if (!Storage::disk('public')->exists($imagePath)) {
      Log::error("ImageController->clearNonAttached: directory {$imagePath} were not found");
      throw new DirectoryNotFoundException("Directory {$imagePath} not found", 404);
    }

    if (!Storage::disk('public')->deleteDirectory($imagePath)) {
      Log::error("ImageController->clearNonAttached: Failed to deleting directory {$imagePath}");
      throw new FailedDeletingDirectoryException("Failed to deleting directory {$imagePath}");
    }

    if ($imageCounter) {
      $images = Image::where("attached_to_post", true)->where("path", $imagePath)->get();
      if (!$images) {
        Log::warning("ImageController->clearNonAttached: images from the directory {$imagePath} were not found in the DB");
        throw new RecordsNotFoundException("images from the directory {$imagePath} were not found in the DB");
      }

      if (!$images->map->delete()) {
        Log::error("ImageController->clearNonAttached: Failed deleting DB records of images from directory {$imagePath}");
        throw new FailedRequestDBException("Failed deleting DB records of images");
      }
    }

    Log::info("ImageController->clearNonAttached: Images from directory {$imagePath} have been deleted");
    return response()->json("Images from directory {$imagePath} have been deleted", 200);
  }
}
