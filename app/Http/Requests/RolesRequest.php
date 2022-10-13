<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class RolesRequest extends BaseFormRequest
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
        switch ($request->route()->getActionMethod()) {
            case 'create_role':
                return [
                    'name' => ['required', 'max:30', 'unique:roles,name']
                ];
                break;
            case 'role_assignment':
                return [
                    //'id' => ['required', 'exists:users,id'],
                    'role' => ['required', 'exists:roles,name']
                ];
                break;
            case 'role_remove':
                return [
                    'role' => ['required', 'exists:roles,name']
                ];
                break;
        }
    }
}
