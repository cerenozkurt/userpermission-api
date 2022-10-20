<?php

namespace App\Http\Middleware\Post;

use App\Models\Post;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostStateControl
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

        if (Post::where('id', $request->route('post'))->where('state', '1')->first()) {
            return $next($request);
        }
        return $apiresponse->apiResponse(false, 'Post daha onaylanmamıştır.', null,null,JsonResponse::HTTP_FORBIDDEN);
    }
}
