<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Routine extends Model
{
    protected $fillable = ['user_id', 'name'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sections()
    {
        return $this->hasMany(Section::class)->orderBy('position');
    }

    public function totalSeconds(): int
    {
        return (int) $this->sections()->with('items')->get()
            ->sum(fn ($s) => $s->items->sum('duration_seconds') * max(1, (int) $s->reps));
    }
}
