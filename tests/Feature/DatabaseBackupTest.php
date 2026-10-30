<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseBackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_and_import_preserve_data_and_login(): void
    {
        $this->seed(AdminUserSeeder::class);
        $this->actingAs(User::first());
        $routine = $this->postJson('/routines', ['name' => 'Respaldo'])->assertCreated()->json();
        $this->putJson('/routines/'.$routine['id'], [
            'name' => 'Respaldo', 'sections' => [['title' => 'Uno', 'items' => [
                ['type' => 'exercise', 'name' => "Sentadillas\nLentas", 'duration_seconds' => 30],
            ]]],
        ])->assertOk();
        $backup = $this->get('/settings/database/export')->assertOk()->streamedContent();
        DB::table('routines')->where('id', $routine['id'])->update(['name' => 'Modificado']);

        $this->post('/settings/database/import', [
            'backup' => UploadedFile::fake()->createWithContent('backup.json', $backup), 'replace_data' => 1,
        ])->assertRedirect('/login')->assertSessionHasNoErrors();

        $this->assertGuest();
        $this->assertDatabaseHas('routines', ['name' => 'Respaldo']);
        $this->assertDatabaseHas('routine_items', ['name' => "Sentadillas\nLentas"]);
        $this->post('/login', ['username' => 'admin', 'password' => 'admin123'])->assertRedirect('/');
        $this->assertAuthenticated();
        $this->postJson('/routines', ['name' => 'Nueva'])->assertCreated();
    }

    public function test_failed_import_rolls_back_previous_database(): void
    {
        $this->seed(AdminUserSeeder::class);
        $this->actingAs(User::first());
        $backup = json_decode($this->get('/settings/database/export')->streamedContent(), true);
        $backup['tables']['users'][0]['username'] = 'otro';
        $backup['tables']['app_settings'] = [['key' => 'duplicate', 'value' => 'one'], ['key' => 'duplicate', 'value' => 'two']];

        $this->post('/settings/database/import', [
            'backup' => UploadedFile::fake()->createWithContent('backup.json', json_encode($backup)), 'replace_data' => 1,
        ])->assertSessionHasErrors('backup');

        $this->assertDatabaseHas('users', ['username' => 'admin']);
        $this->assertDatabaseMissing('users', ['username' => 'otro']);
        $this->assertDatabaseCount('app_settings', 0);
    }

    public function test_invalid_backup_is_rejected_and_access_is_admin_only(): void
    {
        $this->get('/settings/database/export')->assertRedirect('/login');
        $this->post('/settings/database/import')->assertRedirect('/login');
        $student = User::factory()->create(['username' => 'student', 'role' => 'student']);
        $this->actingAs($student)->get('/settings/database/export')->assertForbidden();
        $this->post('/settings/database/import')->assertForbidden();
        $this->seed(AdminUserSeeder::class);
        $this->actingAs(User::where('role', 'admin')->first());
        foreach (['invalid', '{"format":"rutinas-backup","version":1,"tables":{}}'] as $content) {
            $this->post('/settings/database/import', [
                'backup' => UploadedFile::fake()->createWithContent('backup.json', $content), 'replace_data' => 1,
            ])->assertSessionHasErrors('backup');
        }
        $this->assertDatabaseCount('users', 2);
        $this->post('/settings/database/import', [
            'backup' => UploadedFile::fake()->createWithContent('backup.json', '{}'),
        ])->assertSessionHasErrors('replace_data');
    }
}
