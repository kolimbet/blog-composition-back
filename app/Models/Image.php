<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
  use HasFactory;

  protected $fillable = ['user_id', 'post_image', 'post_id', 'name', 'mime_type', 'path'];

  protected $hidden = ['created_at', 'updated_at'];

  /**
   * Get the user who added the image
   */
  public function user()
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Get the user whose avatar is this image
   */
  public function avatarOwner()
  {
    return $this->hasOne(User::class);
  }

  /**
   * Get the post to which the image is attached
   */
  public function post()
  {
    return $this->belongsTo(Post::class);
  }
}