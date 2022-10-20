<?php

namespace App\Http\Resources;

use App\Models\Post;
use App\Models\User;
use Carbon\Carbon;
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
            'id' => $this->id,
            'comment' =>  $this->comment,
            'user' => new UserResource(User::find($this->user_id)),
            'time' => $this->time($this->created_at),
            'post' => $this->when($request->route()->getActionMethod() != 'comments_of_post', new PostResource(Post::find($this->post_id))) //comments_of_post metodunda post değişkenini gizle
        ];
    }

    public function time($time)
    {

        $simdiki_tarih = Carbon::now();
        $ileriki_tarih = $time;
        $saniye_farki = $simdiki_tarih -> diffInSeconds($ileriki_tarih, false);
        $dakika_farki = $simdiki_tarih->diffInMinutes($ileriki_tarih, false);
        $saat_farki   = $simdiki_tarih->diffInHours($ileriki_tarih, false);
        $gun_farki    = $simdiki_tarih->diffInDays($ileriki_tarih, false);
        $ay_farki     = $simdiki_tarih->diffInMonths($ileriki_tarih, false);
        $yil_farki    = $simdiki_tarih->diffInYears($ileriki_tarih, false);

        if (abs($saniye_farki) < 60) {
            return 'Now';
        } elseif (abs($dakika_farki) < 60) {
            return abs($dakika_farki).'min';
        } elseif (abs($saat_farki) < 24) {
            return abs($saat_farki) . 'h';
        } elseif (abs($gun_farki) < 31) {
            return abs($gun_farki) . 'd';
        } elseif (abs($ay_farki) < 12) {
            return abs($ay_farki) . 'm';
        }
        return abs($yil_farki) . 'y';
    }
}
