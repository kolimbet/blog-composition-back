<?php

use App\Http\Controllers\ImageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/name-is-free', [UserController::class, 'nameIsFree']);
Route::post('/email-is-free', [UserController::class, 'emailIsFree']);
Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

Route::get('/posts', [PostController::class, 'feed']);
Route::get('/posts/{slug}', [PostController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
  Route::get('/logout', [UserController::class, 'logout']);

  Route::get('/user', [UserController::class, 'index']);

  Route::post('/user/check-password', [UserController::class, 'checkPassword']);
  Route::post('/user/update-password', [UserController::class, 'updatePassword']);

  Route::post('/user/avatar', [UserController::class, 'setAvatar']);
  Route::delete('/user/avatar', [UserController::class, 'deleteAvatar']);

  Route::get('/avatars', [ImageController::class, 'listOfAvatars']);
  Route::post('/avatars', [ImageController::class, 'storeAvatar']);
  Route::delete('/avatars/{id}', [ImageController::class, 'destroy']);

  Route::middleware('is.admin')->group(function () {
    Route::get('/admin/posts', [PostController::class, 'listInAccount']);
    Route::get('/admin/posts/{slug}', [PostController::class, 'showInAccount']);
    Route::post('/admin/posts/{id}', [PostController::class, 'update']);
    Route::post('/admin/posts', [PostController::class, 'store']);

    Route::get('/images/post/{post}', [ImageController::class, 'listForPost']);
    Route::post('/images', [ImageController::class, 'storeAttachedToPost']);
    Route::delete('/images/{id}', [ImageController::class, 'destroy']);
  });
});