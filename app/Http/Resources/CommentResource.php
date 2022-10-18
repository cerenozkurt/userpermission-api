<?php

namespace App\Http\Resources;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
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
            'comment' =>  $this->comment,
            'user' => new UserResource(User::find($this->user_id)),
            'post' => $this->when($request->route()->getActionMethod() != 'comments_of_post', new PostResource(Post::find($this->post_id))) //comments_of_post metodunda post değişkenini gizle
        ];
    }
}
