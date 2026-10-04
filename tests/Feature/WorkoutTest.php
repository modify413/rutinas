<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AdminUserSeeder::class);
    }

    public function test_admin_seed_and_login(): void
    {
        $this->assertDatabaseHas('users', ['username' => 'admin']);
        $res = $this->post('/login', ['username' => 'admin', 'password' => 'admin123']);
        $res->assertRedirect('/');
        $this->assertAuthenticated();
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_full_routine_flow(): void
    {        $this->post('/login', ['username' => 'admin', 'password' => 'admin123']);

        // Crear rutina
        $this->post('/routines', ['name' => 'Cardio'])->assertRedirect();
        $routineId = \App\Models\Routine::first()->id;

        // Guardar estructura completa con secciones, ejercicios y descansos en orden
        $payload = [
            'name' => 'Cardio',
            'sections' => [
                ['title' => 'Sección 1', 'items' => [
                    ['type' => 'exercise', 'name' => 'Flexiones', 'duration_seconds' => 30, 'gif_url' => null],
                    ['type' => 'exercise', 'name' => 'Sentadillas', 'duration_seconds' => 30, 'gif_url' => 'https://x/y.gif'],
                    ['type' => 'rest', 'name' => 'Descanso', 'duration_seconds' => 15, 'gif_url' => null],
                    ['type' => 'exercise', 'name' => 'Burpees', 'duration_seconds' => 20, 'gif_url' => null],
                ]],
                ['title' => 'Sección 2', 'items' => [
                    ['type' => 'rest', 'name' => 'Hidratación', 'duration_seconds' => 30, 'gif_url' => null],
                    ['type' => 'exercise', 'name' => 'Abdominales', 'duration_seconds' => 40, 'gif_url' => null],
                ]],
            ],
        ];
        $this->putJson("/routines/{$routineId}", $payload)->assertOk();

        // Verificar persistencia y orden
        $this->assertDatabaseCount('sections', 2);
        $this->assertDatabaseCount('routine_items', 6);
        $this->assertDatabaseHas('routine_items', ['type' => 'rest', 'name' => 'Hidratación', 'duration_seconds' => 30]);
        $this->assertDatabaseHas('routine_items', ['type' => 'exercise', 'gif_url' => 'https://x/y.gif']);

        // JSON del timer: orden exacto y total = 30+30+15+20+30+40 = 165
        $json = $this->getJson("/routines/{$routineId}/json")->assertOk()->json();
        $this->assertSame(165, $json['total_seconds']);
        $this->assertSame(
            ['Flexiones', 'Sentadillas', 'Descanso', 'Burpees', 'Hidratación', 'Abdominales'],
            array_column($json['flat'], 'name')
        );
        $this->assertSame(
            ['exercise', 'exercise', 'rest', 'exercise', 'rest', 'exercise'],
            array_column($json['flat'], 'type')
        );

        // Dashboard muestra las 3 pestañas
        $dash = $this->get('/')->assertOk();
        $dash->assertSee('Crear rutinas')->assertSee('Timer')->assertSee('Configuraci');

        // Cambiar username y password
        $this->put('/settings/username', ['username' => 'coach'])->assertRedirect();
        $this->assertDatabaseHas('users', ['username' => 'coach']);
        $this->put('/settings/password', [
            'current_password' => 'admin123',
            'password' => 'nueva123',
            'password_confirmation' => 'nueva123',
        ])->assertRedirect();
        $this->post('/logout');
        $this->post('/login', ['username' => 'coach', 'password' => 'nueva123'])->assertRedirect('/');
        $this->assertAuthenticated();
    }

    public function test_gif_upload_validation_and_gif_path_roundtrip(): void
    {
        $this->post('/login', ['username' => 'admin', 'password' => 'admin123']);

        // Sin archivo -> 422 (no toca el bucket)
        $this->postJson('/uploads/gif', [])->assertStatus(422);

        // Archivo no imagen -> 422 (no toca el bucket)
        $this->postJson('/uploads/gif', [
            'gif' => \Illuminate\Http\UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
        ])->assertStatus(422);

        // Invitado no puede subir
        $this->post('/logout');
        $this->postJson('/uploads/gif', [])->assertUnauthorized();

        // gif_path se guarda y el timer lo resuelve (sin bucket devuelve null, no rompe)
        $this->post('/login', ['username' => 'admin', 'password' => 'admin123']);
        $this->post('/routines', ['name' => 'ConGif']);
        $routineId = \App\Models\Routine::where('name', 'ConGif')->first()->id;
        $this->putJson("/routines/{$routineId}", [
            'name' => 'ConGif',
            'sections' => [[
                'title' => 'S1',
                'items' => [[
                    'type' => 'exercise', 'name' => 'Saltos',
                    'duration_seconds' => 20,
                    'gif_url' => null, 'gif_path' => 'gif-fake-123.gif',
                ]],
            ]],
        ])->assertOk();
        $this->assertDatabaseHas('routine_items', ['name' => 'Saltos', 'gif_path' => 'gif-fake-123.gif']);
        $json = $this->getJson("/routines/{$routineId}/json")->assertOk()->json();
        $this->assertArrayHasKey('gif_url', $json['flat'][0]);
    }
}
