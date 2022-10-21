<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PostPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Post $post)
    {
        //
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->hasAnyRole(['superadmin', 'admin', 'editor']); //admin editor veya superadmin mi
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Post $post)
    {
        //kendi postuysa günceller
        return $user->id == $post->users->id; 

    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Post $post)
    {
        switch ($user) {
            case $user->hasRole('superadmin'): //superadminse siler
                return TRUE;
                break;
            case $user->hasRole('admin'): //adminse rolu superadmin ve admin olmayanların postunu siler
                if ($user->id != $post->users->id  && $post->users->hasAnyRole(['superadmin', 'admin'])) {
                    return FALSE;
                }
                return TRUE;
                break;
            case $user->hasRole('editor'): //editorse sadece writerların postlarını siler
                if ($user->id != $post->users->id  &&  $post->users->hasAnyRole(['superadmin', 'admin', 'editor'])) {
                    return FALSE;
                }
                return TRUE;
                break;
            default: //writersa sadece kendi postunu siler
                if ($post->users->id == $user->id) {
                    return TRUE;
                    break;
                }
                return FALSE;
        }
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Post $post)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Post $post)
    {
        //
    }


    //kendi postu mu
    public function ownPostControl(User $user, Post $post)
    {
        return $user->id == $post->users->id; 
    }


    //kademeli kontrol eder.
    //admin superadmin,admin postlarına müdahale edemez
    //editor superadmin,admin,editor postlarına müdahele edemez
    //writer sadece kendi postlarına müdahale eder
    public function gradualPermission(User $user, Post $post)
    {
        switch ($user) {
            case $user->hasRole('superadmin'): //superadminse true
                return TRUE;
                break;
            case $user->hasRole('admin'): //adminse rolu superadmin ve admin olmayanların postuna müdahale edebilir
                if ($user->id != $post->users->id  && $post->users->hasAnyRole(['superadmin', 'admin'])) {
                    return FALSE;
                }
                return TRUE;
                break;
            case $user->hasRole('editor'): //editorse sadece writerların postlarına müdahale edebilir
                if ($user->id != $post->users->id  &&  $post->users->hasAnyRole(['superadmin', 'admin', 'editor'])) {
                    return FALSE;
                }
                return TRUE;
                break;
            default: //writersa sadece kendi postuna müdahale edebilir
                if ($post->users->id == $user->id) {
                    return TRUE;
                    break;
                }
                return FALSE;
        }
    }


    
}
