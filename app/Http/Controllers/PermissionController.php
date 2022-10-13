<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    
    public function create_permission(Request $request)
    {
        //Permission::create(['name' => 'users.all'])->syncRoles(['superadmin','admin']);
        Permission::create(['name' => $request->permission])->syncRoles([$request->roles]);
    }
}
