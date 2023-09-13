<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreImageRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   *
   * @return bool
   */
  public function authorize()
  {
    return true;
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, mixed>
   */
  public function rules()
  {
    return [
      'image' => 'image|max:1024|mimes:png,jpg,webp',
      'image_name' => ['required', 'string', 'max:40'],
      'post_id' => 'sometimes|integer',
    ];
  }
}