<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiResponseController extends Controller
{
    public function apiResponse($success, $message = null,  $data_name = null, $data = null, $code = 200)
    {
        $success_value = $success == true ? true : false;
        $response = [];
        $response['success'] = $success_value;
        if (isset($message)) {
            $response['message'] = $message;
        }
        if (isset($data_name)) {
            $response[$data_name] = $data;
        }
        

        return response()->json(
            $response,
            $code
        );
    }
}
