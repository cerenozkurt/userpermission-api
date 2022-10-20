<?php

namespace App\Http\Controllers;

use App\Http\Requests\PermissionRequest;
use App\Http\Resources\PermissionResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends ApiResponseController
{
    public function __construct()
    {
        $this->middleware('role_or_permission:superadmin|permission.createdelete', ['only' => ['store', 'destroy']]);
        $this->middleware('permission:permission.view', ['only' => ['index']]);
        $this->middleware('permission:permission.edit', ['only' => ['update']]);
        $this->middleware('permission:assignment.edit', ['only' => [
            'assignRole', 'removeRole', 'permission_assignRole',
            'permission_removeRole', 'user_givePermission', 'user_revokePermission', 'role_givePermission', 'role_revokePermission'
        ]]);
        $this->middleware('role.id.control', ['only' => ['permission_removeRole', 'role_revokePermission']]);
        $this->middleware('permission.id.control', ['only' => ['user_revokePermission']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */


    public function index()
    {
        $permission = Permission::orderby('name', 'asc')->get();
        if ($permission) {
            return $this->apiResponse(true, 'Tüm izinler.', 'permissions', PermissionResource::collection($permission), JsonResponse::HTTP_OK);
        }
        return $this->apiResponse(false, 'İzin bulunamamıştır.', null, null, JsonResponse::HTTP_NOT_FOUND);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(PermissionRequest $request)
    {
        //yeni izin ekleme 
        $store = Permission::create(['name' => $request->name, 'guard_name' => 'web']);
        if ($store) {
            $store->assignRole('superadmin');
            return $this->apiResponse(true, 'İzin eklendi.', 'permission', new PermissionResource($store), JsonResponse::HTTP_OK);
        }
        return $this->apiResponse(false, 'İzin eklenirken bir hata oluştu.', null, null, JsonResponse::HTTP_NOT_FOUND);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(PermissionRequest $request, $id)
    {   //izin güncelleme
        
        $permission = Permission::find($id);

        if ($permission->name != $request->name) {
            if (!Permission::firstwhere('name', $request->name)) {
                $permission->update(['name' => $request->name ?? $permission->name, 'guard_name' => 'web']);
                return $this->apiResponse(true, 'İzin güncellenmiştir.', 'permission', new PermissionResource($permission), JsonResponse::HTTP_OK);
            }
            return $this->apiResponse(false, 'Bu izin zaten mevcuttur.', null, null, JsonResponse::HTTP_NOT_FOUND);
        }
        return $this->apiResponse(true, 'İzin güncellenmiştir.', 'permission', new PermissionResource($permission), JsonResponse::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //izin silme
        $permission = Permission::Find($id);
        $permission->delete();

        return $this->apiResponse(true, 'İzin başarıyla silindi.', null, null, JsonResponse::HTTP_OK);
    }


    //roles_has_permission tablosu
    public function permission_assignRole($permission, PermissionRequest $request)
    {

        $permissions = Permission::find($permission);
        $role = Role::where('name', $request->name)->first();
        if ($role->hasPermissionTo($permissions)) { //bu rol bu izne sahip mi?
            return $this->apiResponse(false, 'Bu izin rol ilişkisi zaten mevcuttur.', null, null, JsonResponse::HTTP_NOT_FOUND);
        }
        $permissions->assignRole($request->name); //bu izne rolü ekler

        return $this->apiResponse(true, 'İzne rol eklenmiştir.', null, null, JsonResponse::HTTP_OK);
    }

    //roles_has_permission tablosu
    public function permission_removeRole($permission, $role)
    {

        $permission = Permission::find($permission);
        $role = Role::find($role);
        if ($role->hasPermissionTo($permission)) {
            $permission->removeRole($role);  //izinden rol kaldırılmıştır
            return $this->apiResponse(true, 'Rolün izni kaldırılmıştır.', null, null, JsonResponse::HTTP_OK);
        }
        return $this->apiResponse(false, 'Böyle bir izin rol ilişkisi bulunmamaktadır.', null, null, JsonResponse::HTTP_NOT_FOUND);
    }

    //model_has_permission tablosu
    public function user_givePermission(PermissionRequest $request, $user)
    {
        $user = User::find($user);
        if ($user->can($request->name)) { //kullanıcı bu izine sahip mi
            return $this->apiResponse(false, 'Kullanıcı bu izne zaten sahiptir.', null, null, JsonResponse::HTTP_NOT_FOUND);
        }

        $user->givePermissionTo($request->name); //kullanıcıya bu izni ekle
        return $this->apiResponse(true, 'Kullanıcıya istenen izin eklendi.', null, null, JsonResponse::HTTP_OK);
    }

    //model_has_permission tablosu
    public function user_revokePermission($user,  $permission)
    {
        $user = User::find($user);
        $permission = Permission::find($permission);

        if ($user->can($permission->name)) {
            $user->revokePermissionTo($permission); //kullanıcıdan bu izni kaldır
            return $this->apiResponse(true, 'Kullanıcı izni başarıyla kaldırıldı.', null, null, JsonResponse::HTTP_OK);
        }
        return $this->apiResponse(false, 'Kullanıcı bu izne sahip değildir.', null, null, JsonResponse::HTTP_NOT_FOUND);
    }

    //role_has_permission tablosu
    public function role_givePermission(PermissionRequest $request, $role)
    {
        $role = Role::find($role);
        $permission = Permission::where('name', $request->name)->first();
        if ($role->hasPermissionTo($permission)) { //bu rol bu izne sahip mi?
            return $this->apiResponse(false, 'Bu izin rol ilişkisi zaten mevcuttur.', null, null, JsonResponse::HTTP_NOT_FOUND);
        }
        $role->givePermissionTo($request->name); //role istenen izin eklenir
        return $this->apiResponse(true, 'Role izin eklenmiştir.', null, null, JsonResponse::HTTP_OK);
    }

    //role_has_permission tablosu
    public function role_revokePermission($role,  $permission)
    {
        $permission = Permission::find($permission);
        $role = Role::find($role);
        if ($role->hasPermissionTo($permission->name)) { //rol bu izne sahip mi?
            $role->revokePermissionTo($permission); //rolden izni kaldır
            return $this->apiResponse(true, 'Rolün izni kaldırılmıştır.', null, null, JsonResponse::HTTP_OK);
        }
        return $this->apiResponse(false, 'Böyle bir izin rol ilişkisi bulunmamaktadır.', null, null, JsonResponse::HTTP_NOT_FOUND);
    }
}
