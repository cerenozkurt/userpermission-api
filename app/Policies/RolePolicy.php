<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;

    public function roleAssignment(User $user,  $role, User $roleuser)
    {
        switch ($user) {
            case $user->hasAnyRole('superadmin'):
                return TRUE;
                break;
            default:
            if($roleuser->hasAnyRole('superadmin') || $role == 'superadmin'){
                return False;
                
            }
            return TRUE;
        }
    }

    public function roleRemove(User $user, $role, $roleuser)
    {   
        switch ($user) {
            case $user->hasAnyRole('superadmin'):
                return TRUE;
            
            default:

            if($roleuser->hasAnyRole(['superadmin','admin']) || $role == 'superadmin' || $role == 'admin'){
                return False;
            }
            return TRUE;
        }
    }
}
