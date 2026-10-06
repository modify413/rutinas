<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'created_by',
        'security_question',
        'security_answer',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'security_answer',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'security_answer' => 'hashed',
        ];
    }

    public function routines()
    {
        return $this->hasMany(Routine::class);
    }

    public function libraryExercises()
    {
        return $this->hasMany(LibraryExercise::class)->orderBy('name');
    }

    public function students()
    {
        return $this->hasMany(User::class, 'created_by');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    /** Rutinas que el usuario puede ejecutar en el Timer. */
    public function visibleRoutines()
    {
        if ($this->isAdmin()) {
            return $this->routines();
        }

        return Routine::where('user_id', $this->created_by ?: -1);
    }

    public static function normalizeAnswer(?string $answer): string
    {
        return mb_strtolower(trim((string) $answer));
    }
}
