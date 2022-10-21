<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostRequest;
use App\Http\Resources\PostResource;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends ApiResponseController
{
    public function __construct()
    {

        $this->middleware('permission:post.create', ['only'  => 'create']);
        $this->middleware('permission:post.update', ['only'  => 'update']);
        $this->middleware('permission:post.delete', ['only' => 'delete']);
        $this->middleware('permission:post.update.category', ['only' => 'post_update_to_category']);
        $this->middleware('permission:post.delete.category ', ['only' => 'post_delete_to_category']);
        $this->middleware('permission:post.awaiting.approve', ['only'  => 'awaiting_approve']);
        $this->middleware('permission:post.approve', ['only'  => 'approve_post']);
        $this->middleware('permission:post.allpost.by.user', ['only' => 'allposts_by_user']);


        // $this->middleware('role:superadmin|admin|editor|writer', ['only' => ['create_post', 'delete_post', 'update_post', 'post_update_to_category','post_delete_to_category']]);
        // $this->middleware('role:superadmin|admin|editor', ['only' => ['awaiting_approve', 'approve_post', 'allposts_by_user']]);

        // 
        //$this->middleware('permission:post.edit', ['only' => ['create_post','post_update_to_category', 'post_delete_to_category', 'delete_post', 'update_post']]);
        $this->middleware('permission:post.approve', ['only' => ['awaiting_approve', 'approve_post', 'allposts_by_user']]);

        //$this->middleware('permission:post.view',['only' => ['index','post_by_id','post_get_to_category','post_by_user']]);
    }


    //policy
    //post ekle  admin/superadmin/editor/writer
    public function create(PostRequest $request)
    {

        $user = auth()->user();

        // policy
        if ($request->user()->cannot('post-create', $user)) {
            $post = Post::create([
                'user_id' => $user->id,
                'title' => $request->title,
                'content' => $request->content,
                'like_count' => 0,
                'comment_count' => 0,
                'state' => '1' //onaylanmış olur
            ]);
            $category = Category::find([$request->category_id]);
            $post->categories()->attach($category); //posta kategoriyi ekler
        } else {
            $post = Post::create([
                'user_id' => $user->id,
                'title' => $request->title,
                'content' => $request->content,
                'like_count' => 0,
                'comment_count' => 0,
                'state' => '0',
            ]);
            $category = Category::find([$request->category_id]);
            $post->categories()->attach($category);  //posta kategoriyi ekler
        }
        if ($post) {
            return $this->apiResponse(true, 'Post eklendi.', 'post', new PostResource($post), JsonResponse::HTTP_OK);
        }
        return $this->apiResponse(false, 'Post eklenirken bir hata oluştu.', null, null, JsonResponse::HTTP_NOT_FOUND);
    }


    //herkes sadece kendi postunu düzenleyebilir
    public function update($id, PostRequest $request)
    {
        $post = Post::find($id);

        if ($request->user()->can('post-update', $post)) {
            $post->title = $request->title ?? $post->title;
            $post->content = $request->content ?? $post->content;
            $post->save();

            return $this->apiResponse(true, 'Postunuz güncellendi.', 'post', new PostResource($post), JsonResponse::HTTP_OK);
        }

        return $this->apiResponse(false, 'Yetkisiz Giriş.', null, null, JsonResponse::HTTP_FORBIDDEN);
    }


    //policy
    //post sil
    //writer kendi postunu silebilir
    //editör kendi ve writerın postunu silebilir
    //admin kendi, editor ve writer postunu silebilir
    //superadmin hepsini silebilir.
    public function delete($id, Request $request)
    {
        $user = auth()->user();
        $post = Post::find($id);

        if ($request->user()->can('post-delete', $post)) {
            $delete = $post->delete();
            if ($delete) {
                return $this->apiResponse(true, 'Post silindi.', 'deleted_post', new PostResource($post), JsonResponse::HTTP_OK);
            }
            return $this->apiResponse(false, 'Post silinirken bir hata oluştu.', null, null, JsonResponse::HTTP_NOT_FOUND);
        }
        return $this->apiResponse(false, 'Yetkisiz Giriş.', null, null, JsonResponse::HTTP_FORBIDDEN);
    }


    //DÜZENLENEBİLİR
    //posta kategori ekle veya güncelle //s.admin,admin, editor ve kendi yazısı olan writer
    public function post_update_to_category($id, PostRequest $request)
    {
        $post = Post::find($id);
        $user = auth()->user();


        if ($request->user()->can('post-gradualpermission', $post)) {

            $category = Category::find($request->category_id);
            $post->categories()->sync($category);

            return $this->apiResponse(true, 'Kategori posta eklenmiştir.', null, null, JsonResponse::HTTP_NOT_FOUND);
        }
        return $this->apiResponse(false, 'Yetkisiz Giriş.', null, null, JsonResponse::HTTP_FORBIDDEN);
    }

    //posttan kategoriyi çıkart // editor ve kendi yazısı olan writer
    public function post_delete_to_category($id, PostRequest $request)
    {
        $post = Post::find($id);
        $user = auth()->user();

        //kendi postu mu  veya  post.edit yetkisi var mı
        if ($request->user->can('ownpostcontrol', $user) || $request->user()->can('post.edit')) {
            if (($post->categories()->wherePivot('category_id', $request->category_id)->first()) == null) { //boşsa uyarı
                return $this->apiResponse(false, 'Post ve kategori ilişkisi bulunmamaktadır.', null, null, JsonResponse::HTTP_NOT_FOUND);
            }
            $category = Category::find($request->category_id);
            $post->categories()->detach($category);

            return $this->apiResponse(true, 'Kategori posttan kaldırılmıştır.', null, null, JsonResponse::HTTP_NOT_FOUND);
        }
    }


    //onay bekleyen postlar listesi //admin,superadmin,editor
    public function awaiting_approve()
    {
        $posts = Post::where('state', '0')->orderby('created_at')->get();
        if ($posts) {
            return $this->apiResponse(true, 'Onay bekleyen postlar.', 'posts', PostResource::collection($posts), JsonResponse::HTTP_OK);
        }
        return $this->apiResponse(false, 'Onay bekleyen post bulunamamıştır.', null, null, JsonResponse::HTTP_NOT_FOUND);
    }

    //postu onayla admin/superadmin/editor
    public function approve_post($id)
    {
        $post = Post::find($id);
        $post->state = '1';
        $post->save();
        return $this->apiResponse(true, 'Post onaylanmıştır.', 'posts',  new PostResource($post), JsonResponse::HTTP_OK);
    }

     //kullanıcının yayında olmayanlar dahil tüm postları // admin/superadmin/editor
     public function allposts_by_user($id)
     {
         $posts = Post::where('user_id', $id)->orderby('created_at')->get();
 
         if ($posts->count() != 0) {
             return $this->apiResponse(true, $id . " id'li kullanıcın postları listelendi.", 'posts', PostResource::collection($posts), JsonResponse::HTTP_OK);
         }
         return  $this->apiResponse(false, 'Post bulunamamıştır.', null, null, JsonResponse::HTTP_NOT_FOUND);
     }




    //giriş yapmış kullanıcının postları //user
    public function my_posts()
    {
        $user = auth()->user();

        $posts = Post::where('user_id', $user->id)->orderby('created_at')->get();

        if ($posts->count() != 0) {
            return $this->apiResponse(true, 'Postlarınız listelendi.', 'posts', PostResource::collection($posts), JsonResponse::HTTP_OK);
        }
        return  $this->apiResponse(false, 'Postunuz bulunamamıştır.', null, null, JsonResponse::HTTP_NOT_FOUND);
    }




    //HERKES 




    //tüm postlar / herkes 
    public function index()
    {
        $post = Post::where('state', '1')->orderby('created_at', 'desc')->paginate(15);  //onaylanmış postları yani state1 olanları getirir
        if ($post) {
            return $this->apiResponse(true, 'Postlar listelendi.', 'posts', PostResource::collection($post), JsonResponse::HTTP_OK);
        }
        return  $this->apiResponse(false, 'Kayıtlı post bulunamamıştır.', null, null, JsonResponse::HTTP_NOT_FOUND);
    }

    //birtek postu getir / herkes 
    public function post_by_id($post)
    {
        $posts = Post::find($post);
        return $this->apiResponse(true, $post . " id'li post getirilmiştir.", 'post', new PostResource($posts), JsonResponse::HTTP_OK);
    }
    //postun kategorilerini getir // herkes
    public function post_get_to_category($id)
    {
        $post = Post::find($id);
        if (!($post->categories->toarray())) { //category yoksa
            return $this->apiResponse(false, $id . " 'li postun kategorisi bulunamadı.", null, null, JsonResponse::HTTP_OK);
        }
        return $this->apiResponse(true, $id . " id'li postun kategorileri listelendi.", 'categories', $post->categories->pluck('name'), JsonResponse::HTTP_OK);
    }

    //idsi  girilen kullanıcının postları, sadece onaylanmış postlar görülür //herkes görür
    public function post_by_user($id)
    {
        $posts = Post::where('user_id', $id)->where('state', '1')->orderby('created_at')->get();

        if ($posts->count() != 0) {
            return $this->apiResponse(true, $id . " id'li kullanıcın postları listelendi.", 'posts', PostResource::collection($posts), JsonResponse::HTTP_OK);
        }
        return  $this->apiResponse(false, 'Post bulunamamıştır.', null, null, JsonResponse::HTTP_NOT_FOUND);
    }


   
}
