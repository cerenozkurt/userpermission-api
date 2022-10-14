<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class PostRequest extends BaseFormRequest
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
            case 'create_post':
                return [
                    'title' => ['required', 'max:100'],
                    'content' => ['required', 'max:15000'],
                ];
                break;
            case 'update_post':
                return [
                    'title' => ['max:100'],
                    'content' => ['max:15000']
                ];
                break;
        }
    }
}
