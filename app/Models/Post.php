<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
  use HasFactory;

  protected $fillable = [
    'user_id',
    'title',
    'slug',
    'excerpt_raw',
    'excerpt_html',
    'content_raw',
    'content_html',
    'is_published',
    'published_at',
    'image_path',
  ];

  /**
   * Get the user who wrote this post
   */
  public function user()
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Get images attached to the post
   */
  public function images()
  {
    return $this->hasMany(Image::class);
  }

  /**
   * Get tags of the post
   */
  public function tags()
  {
    return $this->belongsToMany(Tag::class, 'post_tag');
  }

  /**
   * Get likes attached to the post
   */
  public function likes()
  {
    return $this->hasMany(PostLike::class);
  }

  /**
   * Get comments on the post
   */
  public function comments()
  {
    return $this->hasMany(Comment::class);
  }

  /**
   * Mark as published with published at time
   *
   * @param [boolean] $value
   * @return void
   */
  public function setIsPublishedAttribute($value) {
    // Log::info("setIsPublishedAttribute", [$value, $this->attributes]);
    $value = (bool) $value;
    $oldValue = null;
    if (isset($this->attributes['is_published'])) $oldValue = $this->attributes['is_published'];
    if ($oldValue !== $value) {
      $this->attributes['is_published'] = $value;
      if ($value) {
        $this->attributes['published_at'] = Carbon::now()->format('Y-m-d H:i:s');
      } else {
        $this->attributes['published_at'] = null;
      }
    }
  }
}