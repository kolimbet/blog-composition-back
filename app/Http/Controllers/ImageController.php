<?php

namespace App\Http\Controllers;

use App\Exceptions\DataConflictException;
use App\Exceptions\FailedDeletingFileException;
use App\Exceptions\FailedRequestDBException;
use App\Http\Requests\StoreImageRequest;
use App\Http\Resources\ImageResource;
use App\Models\Image;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Log;
use Nette\DirectoryNotFoundException;
use SebastianBergmann\CodeCoverage\Util\DirectoryCouldNotBeCreatedException;
use Storage;
use Str;
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
  public function listForPost(Request $request, $post)
  {
    // return response()->json(["error" => 'test error'], 500);
    $images = $post->images()->get();
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

    return $this->saveUploadedImage($user, $imagePath, $uploadedImage, $uploadedImageName, true, null);
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
    $user = $request->user();
    if (!$user->isAdmin()) throw new AccessDeniedHttpException('Access denied');

    ['image' => $uploadedImage, 'image_name' => $uploadedImageName] = $request->only('image', 'image_name');

    $postId = null;
    $post = null;
    $imageFolder = null;
    $imagePath = "";

    if ($request->has('image_folder')) {
      $imageFolder = $request->string('image_folder');
    }

    if ($request->has('post_id')) {
      $postId = $request->integer('post_id');
      /** @var Post */
      $post = Post::firstOrFail($postId);

      if ($post->image_path) {
        if (!$imageFolder) {
          $imageFolder = $post->image_path;
        } elseif ($post->image_path !== $imageFolder) {
          throw new DataConflictException();
        }
      }
    }

    if ($imageFolder) {
      $imagePath = "images/{$imageFolder}";
      if (!Storage::disk('public')->exists("images/{$imageFolder}")) {
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
        $post->image_path = $imageFolder;
        if (!$post->save()) {
          $isCleared = Storage::disk('public')->deleteDirectory($imagePath);
          Log::warning("ImageController->storeAttachedToPost():
            Failed saved in the DB for a new image_folder of Post->#{$post->id}.
            Unregistered directory {$imagePath} has been deleted: " + var_export($isCleared, true));

          throw new FailedRequestDBException("Failed saved in the DB for a new image_folder of Post->#{$post->id}");
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
      Log::error('ImageController->saveImageFile file saved error', [$fullFileName]);
      throw new CannotWriteFileException('ImageController->saveImageFile file saved error');
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
      Log::warning("ImageController->saveUploadedImageFile: Failed registered in DB for image {$fullFileName}.
        Unregistered file has been deleted: " . var_export($isCleared, true));
      throw new FailedRequestDBException("Failed registered in DB for image {$fullFileName}");
    }

    Log::info("ImageController->saveUploadedImageFile: image {$fullFileName} was saved and registered in DB #{$image->id} by {$user->name} #{$user->id}");
    return response()->json([
      'image' => new ImageResource($image),
      'image_folder' => $imagePath,
    ], 200);
  }

  /**
   * Remove a user's avatar image
   *
   * @param Request $request
   * @param int $id
   * @return \Illuminate\Http\Response
   */
  public function destroyAvatar(Request $request, $id)
  {
    // return response()->json(["error" => 'test error'], 500);
    $user = $request->user();
    $image = $user->images()->firstOrFail($id);

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

    $image = Image::firstOrFail($id);

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

    return response()->json("Image #{$id} has been deleted", 200);
  }
}
