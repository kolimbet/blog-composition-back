<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Log;

class PaginatedCollection extends ResourceCollection
{
  /**
   * Pagination Parameters
   *
   * @var array
   */
  public $paginationParams = [];

  /**
   * Pagination Links
   *
   * @var array
   */
  public $paginationLinks = [];

  /**
   * Create a new resource instance.
   *
   * @param  mixed  $resource
   * @return void
   */
  public function __construct($resource)
  {
    $this->paginationParams = [
      'current_page' => $resource->currentPage(),
      'last_page' => $resource->lastPage(),

      'per_page' => $resource->perPage(),
      'from' => $resource->firstItem(),
      'to' => $resource->lastItem(),
      'total' => $resource->total(),

      'path' => $resource->path(),
    ];

    $this->paginationLinks = [
      'first_page_url' => $resource->url(1),
      'prev_page_url' => $resource->previousPageUrl(),
      'next_page_url' => $resource->nextPageUrl(),
      'last_page_url' => $resource->url($resource->lastPage()),
    ];

    $resource = $resource->getCollection();

    parent::__construct($resource);
  }

  /**
   * Transform the resource collection into an array.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
   */
  public function toArray($request)
  {
    return [
      'data' => $this->collection,
      'pagination' => $this->paginationParams,
      'links' => $this->paginationLinks,
    ];
  }
}
