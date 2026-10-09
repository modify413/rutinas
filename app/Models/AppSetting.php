<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = ['key', 'value'];

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    public static function logoPath(): ?string
    {
        return static::where('key', 'app_logo')->value('value') ?: null;
    }

    public static function setLogo(?string $path): void
    {
        static::updateOrCreate(['key' => 'app_logo'], ['value' => $path]);
    }
}
