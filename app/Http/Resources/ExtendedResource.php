<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExtendedResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return parent::toArray($request);
    }

    public function __construct($resource,$success=true,$errors=[]) {
        if (!is_array($resource) && !method_exists ($resource,'toArray')) $resource = [$resource];
        parent::__construct($resource);

        $this->additional([
          'success' => $success,
          'errorTexts' => $errors
        ]);
    }

    public function additional($data) {
      $this->additional = array_merge($this->additional,$data);

      return $this;
    }
}
