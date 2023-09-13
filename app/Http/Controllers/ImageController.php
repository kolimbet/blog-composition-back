<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreImageRequest;
use App\Http\Resources\ImageResource;
use App\Models\Image;
use Illuminate\Http\Request;
use Log;
use Storage;
use Str;

class ImageController extends Controller
{
  /**
   * Display a listing of the resource.
   *
   * @param Request $request
   * @return \Illuminate\Http\Response
   */
  public function list(Request $request) {
    // return response()->json(["error" => 'test error'], 500);
    $user = $request->user();
    $images = $user->images()->get();
    return response()->json(ImageResource::collection($images), 200);
  }

  /**
   * Display a listing of the images for post.
   *
   * @param Request $request
   * @return \Illuminate\Http\Response
   */
  public function listForPost(Request $request, $post) {
    // return response()->json(["error" => 'test error'], 500);
    $images = $post->images()->get();
    return response()->json(ImageResource::collection($images), 200);
  }

  /**
   * Display a listing of the user avatar images.
   *
   * @param Request $request
   * @return \Illuminate\Http\Response
   */
  public function listOfAvatars(Request $request) {
    // return response()->json(["error" => 'test error'], 500);
    $user = $request->user();
    $images = $user->images()->where('post_id', null)->get();
    return response()->json(ImageResource::collection($images), 200);
  }

  /**
   * Store a newly created resource in storage.
   *
   * @param StoreImageRequest $request
   * @return \Illuminate\Http\Response
   */
  public function store(StoreImageRequest $request) {
    // return response()->json(["error" => 'test error'], 500);
    $user = $request->user();
    $newImage = $request->only('image', 'image_name');
    $post_id = null;
    if ($request->has('post_id')) {
      $post_id = $request->integer('post_id');
    }

    $imageName = Str::remove(".{$newImage['image']->extension()}", Str::lower($newImage['image_name']));
    // $imageName = urlencode($imageName); // не подходит для русских имён файлов
    $imageName = Str::slug($imageName);
    $generatedImageName =  $imageName;

    $path = $post_id ? "posts/{$user->id}" : "avatars/{$user->id}";
    if (!Storage::disk('public')->exists($path)) {
      // Storage::disk('public')->makeDirectory($path, "777", true);
      Storage::disk('public')->makeDirectory($path);
    }

    $fullFileName = "{$path}/{$generatedImageName}.{$newImage['image']->extension()}";
    if (Storage::disk('public')->exists($fullFileName)) {
      do {
        Log::info("ImageController->store file {$fullFileName} already exists");
        $generatedImageName = $imageName . random_int(1, 9999);
        $fullFileName = "{$path}/{$generatedImageName}.{$newImage['image']->extension()}";
      } while (Storage::disk('public')->exists($fullFileName));
    }

    if (!Storage::disk('public')->put($fullFileName, $newImage['image']->get())) {
      Log::info("ImageController->store file saved error", [$fullFileName]);
      return response()->json(["error" => 'Image file saving error'], 500);
    }

    $image = Image::create([
      'user_id' => $user->id,
      'post_id' => $post_id,
      'path' => $path,
      'name' => "{$generatedImageName}.{$newImage['image']->extension()}",
      'mime_type' => $newImage['image']->extension(),
    ]);

    if ($image) {
      Log::info("ImageController->store file {$fullFileName} was saved and registered in DB #{$image->id}");
      return response()->json(new ImageResource($image), 200);
    } else {
      $isDeleted = Storage::disk('public')->delete($fullFileName);
      Log::info("ImageController->store registered in DB Error for {$fullFileName}. Clear - " . json_encode($isDeleted));
      return response()->json(["error" => 'Registered in DB Error'], 500);
    }
  }

  /**
   * Remove the specified resource from storage.
   *
   * @param Request $request
   * @param int $id
   * @return \Illuminate\Http\Response
   */
  public function destroy(Request $request, $id) {
    // return response()->json(["error" => 'test error'], 500);
    $user = $request->user();

    $image = $user->images()->where('id', $id)->first();
    if (!$image) return response()->json(["error" => 'Image record not found'], 500);

    $fullFileName = "{$image->path}/{$image->name}";
    $isFileDeleted = Storage::disk('public')->delete($fullFileName);
    if (!$isFileDeleted && Storage::disk('public')->exists($fullFileName))
      return response()->json(["error" => 'Error deleting an image file'], 500);

    $isRecordDeleted = $image->delete();

    if ($isRecordDeleted) return response()->json("Record #{$id} deleted", 200);
    else return response()->json(["error" => "Error deleting an image record"], 404);
  }
}
