<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class NominalManagementCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $collects = collect($this->collection);

        $nominals = $collects->map(function($nominal) {
            return $nominal;
        });

        return [
            'success' => true,
            'message' => 'List of nominal donation !',
            'data' => $nominals
        ];
    }

    public function withResponse($request, $response)
    {
        if ($this->collection->isEmpty()) {
            $response->setStatusCode(404);
        }
    }
}
