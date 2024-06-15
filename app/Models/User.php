<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
  use HasApiTokens, HasFactory, Notifiable;

  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'name',
    'email',
    'password',
    'is_admin',
    'is_banned',
    'banned_by',
    'ban_time',
    'ban_comment',
    'avatar_id',
  ];

  /**
   * The attributes that should be hidden for serialization.
   *
   * @var array<int, string>
   */
  protected $hidden = [
    'password',
    'remember_token',
  ];

  /**
   * The attributes that should be cast.
   *
   * @var array<string, string>
   */
  protected $casts = [
    'email_verified_at' => 'datetime',
  ];

  /**
   * Get posts written by the user
   */
  public function posts()
  {
    return $this->hasMany(Post::class);
  }

  /**
   * Get postLikes written by the user
   */
  public function postLikes()
  {
    return $this->hasMany(PostLike::class);
  }

  /**
   * Get images added by the user
   */
  public function images() {
    return $this->hasMany(Image::class);
  }

  /**
   * Get the user's avatar
   */
  public function avatar() {
    return $this->belongsTo(Image::class);
  }

  /**
   * Get user comments
   */
  public function comments()
  {
    return $this->hasMany(Comment::class);
  }

  /**
   * Checking the administrator status
   */
  public function isAdmin()
  {
    return $this->is_admin == true;
  }

  /**
   * Checking for a ban
   */
  public function isBanned()
  {
    return $this->is_banned == true;
  }
}