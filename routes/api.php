<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});*/

Route::post('/register',[AuthController::class, 'create_user']);
Route::post('/login',[AuthController::class, 'login'])->name('login');

Route::middleware('auth:sanctum')->group(function(){
    Route::get('/logout', [AuthController::class, 'logout']);
    Route::post('/update', [AuthController::class, 'update_user'])->name('update');
    //Route::get('/all-user',[AuthController::class, 'all_user'])->name('users.all');
    
    Route::prefix('/user')->controller(UserController::class)->group(function(){
        Route::get('/','index')->name('users.all');
        Route::post('/','create_user')->name('users.create');
        Route::delete('/{user}', 'delete_user')->name('users.delete')->middleware('user.id.control');
        Route::post('/{user}','update_user')->name('users.update')->middleware('user.id.control');
    });

    Route::prefix('/role')->controller(RolesController::class)->group(function(){
        Route::get('/','index')->name('roles.all'); 
        Route::get('/{user}','user_roles')->name('roles.user')->middleware('user.id.control');
        Route::post('/','create_role')->name('roles.create');
        Route::delete('{role}','delete_role')->name('roles.delete');
        Route::post('{user}/assignment','role_assignment')->name('roles.assignment')->middleware('user.id.control');
        Route::post('{user}/remove','role_remove')->name('roles.remove')->middleware('user.id.control');
        Route::get('/filter/{role}', 'role_filter')->name('roles.filter')->middleware('role.name.control');
    });

    Route::prefix('/permission')->controller(PermissionController::class)->group(function(){

    });

    

    
});
