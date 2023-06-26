<?php

namespace App\Http\Resources;

use Illuminate\Support\Collection;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DonationManagementCollection extends ResourceCollection
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

        $donations = $collects->map(function($donation) {
            return $donation;
        });

        return [
            'success' => true,
            'message' => 'Donation data lists !',
            'data' => $donations
        ];
    }

    public function withResponse($request, $response)
    {
        if ($this->collection->isEmpty()) {
            $response->setStatusCode(404);
        }
    }
}
