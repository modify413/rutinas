<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    /** Sirve archivos del disco local (modo sin internet). Requiere login. */
    public function show(string $path)
    {
        if (str_contains($path, '..') || str_starts_with($path, '/') || $path === '') {
            abort(404);
        }

        $disk = Storage::disk('uploads');

        if (! $disk->exists($path)) {
            abort(404);
        }

        return response()->file($disk->path($path));
    }
}
