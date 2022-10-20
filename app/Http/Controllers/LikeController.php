<?php

namespace App\Http\Controllers;

use App\Http\Resources\LikeResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\UserResource;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LikeController extends ApiResponseController
{

    public function __construct()
    {
        $this->middleware('permission:like.edit', ['only' => ['store','destroy']]);
        //$this->middleware('permission:like.view', ['only' => ['index','most_liked']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    //herkes
    public function index($post)
    {
        $likes = Like::has('post')->where('post_id', $post)->get();    //postu beğenen kişiler
        $users_id = $likes->pluck('user_id')->toarray(); 
        $users = User::wherein('id',$users_id)->get();  //postu beğenen kullanıcıların listesi

        $data = [
            'post_id' => $post,
            'users' => $users
        ];
        return $this->apiResponse(true, 'Postu beğenenlerin listesi.', 'likes',new LikeResource($data),JsonResponse::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    //superadmin/admin/editor/writer/user/
    public function store(Request $request, $post_id)
    {
        $user = auth()->user();
        $post = Post::find($post_id);

        if (Like::where('user_id', $user->id)->where('post_id', $post_id)->first() == null) {
            $like = Like::create([
                'user_id' => $user->id,
                'post_id' => $post_id
            ]);

            $post->like_count = $post->like_count + 1;
            $post->save();

            if ($like) {
                return $this->apiResponse(true, 'Post beğenildi.', null, null, JsonResponse::HTTP_OK);
            }
            return $this->apiResponse(false, 'Post beğenilirken bir hata oluştu.', null, null, JsonResponse::HTTP_NOT_FOUND);
        }
        return $this->apiResponse(false, 'Post zaten beğenilmiş.', null, null, JsonResponse::HTTP_NOT_FOUND);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

     //herkes
    public function most_liked()
    {
        $most = Post::where('state','1')->orderby('like_count','desc')->get(); //postları beğeni sırasına göre sıralar
        return $this->apiResponse(true, 'En çok beğenilen postlar.','posts',PostResource::collection($most),JsonResponse::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $like = Like::find($id);
        $user = auth()->user();
        if ($like->user_id == $user->id) {
            $like->delete();

            $post = Post::find($like->post->id);
            $post->like_count = $post->like_count - 1;
            $post->save();

            return $this->apiResponse(true, 'Like geri alındı.', null, null, JsonResponse::HTTP_OK);
        }
        return $this->apiResponse(false, 'Yetkisiz işlem.', null, null, JsonResponse::HTTP_FORBIDDEN);
    }
}
