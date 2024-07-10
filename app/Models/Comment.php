<?php

namespace App\Models;

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
}