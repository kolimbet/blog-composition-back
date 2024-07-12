<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
  use HasFactory;

  protected $fillable = [
    'user_id',
    'post_id',
    'text_raw',
    'text_html',
    'is_published',
    'published_at',
    'is_checked',
    'deleted_at',
    'deleted_by',
  ];

  /**
   * Get the author of the comment
   */
  public function user()
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Get a post to which a comment has been written
   */
  public function post()
  {
    return $this->belongsTo(Post::class);
  }

  /**
   * Get the admin who deleted the message
   */
  public function deletedBy()
  {
    return $this->belongsTo(User::class, 'id', 'deleted_by');
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
