<?php

namespace App\Http\Controllers;

use Aws\S3\S3Client;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
            'http' => ['connect_timeout' => 2, 'timeout' => 5],
            'retries' => 0,
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

    public static function bucketConfigured(): bool
    {
        if (config('media.disk') === 'local') {
            return false;
        }

        foreach (['key', 'secret', 'region', 'bucket', 'endpoint'] as $field) {
            if (! filled(config('filesystems.disks.neon.'.$field))) {
                return false;
            }
        }

        return true;
    }

    public static function storeMedia(UploadedFile $file, string $key): string
    {
        if (static::bucketConfigured()) {
            try {
                static::client()->putObject([
                    'Bucket' => static::bucket(),
                    'Key' => $key,
                    'SourceFile' => $file->getRealPath(),
                    'ContentType' => $file->getMimeType(),
                ]);

                return $key;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $path = 'local/'.$key;
        if (! Storage::disk('local')->putFileAs(dirname($path), $file, basename($path))) {
            throw new \RuntimeException('No se pudo guardar el archivo localmente.');
        }

        return $path;
    }

    public static function deleteMedia(string $path): void
    {
        if (str_starts_with($path, 'local/')) {
            Storage::disk('local')->delete($path);
        } elseif (static::bucketConfigured()) {
            static::client()->deleteObject(['Bucket' => static::bucket(), 'Key' => $path]);
        }
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
        $key = 'media-'.Str::uuid().'.'.$file->extension();

        try {
            $key = static::storeMedia($file, $key);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'No se pudo guardar el archivo.'], 500);
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
            if (str_starts_with($path, 'local/')) {
                return Storage::disk('local')->temporaryUrl($path, now()->addHours(3));
            }
            if (! static::bucketConfigured()) {
                return null;
            }
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
