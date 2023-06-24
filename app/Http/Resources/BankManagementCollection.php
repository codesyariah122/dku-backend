<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class BankManagementCollection extends ResourceCollection
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

        $banks = $collects->map(function($bank) {
            return $bank;
        });

        return [
            'success' => true,
            'message' => 'Bank lists !',
            'data' => $banks
        ];
    }

    public function withResponse($request, $response)
    {
        if ($this->collection->isEmpty()) {
            $response->setStatusCode(404);
        }
    }
}
