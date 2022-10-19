<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class PermissionRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(Request $request)
    {
        switch($request->route()->getActionMethod()){
            case 'store':
                return[
                    'name' => ['required', 'max:100', 'unique:permissions,name']
                ];
                break;
            case 'update':
                return [
                    'name' => ['max:100']
                ];
                break;
            case 'permission_assignRole':
                return [
                    'name' => ['required', 'exists:roles,name']
                ];
                break;
            case 'user_givePermission':
                return [
                    'name' => ['required', 'exists:permissions,name']
                ];
                break;
            case 'role_givePermission':
                return [
                    'name' => ['required', 'exists:permissions,name']
                ];
                break;
        }
    
    }
}
