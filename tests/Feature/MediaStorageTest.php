<?php

namespace Tests\Feature;

use App\Http\Controllers\GifUploadController;
use App\Models\AppSetting;
use App\Models\User;
use Aws\Result;
use Aws\S3\S3Client;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_without_bucket_is_saved_locally(): void
    {
        $disk = Storage::fake('local');
        config(['filesystems.disks.local.root' => $disk->path('')]);
        Storage::forgetDisk('local');
        config(['filesystems.disks.neon.key' => null]);
        $this->seed(AdminUserSeeder::class);
        $this->actingAs(User::where('username', 'admin')->first());

        $response = $this->postJson('/uploads/gif', [
            'gif' => UploadedFile::fake()->create('clip.mp4', 10, 'video/mp4'),
        ])->assertCreated()->assertJsonPath('kind', 'video');

        $path = $response->json('path');
        $this->assertStringStartsWith('local/', $path);
        Storage::disk('local')->assertExists($path);
        $this->assertNotEmpty($response->json('url'));
        $this->get($response->json('url'))->assertOk();
        config(['media.disk' => 'auto', 'filesystems.disks.neon' => [
            'key' => 'test', 'secret' => 'test', 'region' => 'us-east-2',
            'bucket' => 'gifs', 'endpoint' => 'https://storage.example.test',
        ]]);
        Storage::forgetDisk('local');
        $this->get(GifUploadController::temporaryUrl($path))->assertOk();
    }

    public function test_failed_bucket_upload_falls_back_to_local_storage(): void
    {
        Storage::fake('local');
        config(['media.disk' => 'auto', 'filesystems.disks.neon' => [
            'key' => 'test', 'secret' => 'test', 'region' => 'us-east-2',
            'bucket' => 'gifs', 'endpoint' => 'https://storage.example.test',
        ]]);
        $client = \Mockery::mock(S3Client::class);
        $client->shouldReceive('putObject')->once()->andThrow(new \RuntimeException('Offline'));
        $controller = new class extends GifUploadController
        {
            public static S3Client $testClient;

            public static function client(): S3Client
            {
                return self::$testClient;
            }
        };
        $controller::$testClient = $client;

        $path = $controller::storeMedia(UploadedFile::fake()->create('clip.mp4', 10, 'video/mp4'), 'clip.mp4');

        $this->assertSame('local/clip.mp4', $path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_available_bucket_keeps_remote_path(): void
    {
        Storage::fake('local');
        config(['media.disk' => 'auto', 'filesystems.disks.neon' => [
            'key' => 'test', 'secret' => 'test', 'region' => 'us-east-2',
            'bucket' => 'gifs', 'endpoint' => 'https://storage.example.test',
        ]]);
        $client = \Mockery::mock(S3Client::class);
        $client->shouldReceive('putObject')->once()->andReturn(new Result);
        $controller = new class extends GifUploadController
        {
            public static S3Client $testClient;

            public static function client(): S3Client
            {
                return self::$testClient;
            }
        };
        $controller::$testClient = $client;

        $path = $controller::storeMedia(UploadedFile::fake()->create('clip.mp4', 10, 'video/mp4'), 'clip.mp4');

        $this->assertSame('clip.mp4', $path);
        Storage::disk('local')->assertMissing('local/clip.mp4');
    }

    public function test_logo_replacement_in_local_mode_removes_previous_local_logo(): void
    {
        Storage::fake('local');
        config(['media.disk' => 'local']);
        Storage::disk('local')->put('local/logos/old.svg', '<svg/>');
        AppSetting::setLogo('local/logos/old.svg');
        $this->seed(AdminUserSeeder::class);
        $this->actingAs(User::where('username', 'admin')->first());

        $this->post('/settings/logo', [
            'logo' => UploadedFile::fake()->create('logo.svg', 1, 'image/svg+xml'),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertStringStartsWith('local/logos/', AppSetting::logoPath());
        Storage::disk('local')->assertExists(AppSetting::logoPath());
        Storage::disk('local')->assertMissing('local/logos/old.svg');
    }
}
