<?php

namespace App\Http\Controllers;

use App\Exceptions\FailedRequestDBException;
use App\Http\Requests\CommentRequest;
use App\Http\Resources\CommentPaginatedCollection;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Log;
use Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CommentController extends Controller
{
  /**
   * The number of records displayed on the page
   *
   * @var integer
   */
  protected $pageLimit = 10;

  /**
   * Returns a paginated list of comments for the post
   *
   * @param Request $request
   * @param string $postSlug
   * @return Response
   */
  public function listForPost(Request $request, Post $post)
  {
    // return response()->json(["error" => "Test error"], 500);
    DB::enableQueryLog();
    $user = auth('sanctum')->user();
    $comments = false;
    if ($user) {
      $comments = Comment::where('post_id', $post->id)->where(function ($query) use ($user) {
        $query->where('is_published', true)->orWhere('user_id', $user->id);
      })->orderBy('is_published', 'desc')->orderBy('published_at', 'asc')->orderBy('id', 'asc')
        ->with('user.avatar')->paginate($this->pageLimit)->withPath('');
    } else {
      $comments = Comment::where('post_id', $post->id)->where('is_published', true)
        ->orderBy('published_at', 'asc')->orderBy('id', 'asc')
        ->with('user.avatar')->paginate($this->pageLimit)->withPath('');
    }

    $result = response()->json(new CommentPaginatedCollection($comments), 200);
    Log::info("CommentController->listForPost() DB query log:", [$user ? $user->id : "noAuth", DB::getQueryLog()]);
    return $result;
  }

  /**
   * Saves a new comment
   *
   * @param CommentRequest $request
   * @param Post $post
   * @return Response
   */
  public function store(CommentRequest $request, Post $post)
  {
    $user = $request->user();
    if ($user->is_banned) {
      throw new AccessDeniedHttpException('Access denied. You are banned.');
    }

    $commentData = $request->only('text_raw', 'text_html');
    $commentData['user_id'] = $user->id;
    $commentData['post_id'] = $post->id;
    if ($user->is_tested) {
      $commentData['is_published'] = true;
    }

    $comment = Comment::create($commentData);
    if (!$comment) {
      Log::warning("CommentController->store: Failed saving to the DB.");
      throw new FailedRequestDBException("Failed saving to the DB");
    }

    Log::info("Comment #{$comment->id} has been created by user #{$user->id}");
    return response()->json(new CommentResource($comment), 200);
  }

  /**
   * Deletes a comment
   *
   * @param Request $request
   * @param Comment $comment
   * @return Response
   */
  public function destroy(Request $request, Comment $comment)
  {
    $user = $request->user();
    if (!$user->is_admin && $user->id != $comment->user_id) {
      throw new AccessDeniedHttpException('Access denied');
    }

     if (!$comment->delete()) {
      Log::error("CommentController->destroy({$comment->id}): Failed deleting DB record of tag");
      throw new FailedRequestDBException("Failed deleting DB records of tag #{$comment->id}");
    }

    Log::info("Comment #{$comment->id} has been deleted by the {$user->name} #{$user->id}");
    return response()->json(["Tag #{$comment->id} has been successfully deleted"], 200);
  }
}
