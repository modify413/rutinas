<?php

namespace App\Http\Controllers;

use Aws\S3\S3Client;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GifUploadController extends Controller
{
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

    public function store(Request $request)
    {
        $request->validate([
            'gif' => ['required', 'file', 'image', 'max:8192'],
        ], [], ['gif' => 'archivo']);

        $file = $request->file('gif');
        $key = 'gif-'.Str::uuid().'.'.$file->getClientOriginalExtension();

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
