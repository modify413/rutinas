<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoutineItem extends Model
{
    protected $fillable = [
        'section_id', 'type', 'name', 'duration_seconds', 'gif_url', 'gif_path', 'position',
    ];

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function isRest(): bool
    {
        return $this->type === 'rest';
    }

    public function isExercise(): bool
    {
        return $this->type === 'exercise';
    }
}
