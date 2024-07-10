<?php

namespace App\Http\Resources;

class CommentPaginatedCollection extends PaginatedCollection
{
  /**
   * The resource that this resource collects.
   *
   * @var string
   */
  public $collects = CommentResource::class;

  /**
   * Transform the resource collection into an array.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
   */
  public function toArray($request)
  {
    return parent::toArray($request);
  }
}
