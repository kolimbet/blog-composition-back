<?php

use App\Http\Controllers\ImageController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
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

Route::middleware('auth:sanctum')->group(function () {
  Route::get('/logout', [UserController::class, 'logout']);

  Route::get('/user', [UserController::class, 'index']);

  Route::post('/user/check-password', [UserController::class, 'checkPassword']);
  Route::post('/user/update-password', [UserController::class, 'updatePassword']);

  Route::post('/user/avatar', [UserController::class, 'setAvatar']);
  Route::delete('/user/avatar', [UserController::class, 'deleteAvatar']);

  Route::get('/images', [ImageController::class, 'list']);
  Route::get('/images/avatars', [ImageController::class, 'listOfAvatars']);
  Route::get('/images/post/{post}', [ImageController::class, 'listForPost']);
  Route::post('/images', [ImageController::class, 'store']);
  Route::delete('/images/{id}', [ImageController::class, 'destroy']);
});
