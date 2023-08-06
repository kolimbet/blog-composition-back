<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
  use HasFactory;

  protected $fillable = [
    'user_id',
    'main_image_id',
    'title',
    'slug',
    'excerpt_raw',
    'excerpt_html',
    'content_raw',
    'content_html',
    'is_published',
    'published_at',
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
   * Get the main image of the post
   */
  public function mainImage()
  {
    return $this->belongsTo(Image::class);
  }

  /**
   * Get tags of the post
   */
  public function tags()
  {
    return $this->belongsToMany(Tag::class, 'post_tag');
  }

  /**
   * Get comments on the post
   */
  public function comments()
  {
    return $this->hasMany(Comment::class);
  }
}
