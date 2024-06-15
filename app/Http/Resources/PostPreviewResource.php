<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PostPreviewResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
   */
  public function toArray($request)
  {
    $result = parent::toArray($request);
    $result['author'] = new UserResource($this->user);
    $likes = $this->likes;
    $result['likes'] = $likes && $likes->count() ? $likes : [];
    return $result;
  }
}
