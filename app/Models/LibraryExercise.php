<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryExercise extends Model
{
    protected $fillable = [
        'user_id', 'name', 'duration_seconds', 'gif_url', 'gif_path',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
