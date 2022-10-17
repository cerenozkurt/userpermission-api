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
        switch ($request->route()->getActionMethod()) {
            case 'create_post':
                return [
                    'title' => ['required', 'max:100'],
                    'content' => ['required', 'max:15000'],
                    'category_id' => ['required', 'exists:categories,id'],
                ];
                break;
            case 'update_post':
                return [
                    'title' => ['max:100'],
                    'content' => ['max:15000'],
                    'category_id' => ['exists:categories,id'],
                ];
                break;
            case 'post_add_to_category':
                return [
                    'category_id' =>  ['required', 'integer', 'exists:categories,id']
                ];
                break;
            case 'post_delete_to_category':
                return [
                    'category_id' =>  ['required', 'integer', 'exists:categories,id']
                ];
                break;
            case 'post_update_to_category':
                return [
                    'category_id' =>  ['required']
                ];
                break;
        }
    }
}
