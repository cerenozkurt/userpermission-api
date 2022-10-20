<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentRequest;
use App\Http\Resources\CommentResource;
use App\Http\Resources\PostResource;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class CommentController extends ApiResponseController
{

    public function __construct()
    {
        $this->middleware('permission:comment.edit',['only'=> ['store','update']]);

        $this->middleware('permission:comment.delete',['only' => ['destroy']]);
        //$this->middleware('permission:comment.view',['only'=>['show','comments_of_post','comments_of_user']]);
    }


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
    public function store($post_id, CommentRequest $request)
    {
        $user = auth()->user();
        $comment = Comment::create([
            'user_id' => $user->id,
            'post_id' => $post_id,
            'comment' => $request->comment

        ]);

        $post =  Post::find($post_id);
        $post->comment_count = $post->comment_count + 1;
        $post->save();

        if ($comment) {
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
        //yorum benimse güncelle
        $user = auth()->user();
        $comment = Comment::find($id);
        $roles = $user->roles->pluck('name')->toarray();

        if ($comment->user_id == $user->id) { //yorum kullanıcının kendi yorumuysa veya kullanıcı admin/superadmin/editorse
            $comment->comment = $request->comment ?? $comment->comment;
            $comment->save();

            return $this->apiResponse(true, 'Yorum güncellendi.', 'comment', new CommentResource($comment), JsonResponse::HTTP_OK);
        }
        return $this->apiResponse(false, 'Yetkisiz işlem.', null, null, JsonResponse::HTTP_FORBIDDEN);
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
        $post_id = $comment->post_id;

        $user = auth()->user();

        $roles = $user->roles->pluck('name')->toarray();

        if ($comment->user_id == $user->id || in_array('superadmin', $roles) || in_array('admin', $roles) || in_array('editor', $roles)) { //yorum kullanıcının kendi yorumuysa veya kullanıcı admin/superadmin/editorse
            $delete = $comment->delete();
            $post =  Post::find($post_id);
            $post->comment_count = $post->comment_count - 1;
            $post->save();

            if ($delete) {
                return $this->apiResponse(true, 'Yorum silindi.', null, null, JsonResponse::HTTP_OK);
            }
            return $this->apiResponse(false, 'Yorum silinirken bir hata oluştu.', null, null, JsonResponse::HTTP_NOT_FOUND);
        }

        return $this->apiResponse(false, 'Yetkisiz işlem.', null, null, JsonResponse::HTTP_FORBIDDEN);
    }


    //postun yorumları
    public function comments_of_post($post_id)
    {
        $post = Post::find($post_id);
        if (!($post->comments->toarray())) {
            return $this->apiResponse(false, 'Bu posta ait yorum bulunamadı.', 'post', new PostResource($post), JsonResponse::HTTP_NOT_FOUND);
        }
        return $this->apiResponse(true, 'Postun yorumları.', 'comments', CommentResource::collection($post->comments), JsonResponse::HTTP_OK);
    }


    //kullanının yorumları
    public function comments_of_user($user_id)
    {
        $user = User::find($user_id);
        $comments = Comment::where('user_id', $user_id)->orderby('created_at','desc')->get();
        if (!($comments->toarray())) {
            return $this->apiResponse(false, 'Bu kullanıcıya ait yorum bulunamadı.', 'user', null, JsonResponse::HTTP_NOT_FOUND);
        }
        return $this->apiResponse(true, 'Kullanıcının yorumları.', 'comments', CommentResource::collection($comments), JsonResponse::HTTP_OK);
    }
}
