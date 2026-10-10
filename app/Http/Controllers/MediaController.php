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

    /**
     * Logo público (login y favicon): no requiere login para no romper
     * la redirección post-login (la petición del logo como invitado
     * sobrescribía la URL de destino). Solo sirve el logo configurado,
     * nunca rutas arbitrarias.
     */
    public function logo()
    {
        $path = \App\Models\AppSetting::logoPath();

        if (! $path) {
            abort(404);
        }

        if (\App\Http\Controllers\GifUploadController::isLocalPath($path)) {
            $rel = substr($path, strlen('local/'));
            if (str_contains($rel, '..') || $rel === '') {
                abort(404);
            }
            $disk = Storage::disk('uploads');
            if (! $disk->exists($rel)) {
                abort(404);
            }

            return response()->file($disk->path($rel));
        }

        $url = \App\Http\Controllers\GifUploadController::temporaryUrl($path);
        if (! $url) {
            abort(404);
        }

        return redirect()->away($url);
    }
}
