<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    



    //izin ekle //superadmin
    public function create_permission(Request $request)
    {
        //Permission::create(['name' => 'users.all'])->syncRoles(['superadmin','admin']);
       // Permission::create(['name' => $request->permission])->syncRoles([$request->roles]);
    }


    //izin sil
    public function delete_permission()
    {
        # code...
    }

    //kullanıcıya izin atama

    //kullanıcının iznini alma

    //role izin atama

    //rolün iznini alma


}
