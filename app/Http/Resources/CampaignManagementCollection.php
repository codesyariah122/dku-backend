<?php

namespace App\Http\Resources;

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
        $campaigns = collect($this->collection);
        
        return [
            'success' => true,
            'message' => 'Campaign lists !',
            'data' => $campaigns
        ];
    }
}
