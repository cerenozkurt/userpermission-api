<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class CategoryRequest extends BaseFormRequest
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
            case 'create_category':
                return [
                    'name' => ['required', 'max:30', 'unique:categories,name']
                ];
                break;
            case 'update_category':
                return [
                    'name' => ['max:30']
                ];
                break;
        }
    }
}
