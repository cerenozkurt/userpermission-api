<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class AuthRequest extends BaseFormRequest
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
            case 'create_user':
                return [
                    'name' => ['required', 'string'],
                    'email' => ['required', 'string', 'email', 'unique:users,email'],
                    'password' => ['required', 'string'],
                ];
                break;
            case 'login':
                return [
                    'password' => ['required', 'string'],
                    'email' => ['required', 'string', 'email'],
                ];
                break;
            case 'update_user':
                return [
                    'name' => ['string'],
                    'email' => ['string', 'email'],
                    'password' => ['string'],
                ];
        }
    }
}
