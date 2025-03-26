<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\UpdateUserPasswordRequest;
use App\Http\Resources\CommentResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\UserResource;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Auth;
use Hash;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Log;

class UserController extends Controller
{
  /**
   * Get data of an authorized user
   *
   * @param Request $request
   * @return \Illuminate\Http\Response
   */
  public function checkAuth(Request $request)
  {
    return response()->json(new UserResource($request->user()), 200);
  }

  /**
   * Get extended data about an authorized user
   *
   * @param Request $request
   * @return \Illuminate\Http\Response
   */
  public function aboutSelf(Request $request)
  {
    $user = User::whereId($request->user()->id)->with('avatar')->withCount('comments')->withCount('posts')->first();

    return $this->about($user);
  }

  /**
   * Get extended data about User by ID
   *
   * @param Request $request
   * @param [type] $id  User ID
   * @return \Illuminate\Http\Response
   */
  public function aboutAnother(Request $request , $id) {
    $user = User::whereId($id)->with('avatar')->withCount('comments')->withCount('posts')->first();

    if (!$user) {
      throw new ModelNotFoundException('User was not found');
    }

    return $this->about($user);
  }

  /**
   * Get extended data about User
   *
   * @param User $user
   * @return \Illuminate\Http\Response
   */
  protected function about(User $user) {
    $res = [];

    $res['user'] = new UserResource($user);

    $res['last_comments'] = Comment::whereUserId($user->id)->with('user.avatar')->orderBy('id', 'desc')->limit(5)->get();
    $res['last_comments'] = CommentResource::collection($res['last_comments']);

    if ($user->is_admin) {
      $res['last_post'] = Post::whereUserId($user->id)->where('is_published', true)
        ->with(['user.avatar', 'likes'])->withCount('comments')->orderBy('id', 'desc')->first();
      $res['last_post'] = new PostResource($res['last_post']);
    }

    return response()->json($res, 200);
  }

  /**
   * User authorization
   *
   * @param LoginRequest $request
   * @return \Illuminate\Http\Response
   */
  public function login(LoginRequest $request)
  {
    // return response()->json(["error" => 'test error'], 500);
    $credentials = $request->only('email', 'password');
    $remember = $request->boolean('remember');

    $isLogin = Auth::attempt($credentials, $remember);

    if (!$isLogin) {
      Log::info("user login " . json_encode($isLogin), [$credentials, $remember]);
      return response()->json(["error" => 'Invalid login details'], 500);
    } else {
      $user = Auth::user();
      $user->tokens()->delete();
      $token = $user->createToken('authToken')->plainTextToken;
      Log::info("user #{$user->id} login " . json_encode($isLogin) . ". Remember - " . json_encode($remember) . ". New token: {$token}");
      return response()->json([
        'access_token' => $token,
        'token_type' => 'Bearer',
        'user' => new UserResource($user),
      ]);
    }
  }

  /**
   * User logout
   *
   * @param Request $request
   * @return \Illuminate\Http\Response
   */
  public function logout(Request $request)
  {
    $user = $request->user();
    $message = "user #{$user->id} logout";
    $user->tokens()->delete();
    Log::info($message);

    return response()->json([
      'success' => true,
      'message' => $message,
    ], 200);
  }

  /**
   * Checks if the login is free
   *
   * @param Request $request
   * @return \Illuminate\Http\Response
   */
  public function nameIsFree(Request $request)
  {
    // return response()->json(["error" => 'test error'], 500);
    if (!$request->has("name")) return response()->json(["error" => "Name not received"], 500);
    $name = $request->string("name");

    $user = User::where("name", $name)->first();
    $isFree = !$user;

    Log::info("check name '{$name}' - " . ($isFree ? "free" : "busy"));
    return response()->json($isFree, 200);
  }

  /**
   * Checks if the email is free
   *
   * @param Request $request
   * @return \Illuminate\Http\Response
   */
  public function emailIsFree(Request $request)
  {
    // return response()->json(["error" => "test error " . __METHOD__], 500);
    if (!$request->has("email")) return response()->json(["error" => "Email address not received"], 500);
    $email = $request->string("email");

    $user = User::where("email", $email)->first();
    $isFree = !$user;

    Log::info("check email '{$email}' - " . ($isFree ? "free" : "busy"));
    return response()->json($isFree, 200);
  }

  /**
   * Registering a new user
   *
   * @param CreateUserRequest $request
   * @return \Illuminate\Http\Response
   */
  public function register(CreateUserRequest $request)
  {
    // return response()->json(["error" => "test error " . __METHOD__], 500);
    $regData = $request->only("name", "email", "password");
    $regData["password"] = Hash::make($regData["password"]);

    $user = User::create($regData);
    Log::info("register new user - " . json_encode((bool) $user) /*, [$regData, $user]*/);
    if ($user) return response()->json(true, 200);
    else return response()->json(["error" => "Failed to save a new user"], 500);
  }

  /**
   * Check user password
   *
   * @param Request $request
   * @return \Illuminate\Http\Response
   */
  public function checkPassword(Request $request) {
    if (!$request->has("password")) return response()->json(["error" => "Password not received"], 500);
    $password = $request->string("password");

    $user = $request->user();
    if (!$user) return response()->json(["error" => "User is not logged in"], 500);

    $isCorrect = Hash::check($password, $user->password);

    // return response()->json(["error" => 'test error'], 500);
    Log::info("check user #{$user->id} password - " . json_encode($isCorrect));
    return response()->json($isCorrect, 200);
  }

  /**
   * Changing the user's password
   *
   * @param UpdateUserPasswordRequest $request
   * @return \Illuminate\Http\Response
   */
  public function updatePassword(UpdateUserPasswordRequest $request) {
    $user = $request->user();
    if (!$user) return response()->json(["error" => "User is not logged in"], 500);

    $passData = $request->only("password", "new_password", "new_password_repeat");
    $isPasswordVerified = Hash::check($passData["password"], $user->password);
    // return response()->json(["error" => 'test error'], 500);

    if (!$isPasswordVerified) return response()->json(["error" => "Invalid current password entered"], 500);
    $user->password = Hash::make($passData["new_password"]);
    $result = $user->save();
    Log::info("change user #{$user->id} password on {$user->password}", [$result]);
    if ($result) return response()->json((bool) $result, 200);
    else return response()->json(["error" => "Failed to save a new user password record"], 500);
  }

  /**
   * Set the user's avatar
   *
   * @param Request $request
   * @return \Illuminate\Http\Response
   */
  public function setAvatar(Request $request) {
    // return response()->json(["error" => 'test error'], 500);
    if (!$request->has("id")) return response()->json(["error" => "Image id not received"], 500);
    $imageId = $request->input("id");

    $user = $request->user();
    if (!$user) return response()->json(["error" => "User is not logged in"], 500);

    // $image = $user->images()->where('id', $imageId)->first();
    $image = $user->images()->where('id', $imageId)->first();
    if (!$image) return response()->json(["error" => 'Image record not found'], 500);

    $user->avatar_id = $image->id;
    $result = $user->save();
    Log::info("change user #{$user->id} avatar on image #{$user->avatar_id}", [$result]);
    if ($result) return response()->json(new UserResource($user), 200);
    else return response()->json(["error" => 'Failed to save a new user avatar record'], 500);
  }

  /**
   * Delete a user's avatar
   *
   * @param Request $request
   * @return \Illuminate\Http\Response
   */
  public function deleteAvatar(Request $request) {
    // return response()->json(["error" => 'test error'], 500);
    $user = $request->user();
    if (!$user) return response()->json(["error" => "User is not logged in"], 500);

    $user->avatar_id = null;
    $result = $user->save();
    Log::info("delete user #{$user->id} avatar", [$result]);
    if ($result) return response()->json(new UserResource($user), 200);
    else return response()->json(["error" => 'Failed to delete user avatar record'], 500);
  }
}
