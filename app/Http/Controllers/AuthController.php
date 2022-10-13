<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends ApiResponseController
{
    public function all_user()
    {
        try {
            $users = User::orderby('name')->get();
            
            if ($users) {
                return $this->apiResponse(true, 'Users List', 'users', UserResource::collection(User::all()), JsonResponse::HTTP_OK);
            }
            return $this->apiResponse(false, 'No registered users.', null, null, JsonResponse::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
        }
    }

    public function register(AuthRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        //yeni kayıt olan her kullanıcıya user rolünü atarız.
        $user->assignRole('user');

        if ($user) {
            return $this->apiResponse(true, 'Kullanıcı Başarıyla Kaydoldu!', 'user', new UserResource($user), JsonResponse::HTTP_CREATED);
        }
        return $this->apiResponse(false, 'Kullanıcı kaydolurken bir hata gerçekleşti!', null, null,  JsonResponse::HTTP_UNAUTHORIZED);
    }

    public function login(AuthRequest $request)
    {
        try {
            $user = User::where('email',$request->email)->first();
            if (!$user) {
                return $this->apiResponse(false, 'Geçersiz email.', null, null, JsonResponse::HTTP_NOT_FOUND);
            }
            if (!Hash::check($request->password, $user->password)) {
                return $this->apiResponse(false, 'Geçersiz şifre.', null, null, JsonResponse::HTTP_NOT_FOUND);
            }
            $token = $user->createToken('token')->plainTextToken;

            return $this->apiResponse(true, 'Giriş Başarılı!', 'token', $token, JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return $this->apiResponse(false, $e->getMessage(), null, null, JsonResponse::HTTP_NOT_FOUND);
        }
    }

    public function logout(Request $request)
    {
        try {
            $result = $request->user()->currentAccessToken()->delete();
            if ($result) {
                return $this->apiResponse(true, "Çıkış Başarılı!", null, null, JsonResponse::HTTP_OK);
            } else {
                return $this->apiResponse(false, "Çıkış Yapılamadı!", null, null, JsonResponse::HTTP_NOT_FOUND);
            }
        } catch (\Exception $e) {
            return $this->apiResponse(false, $e->getMessage(), null, null, JsonResponse::HTTP_NOT_FOUND);
            
        }
    }




}