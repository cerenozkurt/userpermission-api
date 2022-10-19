<?php

namespace App\Http\Middleware\Permission;

use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleIdControl
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
        try {
            $role = Role::findorfail($request->route('role'));
            if ($role) {
                return $next($request);
            }
        } catch (\Exception $e) {
            if ($e instanceof ModelNotFoundException) {
                return $apiresponse->apiResponse(false, 'Rol bulunamadı.', null, null, JsonResponse::HTTP_NOT_FOUND);
            }
            return $apiresponse->apiResponse(false, null, 'error', $e->getMessage(), JsonResponse::HTTP_NOT_FOUND);
        }
    }
}
