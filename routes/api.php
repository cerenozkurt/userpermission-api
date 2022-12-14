<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PostController;
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

Route::post('/register', [AuthController::class, 'create_user']);
Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/logout', [AuthController::class, 'logout']);
    Route::post('/update', [AuthController::class, 'update_user'])->name('update');
    //Route::get('/all-user',[AuthController::class, 'all_user'])->name('users.all');

    Route::prefix('/permission')->controller(PermissionController::class)->middleware('role:superadmin|admin')->group(function(){
        Route::get('/','index');
        Route::post('/','store')->middleware('role:superadmin|admin')->name('permission.store');
        Route::post('/{permission}', 'update')->middleware('permission.id.control');
        Route::delete('{permission}','destroy')->middleware('permission.id.control');

        Route::post('/role/{permission}','permission_assignRole')->middleware('permission.id.control');
        Route::delete('/role/{permission}/{role}','permission_removeRole')->middleware(['permission.id.control']);
        
        Route::post('/user/{user}','user_givePermission')->middleware('user.id.control');
        Route::delete('/user/{user}/{permission}','user_revokePermission')->middleware('user.id.control');
       
        Route::post('/role/{role}/permission','role_givePermission')->middleware('role.id.control');
        Route::delete('/role/{role}/permission/{permission}', 'role_revokePermission')->middleware('permission.id.control');
    });


    Route::prefix('/user')->controller(UserController::class)->group(function () {
        Route::get('/', 'index')->name('users.all');
        Route::post('/', 'create_user')->name('users.create');
        Route::delete('/{user}', 'delete_user')->name('users.delete')->middleware('user.id.control');
        Route::post('/{user}', 'update_user')->name('users.update')->middleware('user.id.control');
    });

    Route::prefix('/role')->controller(RolesController::class)->group(function () {
        Route::get('/', 'index')->name('roles.all');
        Route::get('/{user}', 'user_roles')->name('roles.user')->middleware('user.id.control');
        Route::post('/', 'create_role')->name('roles.create');
        Route::delete('{role}', 'delete_role')->middleware('role.id.control');
        Route::post('{user}/assignment', 'role_assignment')->name('roles.assignment')->middleware('user.id.control');
        Route::post('{user}/remove', 'role_remove')->name('roles.remove')->middleware('user.id.control');
        Route::get('/filter/{role}', 'role_filter')->name('roles.filter')->middleware('role.name.control');
    });

    /*Route::prefix('/permission')->controller(PermissionController::class)->group(function(){

    });*/

    Route::prefix('/category')->controller(CategoryController::class)->group(function () {
        Route::post('/', 'create')->name('categories.create');
        Route::delete('/{category}', 'delete_category')->name('categories.delete')->middleware('category.id.control');
        Route::post('/{category}/update', 'update_category')->name('categories.update')->middleware('category.id.control');
        Route::get('/{category}/posts','get_posts_of_category')->middleware('category.id.control');
        Route::get('/{category}','search');
    });



    Route::prefix('/post')->controller(PostController::class)->group(function () {
        Route::post('/', 'create');
        Route::post('/{post}/update', 'update')->middleware('post.id.control');
        Route::delete('/{post}', 'delete')->name('posts.delete')->middleware('post.id.control');
        Route::post('/{post}/category', 'post_update_to_category')->middleware('post.id.control');
        Route::get('/awaiting', 'awaiting_approve');
        Route::get('{post}/approved', 'approve_post')->middleware('post.id.control');
        Route::get('/my', 'my_posts');
        Route::get('/{user}/posts', 'allposts_by_user')->middleware('user.id.control');

        Route::post('/{post}/category/delete', 'post_delete_to_category')->middleware('post.id.control');
    });

    Route::prefix('/comment')->controller(CommentController::class)->group(function(){
        Route::post('/{post}','store')->middleware(['post.id.control', 'post.state.control']);
        Route::post('/{comment}/update','update')->middleware('comment.id.control');
        Route::get('/{comment}','show')->middleware('comment.id.control');
        Route::delete('{comment}','destroy')->middleware('comment.id.control');
        
    });

    Route::prefix('/like')->controller(LikeController::class)->group(function(){
        Route::post('/{post}','store')->middleware(['post.id.control','post.state.control']);
        Route::delete('/{like}','destroy')->middleware('like.id.control');
    
    });


});



//PUBL??C
Route::prefix('public')->group(function () {
    Route::get('/category', [CategoryController::class, 'index'])->name('categories.name');

    Route::prefix('/post')->controller(PostController::class)->group(function () {
        Route::get('/', 'index')->name('posts.name');
        Route::get('/{post}',  'post_by_id')->middleware('post.id.control','post.state.control');
        Route::get('/{user}/posts', 'post_by_user')->middleware('user.id.control');
        Route::get('{post}/category', 'post_get_to_category')->middleware(['post.id.control','post.state.control']); //herkes
    });

    Route::get('/like/{post}',[LikeController::class, 'index'])->middleware(['post.id.control','post.state.control']);
    Route::get('/like',[LikeController::class, 'most_liked']);

    Route::prefix('/comment')->controller(CommentController::class)->group(function(){
        Route::get('/{post}/posts','comments_of_post')->middleware(['post.id.control','post.state.control']);
        Route::get('/{user}/user','comments_of_user')->middleware('user.id.control');
    });
});
