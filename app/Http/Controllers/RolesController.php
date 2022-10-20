<?php

namespace App\Http\Controllers;

use App\Http\Requests\RolesRequest;
use App\Http\Resources\RolesResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class RolesController extends ApiResponseController
{
    function __construct()
    {
        //$this->middleware('role:superadmin', ['only' => [ 'create_role','delete_role']]); //bu işlemleri superadmin rolüne sahip olanlar gerçekleştirebilir
        //$this->middleware('role:superadmin|admin', ['only' => ['index','role_assignment', 'user_roles', 'role_remove', 'role_filter']]);

        $this->middleware('permission:role.view', ['only' => ['user_roles', 'role_filter']]);
        $this->middleware('permission:role.createdelete', ['only' => ['create_role', 'delete_role']]);
        $this->middleware('permission:assignment.edit', ['only' => ['index', 'role_assignment', 'role_remove']]);
    }

    //tüm roller  superadmin/admin 
    public function index()
    {
        $roles = Role::all();
        return $this->apiResponse(true, 'Tüm roller listelendi.', 'roles', RolesResource::collection($roles), JsonResponse::HTTP_OK);
    }

    //yeni bir rol ekleme
    public function create_role(RolesRequest $request)
    {
        $role = Role::create(['name' => $request->name, 'guard_name' => 'web']);

        if ($role) {
            return $this->apiResponse(true, 'Rol başarıyla eklendi.', 'role', new RolesResource($role), JsonResponse::HTTP_OK);
        }
        return $this->apiResponse(false, 'Rol eklenirken bir hata oluştu.', 'role', new RolesResource($role), JsonResponse::HTTP_NOT_FOUND);
    }

    //rol silme
    public function delete_role($role)
    {
        $roles = Role::find($role);

        $delete =  $roles->delete();

        if ($delete) {
            return $this->apiResponse(true, 'Rol başarıyla silindi.', 'role', $roles, JsonResponse::HTTP_OK);
        }
        return $this->apiResponse(false, 'Rol silinirken bir hata oluştu.', 'role', $roles, JsonResponse::HTTP_NOT_FOUND);
    }

    //kullanıcıya rol atama, superadmin ve admin atayabilir, admin superadmin rolünü atayamaz
    public function role_assignment($id, RolesRequest $request)
    {
        $authuser = Auth::user();
        if (in_array('superadmin', $authuser->roles->pluck('name')->toarray())) { //superadminse
            $user = User::find($id);
            if (in_array($request->role, $user->roles->pluck('name')->toarray())) { //atanmak istenen rol zaten atanmış ise
                return $this->apiResponse(false, 'Kullanıcı ' . $request->role . ' rolüne zaten sahip.', 'user', new UserResource($user), JsonResponse::HTTP_NOT_FOUND);
            }
            $user->assignRole($request->role);
            return $this->apiResponse(true, 'Kullanıcıya ' . $request->role . ' rolü atandı.', 'user', new UserResource($user), JsonResponse::HTTP_OK);
        } elseif (in_array('admin', $authuser->roles->pluck('name')->toarray())) { //adminse, superadmin dışındaki rol atamalarını yapabilir
            $user = User::find($id);
            if ($request->role == 'superadmin' || in_array('superadmin', $user->roles->pluck('name')->toarray())) { //atanmak istenen rol süoeradminse veya kullanıcı superadminse 
                return $this->apiResponse(false, 'Admin superadmin rolü ile ilgili işlem yapamaz.', null, null, JsonResponse::HTTP_FORBIDDEN);
            } elseif (in_array($request->role, $user->roles->pluck('name')->toarray())) { //atanmak istenen rol zaten atanmış ise
                return $this->apiResponse(false, 'Kullanıcı ' . $request->role . ' rolüne zaten sahip.', 'user', new UserResource($user), JsonResponse::HTTP_NOT_FOUND);
            }
            $user->assignRole($request->role);
            return $this->apiResponse(true, 'Kullanıcıya ' . $request->role . ' rolü atandı.', 'user', new UserResource($user), JsonResponse::HTTP_OK);
        }
    }

    //girilen kullanıcının sahip olduğu roller  //superadmin,admin
    public function user_roles($id)
    {
        $user = User::find($id);
        return ['roles_the_user_has' =>  new UserResource($user)];
    }

    //kullanıcının rolünü silme
    public function role_remove($id, RolesRequest $request)
    {
        //USER ROLÜ KAYITLI TÜM KULLANICILARIN ORTAK ROLÜ OLDUĞU İÇİN KALDIRILAMAZ. ANCAK KULLANICI HESABI SİLİNİRSE KALDIRILIR.
        if ($request->role == 'user') {
            return $this->apiResponse(false, 'User rolü her kullanıcı için ortak roldür. Kaldırılamaz.', null, null, JsonResponse::HTTP_FORBIDDEN);
        }

        $authuser = Auth::user();
        if (in_array('superadmin', $authuser->roles->pluck('name')->toarray())) { //superadminse
            $user = User::find($id);
            if (in_array($request->role, $user->roles->pluck('name')->toarray())) { //silinmek istenen rol kullanıcının rolü mü? ve user rolüne eşit değilse
                $user->removeRole($request->role); //rolü sil
                return $this->apiResponse(true, 'Kullanıcıdan ' . $request->role . ' rolü kaldırıldı.', 'user', new UserResource($user), JsonResponse::HTTP_OK);
            }
            return $this->apiResponse(false, 'Kullanıcı ' . $request->role . ' rolüne zaten sahip değil.', 'user', new UserResource($user), JsonResponse::HTTP_NOT_FOUND);
        } elseif (in_array('admin', $authuser->roles->pluck('name')->toarray())) { //adminse, superadmin ve admin dışındaki rol silmelerini yapabilir
            $user = User::find($id);
            if ($request->role == 'superadmin' || $request->role == 'admin') { //silinmek istenen rol süperadminse veya kullanıcı superadminse 
                return $this->apiResponse(false, 'Admin superadmin ve admin rolü ile ilgili rol silme işlemi yapamaz.', null, null, JsonResponse::HTTP_FORBIDDEN);
            } elseif (in_array($request->role, $user->roles->pluck('name')->toarray())) { //silinmek istenen rol kullanıcının rolü mü? ve user rolüne eşit değilse
                $user->removeRole($request->role); //rolü sil
                return $this->apiResponse(true, 'Kullanıcıdan ' . $request->role . ' rolü kaldırıldı.', 'user', new UserResource($user), JsonResponse::HTTP_OK);
            }
            return $this->apiResponse(false, 'Kullanıcı ' . $request->role . ' rolüne zaten sahip değil.', 'user', new UserResource($user), JsonResponse::HTTP_NOT_FOUND);
        }
    }

    //girilen role sahip kullanıcıları listeler
    public function role_filter($role)
    {
        $users = User::role($role)->get();
        return ['in_' . $role . '_role' => UserResource::collection($users)];
    }
}
