<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends ApiResponseController
{
    public function __construct()
    {
        $this->middleware('role:superadmin', ['only' => ['delete_user']]);
        $this->middleware('role:superadmin,admin', ['only' => ['index', 'create_user', 'update_user']]);
    }

    //tüm kullanıcıları listeler/ admin ve superadmin
    public function index()
    {
        $users = User::orderby('name')->get();
        if ($users) {
            return $this->apiResponse(true, 'Kullanıcılar listelendi.', 'users', UserResource::collection($users), JsonResponse::HTTP_OK);
        }
        return $this->apiResponse(false, 'Kayıtlı kullanıcı yoktur.', null, null, JsonResponse::HTTP_NOT_FOUND);
    }

    //kullanıcı ekle / superadmin admin
    public function create_user(AuthRequest $request)
    {
        //kullanıcı admin veya superadminse işlem yap
        $authcontroller = app('App\Http\Controllers\AuthController');
        return $authcontroller->create_user($request);
    }

    //kullanıcı sil / superadmin
    public function delete_user($id)
    {
        $user = User::find($id);
        if (in_array('superadmin', $user->roles->pluck('name')->toarray())) { //kullanıcı superadminse silinemez
            return $this->apiResponse(false, 'Bu kullanıcı silinemez.', null, null, JsonResponse::HTTP_FORBIDDEN);
        }
        $delete = $user->delete();

        if ($delete) {
            return $this->apiResponse(true, 'Kullanıcı başarıyla silindi.', 'deleted_user', $user, JsonResponse::HTTP_OK);
        }
        return $this->apiResponse(false, 'Kullanıcı silinemedi.', 'user', $user, JsonResponse::HTTP_NOT_FOUND);
    }

    //kullanıcı güncelle / superadmin admin
    public function update_user($id, AuthRequest $request)
    {
        $user = User::find($id);
        $authuserroles = auth()->user()->roles->pluck('name')->toarray();

        //admin kullanıcısının işlemleri
        if (in_array('admin', $authuserroles)) {
            $userroles = $user->roles->pluck('name')->toarray();

            //kullanıcı superadmin veya adminse güncelleme işlemi gerçekleştirilemez
            if (in_array('superadmin', $userroles) || in_array('admin', $userroles)) {
                return $this->apiResponse(false, 'Bu kullanıcıyı güncellemeye yetkiniz yok.', null, null, JsonResponse::HTTP_FORBIDDEN);
            }
            if (User::where('email', $request->email)->first() == null || $user->email == $request->email) {
                $user->name = $request->name ?? $user->name;
                $user->email = $request->email ?? $user->email;
                $user->password = Hash::make($request->password) ?? $user->password;
                $user->save();

                if ($user) {
                    return $this->apiResponse(true, 'Kullanıcı Başarıyla Güncellendi!', 'user', new UserResource($user), JsonResponse::HTTP_CREATED);
                }
                return $this->apiResponse(false, 'Kullanıcı güncellenirken bir hata gerçekleşti!', null, null,  JsonResponse::HTTP_NOT_FOUND);
            }
            return $this->apiResponse(false, 'email zaten kayıtlıdır.', null, null,  JsonResponse::HTTP_NOT_FOUND);
        }

        //superadmin kullanıcısının işlemleri
        if (User::where('email', $request->email)->first() == null || $user->email == $request->email) {
            $user->name = $request->name ?? $user->name;
            $user->email = $request->email ?? $user->email;
            $user->password = Hash::make($request->password) ?? $user->password;
            $user->save();

            if ($user) {
                return $this->apiResponse(true, 'Kullanıcı Başarıyla Güncellendi!', 'user', new UserResource($user), JsonResponse::HTTP_CREATED);
            }
            return $this->apiResponse(false, 'Kullanıcı güncellenirken bir hata gerçekleşti!', null, null,  JsonResponse::HTTP_NOT_FOUND);
        }
        return $this->apiResponse(false, 'email zaten kayıtlıdır.', null, null,  JsonResponse::HTTP_NOT_FOUND);
    }

    
}
