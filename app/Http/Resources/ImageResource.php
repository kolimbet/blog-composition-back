<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ImageResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
   */
  public function toArray($request)
  {
    return [
      'id' => $this->id,
      'user_id' => $this->user_id,
      'post_image' => $this->post_image,
      'post_id' => $this->post_id,
      'path' => $this->path,
      'name' => $this->name,
      'mime_type' => $this->mime_type,
      'full_url' => config('app.url') . "/storage/{$this->path}/{$this->name}",
    ];
  }
}