<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use Auth;
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
  public function index(Request $request)
  {
    return response()->json(new UserResource($request->user()), 200);
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
}