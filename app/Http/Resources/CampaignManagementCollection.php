<?php

namespace App\Http\Resources;
use Illuminate\Support\Collection;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CampaignManagementCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     * @author Puji Ermanto <pujiermanto@gmail.com>
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $collects = collect($this->collection);

        $campaigns = $collects->map(function($campaign) {
            return $campaign;
        });

        return [
            'success' => true,
            'message' => 'Campaign lists !',
            'data' => $campaigns
        ];
    }

    public function withResponse($request, $response)
    {
        if ($this->collection->isEmpty()) {
            $response->setStatusCode(404);
        }
    }
}
