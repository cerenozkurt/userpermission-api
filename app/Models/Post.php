<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $table = 'posts';
    protected $fillable = [
        'user_id',
        'title',
        'content',
        'like_count',
        'comment_count',
        'state'
    ];

    //ONE TO MANY
    public function users()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    //MANY TO MANY
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_posts', 'post_id', 'category_id');
    }


    //ONE TO MANY
    public function comments()
    {
        return $this->hasMany(Comment::class)->orderby('created_at','desc');
    }

    //ONE TO MANY
    public function likes()
    {
        return $this->hasMany(Like::class);
    }
}
