<?php

namespace App\Http\Controllers;

use Aws\S3\S3Client;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GifUploadController extends Controller
{
    public const MAX_KB = 25600; // 25 MB

    public static function client(): S3Client
    {
        $disk = config('filesystems.disks.neon');

        return new S3Client([
            'version' => 'latest',
            'region' => $disk['region'],
            'endpoint' => $disk['endpoint'],
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => $disk['key'],
                'secret' => $disk['secret'],
            ],
        ]);
    }

    public static function bucket(): string
    {
        return config('filesystems.disks.neon.bucket', 'gifs');
    }

    public static function mediaKind(?string $pathOrUrl): string
    {
        $s = (string) $pathOrUrl;
        if (preg_match('#(?:youtube\.com/(?:watch|embed|shorts|live)|youtu\.be/)#i', $s)) {
            return 'youtube';
        }

        return (bool) preg_match('/\.(mp4|webm)(\?|#|$)/i', $s)
            ? 'video'
            : 'image';
    }

    public function store(Request $request)
    {
        // Si PHP rechazó el archivo por su propio límite, llega vacío:
        // avisarlo claro en vez del genérico "campo obligatorio".
        if (! $request->hasFile('gif') && $request->server('CONTENT_LENGTH') > 0) {
            return response()->json(['message' => 'El archivo es demasiado grande para el servidor (máx 25 MB).'], 422);
        }

        $request->validate([
            'gif' => [
                'required', 'file', 'max:'.static::MAX_KB,
                'mimetypes:image/gif,image/jpeg,image/png,image/webp,video/mp4,video/webm',
            ],
        ], [
            'gif.required' => 'Elige un archivo primero.',
            'gif.max' => 'El archivo supera los 25 MB.',
            'gif.mimetypes' => 'Formato no permitido. Usa GIF, JPG, PNG, WEBP, MP4 o WEBM.',
        ], ['gif' => 'archivo']);

        $file = $request->file('gif');

        // Disco local (sin internet): se guarda en storage/app/uploads y se
        // sirve por /media. Las rutas locales llevan prefijo "local/".
        if (config('media.disk') === 'local') {
            try {
                $stored = \Illuminate\Support\Facades\Storage::disk('uploads')->putFile('', $file);
                if (! $stored) {
                    throw new \RuntimeException('write');
                }
                $path = 'local/'.$stored;

                return response()->json([
                    'path' => $path,
                    'url' => static::publicUrl($path),
                    'kind' => static::mediaKind($path),
                ], 201);
            } catch (\Throwable $e) {
                report($e);

                return response()->json(['message' => 'No se pudo guardar el archivo.'], 500);
            }
        }

        $key = 'media-'.Str::uuid().'.'.$file->getClientOriginalExtension();

        try {
            static::client()->putObject([
                'Bucket' => static::bucket(),
                'Key' => $key,
                'Body' => fopen($file->getRealPath(), 'rb'),
                'ContentType' => $file->getMimeType(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'No se pudo subir el archivo al bucket.'], 500);
        }

        return response()->json([
            'path' => $key,
            'url' => static::temporaryUrl($key),
            'kind' => static::mediaKind($key),
        ], 201);
    }

    public static function temporaryUrl(?string $path): ?string
    {
        if (! $path || str_starts_with($path, 'local/')) {
            return null;
        }
        try {
            $cmd = static::client()->getCommand('GetObject', [
                'Bucket' => static::bucket(),
                'Key' => $path,
            ]);

            return (string) static::client()->createPresignedRequest($cmd, '+3 hours')->getUri();
        } catch (\Throwable) {
            return null;
        }
    }

    /** URL pública del medio, sea del bucket o del disco local. */
    public static function publicUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (str_starts_with($path, 'local/')) {
            return url('/media/'.substr($path, strlen('local/')));
        }

        return static::temporaryUrl($path);
    }

    public static function isLocalPath(?string $path): bool
    {
        return is_string($path) && str_starts_with($path, 'local/');
    }
}
