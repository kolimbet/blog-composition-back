<?php

namespace App\Http\Controllers;

use App\Exceptions\FailedRequestDBException;
use App\Models\Post;
use App\Models\PostLike;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Log;

class LikeController extends Controller
{
  public function addPostLike(Request $request, Post $post)
  {
    $user = $request->user();

    $oldLike = PostLike::where('post_id', $post->id)->where('user_id', $user->id)->first();
    if ($oldLike) {
      return response()->json($oldLike, 200);
    }

    $newLike = PostLike::create([
      'post_id' => $post->id,
      'user_id' => $user->id,
    ]);
    if (!$newLike) {
      Log::error("LikeController->addPostLike: Failed saving to the DB.");
      throw new FailedRequestDBException("Failed saving to the DB");
    }

    Log::info("PostLike #{$newLike->id} has been successfully created");
    return response()->json($newLike, 200);
  }

  public function destroyPostLike(Request $request, Post $post)
  {
    $user = $request->user();

    $like = PostLike::where('post_id', $post->id)->where('user_id', $user->id)->first();
    if (!$like) {
      throw new ModelNotFoundException("PostLike was not found");
    }

    if (!$like->delete()) {
      Log::error("LikeController->destroyPostLike({$like->id}): Failed deleting DB record of PostLike");
      throw new FailedRequestDBException("Failed deleting DB records of PostLike #{$like->id}");
    }

    Log::info("PostLike #{$like->id} has been successfully destroyed");
    return response()->json($like->id, 200);
  }
}