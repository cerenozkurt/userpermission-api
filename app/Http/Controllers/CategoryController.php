<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends ApiResponseController
{

    public function _construct()
    {
        $this->middleware('role:superadmin|admin|editor', ['only' => ['create_category', 'delete_category', 'update_category']]);
    }

    //kategorileri listele //herkes
    public function index()
    {
        $categories = Category::orderby('name')->get();
        if ($categories) {
            return $this->apiResponse(true, 'Tüm kategoriler listelendi.', 'categories', CategoryResource::collection($categories), JsonResponse::HTTP_OK);
        }
        return $this->apiResponse(false, 'Kayıtlı kategori yoktur.', null, null, JsonResponse::HTTP_NOT_FOUND);
    }

    //kategori ekle /superadmin/admin/editor
    public function create_category(CategoryRequest $request)
    {
        $category = Category::create([
            'name' => $request->name
        ]);
        if ($category) {
            return $this->apiResponse(true, "Kategori eklendi.", 'category', new CategoryResource($category), JsonResponse::HTTP_OK);
        }
        return $this->apiResponse(false, 'Kategori eklenemedi.', null, null, JsonResponse::HTTP_NOT_FOUND);
    }

    //kategori sil / superadmin/admin/editor
    public function delete_category($id)
    {
        $category = Category::find($id);
        $delete = $category->delete();

        if ($category) {
            return $this->apiResponse(true, "Kategori silindi.", 'deleted_category', new CategoryResource($category), JsonResponse::HTTP_OK);
        }
        return $this->apiResponse(false, 'Kategori silinemedi.', null, null, JsonResponse::HTTP_NOT_FOUND);
    }

    //kategori güncelle /superadmin/admin/editor
    public function update_category($id, CategoryRequest $request)
    {
        $category = Category::find($id);

        if (Category::where('name', $request->name)->first() == null || $request->name == $category->name) {
            $category->name = $request->name ?? $category->name;
            $category->save();

            if ($category) {
                return $this->apiResponse(true, "Kategori güncellendi.", 'updated_category', new CategoryResource($category), JsonResponse::HTTP_OK);
            }
            return $this->apiResponse(false, 'Kategori güncellenemedi.', null, null, JsonResponse::HTTP_NOT_FOUND);
        }
        return $this->apiResponse(false, 'Kategori ismi zaten mevcuttur. Başka isim deneyiniz.', null, null, JsonResponse::HTTP_NOT_FOUND);
    }
}
