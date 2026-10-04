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
        return (bool) preg_match('/\.(mp4|webm)(\?|#|$)/i', (string) $pathOrUrl)
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
        if (! $path) {
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
}
