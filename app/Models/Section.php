<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $fillable = ['routine_id', 'title', 'position'];

    public function routine()
    {
        return $this->belongsTo(Routine::class);
    }

    public function items()
    {
        return $this->hasMany(RoutineItem::class)->orderBy('position');
    }
}
