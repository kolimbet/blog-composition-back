<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Log;
use Str;
use Symfony\Component\Console\Input\Input;

class PostController extends Controller
{
  protected $pageLimit = 10;

  public function listOfPublished(Request $request)
  {
    $postList = Post::where('is_published', true)->orderBy('published_at', 'desc')->paginate($this->pageLimit)->withPath('');
    return response()->json($postList, 200);
  }

  public function listInAccount(Request $request)
  {
    $user = $request->user();
    $postList = $user->posts()->orderBy('id', 'desc')->paginate($this->pageLimit)->withPath('');
    return response()->json($postList, 200);
  }

  public function showInAccount(Request $request, $id)
  {
    $post = null;
    if (ctype_digit($id)) $post = Post::whereId($id)->first();
    if (!$post) $post = Post::whereSlug($id)->first();
    if (!$post) return response()->json(["error" => "Record not found"], 500);

    return response()->json($post, 200);
  }

  public function store(Request $request)
  {
    $postData = $request->only('title', 'slug', 'excerpt_raw', 'excerpt_html', 'content_raw', 'content_html', 'is_published', 'image_path');
    if (!$postData['slug']) {
      $postData['slug'] = Str::slug($postData['title'], '-');
    }
    $postData['slug'] = substr($postData['slug'], 0, 100);
    $postData['user_id'] = $request->user()->id;

    $post = Post::create($postData);

    if ($post) return response()->json($post->id, 200);
    else return response()->json(["error" => "Failed to save a new post"], 500);
  }

  public function update(Request $request, $id)
  {
    $post = null;
    $post = Post::whereId($id)->first();
    $postData = $request->only('title', 'slug', 'excerpt_raw', 'excerpt_html', 'content_raw', 'content_html', 'is_published', 'image_path');
    if (!$postData['slug']) {
      $postData['slug'] = Str::slug($postData['title'], '-');
    }
    $postData['slug'] = substr($postData['slug'], 0, 100);

    if ($post->update($postData)) {
      Log::info('PostController->update post #' . $id, [$postData]);
      return response()->json($post, 200);
    } else return response()->json(["error" => "Failed to update post #{$id}"], 500);
  }
}
