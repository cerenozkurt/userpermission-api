<?php

namespace App\Http\Middleware\User;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleNameControl
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $apiresponse = app('App\Http\Controllers\ApiResponseController');
        if(DB::table('roles')->where('name',$request->route('role'))->first()){
            return $next($request);
        }
        return $apiresponse->apiResponse(false, $request->route('role').' rolü bulunamadı.', null, null, JsonResponse::HTTP_NOT_FOUND);
    }
}
