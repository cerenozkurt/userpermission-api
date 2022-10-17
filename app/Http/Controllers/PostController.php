<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostRequest;
use App\Http\Resources\PostResource;
use App\Http\Resources\UserResource;
use App\Models\Category;
use App\Models\CategoryPost;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use function PHPUnit\Framework\isEmpty;

class PostController extends ApiResponseController
{
    public function __construct()
    {
        $this->middleware('role:superadmin|admin|editor|writer', ['only' => ['create_post', 'delete_post', 'update_post']]);
        $this->middleware('role:superadmin|admin|editor', ['only' => ['awaiting_approve', 'approve_post', 'allposts_by_user']]);
    }

    //index herkes görür
    public function index()
    {
        $post = Post::where('state', '1')->orderby('created_at', 'desc')->paginate(15);
        if ($post) {
            return $this->apiResponse(true, 'Postlar listelendi.', 'posts', PostResource::collection($post), JsonResponse::HTTP_OK);
        }
        return  $this->apiResponse(false, 'Kayıtlı post bulunamamıştır.', null, null, JsonResponse::HTTP_NOT_FOUND);
    }

    //birtek postu getir herkes görür
    public function post_by_id($id)
    {
        $post = Post::find($id);
        if ($post->state == '1') {
            return $this->apiResponse(true, $id . " id'li post getirilmiştir.", 'post', new PostResource($post), JsonResponse::HTTP_OK);
        }
        return  $this->apiResponse(false, 'Post henüz daha yayınlanmamıştır.', null, null, JsonResponse::HTTP_NOT_FOUND);
    }

    //post ekle // admin/superadmin/editor/writer
    public function create_post(PostRequest $request)
    {
        $user = auth()->user();
        if (in_array('writer', $user->roles->pluck('name')->toarray())) { //kullanıcı writer ise önce denetlenmesi için state 0 olur
            $post = Post::create([
                'user_id' => $user->id,
                'title' => $request->title,
                'content' => $request->content,
                'like_count' => 0,
                'comment_count' => 0,
                'state' => '0',
            ]);
            $category = Category::find([$request->category_id]);
            $post->categories()->attach($category);
        } else {
            $post = Post::create([
                'user_id' => $user->id,
                'title' => $request->title,
                'content' => $request->content,
                'like_count' => 0,
                'comment_count' => 0,
                'state' => '1',
            ]);
            $category = Category::find([$request->category_id]);
            $post->categories()->attach($category); //posta kategoriyi ekler
        }
        if ($post) {
            return $this->apiResponse(true, 'Post eklendi.', 'post', new PostResource($post), JsonResponse::HTTP_OK);
        }
        return $this->apiResponse(false, 'Post eklenirken bir hata oluştu.', null, null, JsonResponse::HTTP_NOT_FOUND);
    }


    /* //posta kategori ekle
    public function post_add_to_category($id, PostRequest $request)
    {
        $post = Post::find($id);


        if (($post->categories()->wherePivot('category_id', $request->category_id)->first()) == null) { //boşsa oluştur
            $category = Category::find([$request->category_id]);
            $post->categories()->attach($category);

            return $this->apiResponse(true, Category::find($request->category_id)->name . ' kategorisine ' . $id . "id'li post eklendi.", null, null, JsonResponse::HTTP_OK);
        }
        return $this->apiResponse(false, 'Post kategoriye zaten eklidir.', null, null, JsonResponse::HTTP_NOT_FOUND);
    }*/

    //posta kategori ekle veya güncelle
    public function post_update_to_category($id, PostRequest $request)
    {
        $post = Post::find($id);

        $category_id = $request->category_id;
        $deger = explode(",", $category_id);

        switch (count($deger)) {
            case 1:
                if (Category::find($deger[0]) != null) {
                    $post->categories()->sync([$deger[0]]);
                    return $this->apiResponse(true, $post->categories[0]->name . ' isimli kategori ' . $id . " id'li posta eklendi.", null, null, JsonResponse::HTTP_OK);
                    break;
                }
                return $this->apiResponse(false, 'Category bulunamadı.', 'olmayan_category', $deger[0], JsonResponse::HTTP_NOT_FOUND);

            case 2:
                if (Category::find($deger[0]) != null) {
                    if (Category::find($deger[1]) != null) {
                        $post->categories()->sync([$deger[0], $deger[1]]);
                        return $this->apiResponse(true, $post->categories[0]->name . ' ve ' . $post->categories[1]->name . ' kategorileri ' . $id . " id'li posta eklendi.", null, null, JsonResponse::HTTP_OK);
                        break;
                    }
                    return $this->apiResponse(false, 'Category bulunamadı.', 'olmayan_category', $deger[1], JsonResponse::HTTP_NOT_FOUND);
                }
                return $this->apiResponse(false, 'Category bulunamadı.', 'olmayan_category', $deger[0], JsonResponse::HTTP_NOT_FOUND);


            case 3:
                if (Category::find($deger[0]) != null) {
                    if (Category::find($deger[1]) != null) {
                        if (Category::find($deger[2]) != null) {
                            $post->categories()->sync([$deger[0], $deger[1], $deger[2]]);
                            return $this->apiResponse(true, $post->categories[0]->name . ', ' . $post->categories[1]->name . ' ve ' . $post->categories[2]->name . ' kategorileri ' . $id . " id'li posta eklendi.", null, null, JsonResponse::HTTP_OK);
                            break;
                        }
                        return $this->apiResponse(false, 'Category bulunamadı.', 'olmayan_category', $deger[2], JsonResponse::HTTP_NOT_FOUND);
                    }
                    return $this->apiResponse(false, 'Category bulunamadı.', 'olmayan_category', $deger[1], JsonResponse::HTTP_NOT_FOUND);
                }
                return $this->apiResponse(false, 'Category bulunamadı.', 'olmayan_category', $deger[0], JsonResponse::HTTP_NOT_FOUND);

            default:
                return $this->apiResponse(false, 'En fazla 3 kategori ekleyebilirsiniz.', null, null, JsonResponse::HTTP_NOT_FOUND);
        }
    }


    //postun kategorilerini getir
    public function post_get_to_category($id)
    {
        $post = Post::find($id);
        if (!($post->categories->toarray())) { //category yoksa
            return $this->apiResponse(false, $id . " 'li postun kategorisi bulunamadı.", null, null, JsonResponse::HTTP_OK);
        }
        return $this->apiResponse(true, $id . " id'li postun kategorileri listelendi.", 'categories', $post->categories->pluck('name'), JsonResponse::HTTP_OK);
    }

    
    //posttan kategoriyi çıkart
    public function post_delete_to_category($id, PostRequest $request)
    {
        $post = Post::find($id);

        if (($post->categories()->wherePivot('category_id', $request->category_id)->first()) == null) { //boşsa uyarı
            return $this->apiResponse(false, 'Post ve kategori ilişkisi bulunmamaktadır.', null, null, JsonResponse::HTTP_NOT_FOUND);
        }
        $category = Category::find($request->category_id);
        $post->categories()->detach($category);

        return $this->apiResponse(true, 'Kategori posttan kaldırılmıştır.', null, null, JsonResponse::HTTP_NOT_FOUND);
    }

    //onay bekleyen postlar listesi
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

    //post sil
    //writer kendi postunu silebilir
    //editör kendi ve writerın postunu silebilir
    //admin kendi, editor ve writer postunu silebilir
    //superadmin hepsini silebilir.
    public function delete_post($id)
    {
        $user = auth()->user();
        $userroles = $user->roles->pluck('name')->toarray();
        $post = Post::find($id);
        $postuserroles =  $post->users->roles->pluck('name')->toarray();
        switch ($userroles) {
            case in_array('superadmin', $userroles):
                $delete = $post->delete();
                break;
            case in_array('admin', $userroles):
                if ($user->id != $post->users->id && (in_array('superadmin', $postuserroles) || in_array('admin', $postuserroles))) {
                    return $this->apiResponse(false, 'Bu postu silmeye yetkiniz yok.', null, null, JsonResponse::HTTP_FORBIDDEN);
                }
                $delete = $post->delete();
                break;
            case in_array('editor', $userroles):
                if ($user->id != $post->users->id && (in_array('superadmin', $postuserroles) || in_array('admin', $postuserroles) || in_array('editor', $postuserroles))) {
                    return $this->apiResponse(false, 'Bu postu silmeye yetkiniz yok.', null, null, JsonResponse::HTTP_FORBIDDEN);
                }
                $delete = $post->delete();
                break;
            default:
                if ($post->users->id == $user->id) {
                    $delete = $post->delete();
                }
                return $this->apiResponse(false, 'Bu postu silmeye yetkiniz yok.', null, null, JsonResponse::HTTP_FORBIDDEN);
        }
        if ($delete) {
            return $this->apiResponse(true, 'Post silindi.', 'deleted_post', new PostResource($post), JsonResponse::HTTP_OK);
        }
        return $this->apiResponse(false, 'Post silinirken bir hata oluştu.', null, null, JsonResponse::HTTP_NOT_FOUND);
    }



    //herkes sadece kendi postunu düzenleyebilir
    public function update_post($id, PostRequest $request)
    {
        $user = auth()->user();
        $post = Post::find($id);

        if ($user->id == $post->users->id) {
            $post->title = $request->title ?? $post->title;
            $post->content = $request->content ?? $post->content;
            $post->save();

            return $this->apiResponse(true, 'Postunuz güncellendi.', 'post', new PostResource($post), JsonResponse::HTTP_OK);
        }
        return $this->apiResponse(false, 'Yetkisiz Giriş.', null, null, JsonResponse::HTTP_FORBIDDEN);
    }

    //giriş yapmış kullanıcının postları
    public function my_posts()
    {
        $user = auth()->user();

        $posts = Post::where('user_id', $user->id)->orderby('created_at')->get();

        if ($posts->count() != 0) {
            return $this->apiResponse(true, 'Postlarınız listelendi.', 'posts', PostResource::collection($posts), JsonResponse::HTTP_OK);
        }
        return  $this->apiResponse(false, 'Postunuz bulunamamıştır.', null, null, JsonResponse::HTTP_NOT_FOUND);
    }

    //idsi  girilen kullanıcının postları /herkes görür
    public function post_by_user($id)
    {
        $posts = Post::where('user_id', $id)->where('state', '1')->orderby('created_at')->get();

        if ($posts->count() != 0) {
            return $this->apiResponse(true, $id . " id'li kullanıcın postları listelendi.", 'posts', PostResource::collection($posts), JsonResponse::HTTP_OK);
        }
        return  $this->apiResponse(false, 'Post bulunamamıştır.', null, null, JsonResponse::HTTP_NOT_FOUND);
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
}
