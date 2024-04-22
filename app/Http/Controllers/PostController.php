<?php

namespace App\Http\Controllers;

use App\Exceptions\DataConflictException;
use App\Exceptions\FailedRequestDBException;
use App\Http\Resources\ImageResource;
use App\Models\Image;
use App\Models\Post;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Http\Request;
use Log;
use Str;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PostController extends Controller
{
  protected $pageLimit = 10;

  public function feed(Request $request)
  {
    // return response()->json(["error" => "Test error"], 500);
    $postList = Post::where('is_published', true)->orderBy('published_at', 'desc')->paginate($this->pageLimit)->withPath('');
    return response()->json($postList, 200);
  }

  public function listForAdmin(Request $request)
  {
    // return response()->json(["error" => "Test error"], 500);
    $user = $request->user();
    if (!$user->isAdmin()) throw new AccessDeniedHttpException('Access denied');

    $postList = Post::orderBy('id', 'desc')->paginate($this->pageLimit)->withPath('');
    return response()->json($postList, 200);
  }

  public function show(Request $request, $slug)
  {
    // return response()->json(["error" => "Test error"], 500);
    $post = null;
    if (ctype_digit($slug)) $post = Post::whereId($slug)->first();
    if (!$post) $post = Post::whereSlug($slug)->first();
    if (!$post) {
      throw new ModelNotFoundException();
    }

    $user = $request->user();
    if (!$post->is_published && (!$user || !$user->isAdmin())) {
      throw new AccessDeniedHttpException('Access denied');
    }

    // A resource is needed
    return response()->json($post, 200);
  }

  public function showForAdmin(Request $request, $slug)
  {
    // return response()->json(["error" => 'test error'], 500);
    $user = $request->user();
    if (!$user->isAdmin()) throw new AccessDeniedHttpException('Access denied');

    $post = null;
    if (ctype_digit($slug)) $post = Post::whereId($slug)->first();
    if (!$post) $post = Post::whereSlug($slug)->first();
    if (!$post) {
      throw new ModelNotFoundException();
    }

    $images = $post->images()->get();
    return response()->json(['post' => $post, 'images' => $images ? ImageResource::collection($images) : []], 200);
  }

  public function store(Request $request)
  {
    // return response()->json(["error" => 'test error'], 500);
    $user = $request->user();
    if (!$user->isAdmin()) throw new AccessDeniedHttpException('Access denied');

    if (!$request->has('image_counter')) {
      Log::error("PostController->store: image_counter not received.");
      throw new BadRequestException("Bad request: image_counter not received");
    }
    $imageCounter = $request->integer('image_counter');

    $postData = $request->only('title', 'slug', 'excerpt_raw', 'excerpt_html', 'content_raw', 'content_html', 'is_published', 'image_path');
    if (!$postData['slug']) {
      $postData['slug'] = Str::slug($postData['title'], '-');
    }
    $postData['slug'] = substr($postData['slug'], 0, 100);
    if (Post::where('slug', $postData['slug'])->first()) {
      return response()->json(['error' => 'This Slug is already being used by another post'], 400);
    }

    $postData['user_id'] = $user->id;
    /**
     * @var Post $post
     */
    $post = Post::create($postData);

    if (!$post) {
      Log::warning("PostController->store: Failed saving to the DB.");
      throw new FailedRequestDBException("Failed saving to the DB");
    }

    if ($post->image_path && $imageCounter) {
      try{
         $images = Image::where("attached_to_post", true)->where("path", $post->image_path)->get();
        if (!$images) {
          Log::warning("ImageController->store: images from the directory {$post->image_path} were not found in the DB");
          throw new RecordsNotFoundException("images from the directory {$post->image_path} were not found in the DB");
        }

        foreach ($images as $image) {
          /**
           * @var Image $image
           */
          $image->post_id = $post->id;
          $image->save();
        }
      } catch(Exception $e) {
        $clearPost = $post->delete();
        Log::error("ImageController->store: Failed to attach images to the created post. Clear an incorrect post: " + var_export($clearPost, true));
        throw $e;
      }
    }

    return response()->json($post->id, 200);
  }

  public function update(Request $request, Post $post)
  {
    // return response()->json(["error" => "Test error"], 500);
    $user = $request->user();
    if (!$user->isAdmin()) throw new AccessDeniedHttpException('Access denied');

    $postData['user_id'] = $user->id;

    $postData = $request->only('title', 'slug', 'excerpt_raw', 'excerpt_html', 'content_raw', 'content_html', 'is_published', 'image_path');
    if (!$postData['slug']) {
      $postData['slug'] = Str::slug($postData['title'], '-');
    }
    $postData['slug'] = substr($postData['slug'], 0, 100);
    if (Post::where('slug', $postData['slug'])->where('id', '<>', $post->id)->first()) {
      return response()->json(['error' => 'This Slug is already being used by another post'], 400);
    }

    if (!$post->update($postData)) {
      Log::warning("PostController->update: Failed to update post #{$post->id} to the DB");
      throw new FailedRequestDBException("Failed to update post #{$post->id} to the DB");
    }

    Log::info("PostController->update: post #{$post->id} updated successfully");
    $images = $post->images()->get();
    return response()->json(['post' => $post, 'images' => $images ? ImageResource::collection($images) : []], 200);
  }
}