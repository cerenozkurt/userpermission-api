<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentRequest;
use App\Http\Resources\CommentResource;
use App\Http\Resources\PostResource;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends ApiResponseController
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
       //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store($post, CommentRequest $request)
    {
        $user = auth()->user();
        $comment = Comment::create([
            'user_id' => $user->id,
            'post_id'=> $post, 
            'comment' => $request->comment

        ]);
        
        if($comment){
            return $this->apiResponse(true, 'Yorum paylaşıldı.', 'comment', new CommentResource($comment), JsonResponse::HTTP_OK);
        }
        return $this->apiResponse(false, 'Yorum paylaşılırken  bir hata oluştu.', null, null, JsonResponse::HTTP_NOT_FOUND);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $comment = Comment::find($id);
        return $this->apiResponse(true, 'Yorum görüntüleniyor.', 'comment', new CommentResource($comment), JsonResponse::HTTP_OK);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(CommentRequest $request, $id)
    {
        $comment = Comment::find($id);
        $comment->comment = $request->comment ?? $comment->comment;
        $comment->save();

        return $this->apiResponse(true, 'Yorum güncellendi.', 'comment', new CommentResource($comment), JsonResponse::HTTP_OK);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $comment = Comment::find($id);
        $delete = $comment->delete();

        if($delete){
            return $this->apiResponse(true, 'Yorum silindi.', null, null, JsonResponse::HTTP_OK);
        }
        return $this->apiResponse(false, 'Yorum silinirken bir hata oluştu.', null, null, JsonResponse::HTTP_NOT_FOUND);
    }


    //postun yorumları
    public function comments_of_post($post_id)
    {
        $post = Post::find($post_id);
        if (!($post->comments->toarray())){
            return $this->apiResponse(false, 'Bu posta ait yorum bulunamadı.', 'post', new PostResource($post), JsonResponse::HTTP_NOT_FOUND);
        }
        return $this->apiResponse(true, 'Postun yorumları.', 'comments', CommentResource::collection($post->comments),JsonResponse::HTTP_OK);
    }


    //kullanının yorumları
    public function comments_of_user($user_id)
    {
        $user = User::find($user_id);
        $comments = Comment::where('user_id', $user_id)->get();
        if(!($comments->toarray())){
            return $this->apiResponse(false, 'Bu kullanıcıya ait yorum bulunamadı.','user',null, JsonResponse::HTTP_NOT_FOUND);
        }
        return $this->apiResponse(true, 'Kullanıcının yorumları.', 'comments', CommentResource::collection($comments),JsonResponse::HTTP_OK);
    }
    
}
