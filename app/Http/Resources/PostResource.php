<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return[
            'id' => $this->id,
            'state' => $this->state == 0 ? 'Onay Bekliyor..':'Aktif.',
            'categories' =>$this->categories->pluck('name') ,
            'title' => $this->title,
            'content' => $this->content,
            'like_count' => $this->like_count,
            'comment_count' => $this->comment_count,
            'created_time' => $this->created_at->format('Y-m-d'),
            'user' => new UserResource($this->users)
        ];
    }
}
