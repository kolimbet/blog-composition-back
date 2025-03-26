<?php

namespace App\Http\Resources;

use DB;
use Illuminate\Http\Resources\Json\JsonResource;
use Log;

class UserResource extends JsonResource
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
    $result['avatar'] = $this->avatar_id ? new ImageResource($this->avatar) : null;
    return $result;
  }
}