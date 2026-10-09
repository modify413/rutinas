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
    {        $this->post('/login', ['username' => 'admin', 'password' => 'admin123']);

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

    public function test_section_reps_expand_in_timer_and_total(): void
    {
        $this->post('/login', ['username' => 'admin', 'password' => 'admin123']);
        $this->post('/routines', ['name' => 'Reps']);
        $routineId = \App\Models\Routine::where('name', 'Reps')->first()->id;
        $this->putJson("/routines/{$routineId}", [
            'name' => 'Reps',
            'sections' => [[
                'title' => 'Bloque', 'reps' => 3,
                'items' => [
                    ['type' => 'exercise', 'name' => 'A', 'duration_seconds' => 10, 'gif_url' => null],
                    ['type' => 'rest', 'name' => 'D', 'duration_seconds' => 5, 'gif_url' => null],
                ],
            ]],
        ])->assertOk();

        $json = $this->getJson("/routines/{$routineId}/json")->assertOk()->json();
        // 2 elementos x 3 vueltas = 6, total (10+5)*3 = 45
        $this->assertCount(6, $json['flat']);
        $this->assertSame(45, $json['total_seconds']);
        $this->assertSame('Bloque · vuelta 2/3', $json['flat'][2]['section']);
        $this->assertSame(['exercise', 'rest', 'exercise', 'rest', 'exercise', 'rest'],
            array_column($json['flat'], 'type'));
    }

    public function test_library_crud(): void
    {
        $this->post('/login', ['username' => 'admin', 'password' => 'admin123']);

        $res = $this->postJson('/library', [
            'name' => 'Flexiones', 'duration_seconds' => 30,
            'gif_url' => 'https://x/y.gif', 'gif_path' => null,
        ])->assertCreated();
        $id = $res->json('id');
        $this->assertDatabaseHas('library_exercises', ['id' => $id, 'name' => 'Flexiones']);

        $this->postJson('/library', ['name' => '', 'duration_seconds' => 30])->assertStatus(422);

        $this->deleteJson("/library/{$id}")->assertOk();
        $this->assertDatabaseMissing('library_exercises', ['id' => $id]);

        // Dashboard expone la biblioteca
        $this->get('/')->assertOk()->assertSee('Mi biblioteca');
    }

    public function test_library_update(): void
    {
        $this->post('/login', ['username' => 'admin', 'password' => 'admin123']);
        $id = $this->postJson('/library', [
            'name' => 'Original', 'duration_seconds' => 30, 'gif_url' => null,
        ])->assertCreated()->json('id');

        $this->putJson("/library/{$id}", [
            'name' => 'Editado', 'duration_seconds' => 45, 'gif_url' => 'https://x/z.gif',
        ])->assertOk();
        $this->assertDatabaseHas('library_exercises', [
            'id' => $id, 'name' => 'Editado', 'duration_seconds' => 45,
        ]);

        $this->putJson("/library/{$id}", ['name' => '', 'duration_seconds' => 45])
            ->assertStatus(422);
    }

    public function test_reps_run_round_robin_across_sections(): void
    {
        $this->post('/login', ['username' => 'admin', 'password' => 'admin123']);
        $this->post('/routines', ['name' => 'Vueltas']);
        $routineId = \App\Models\Routine::where('name', 'Vueltas')->first()->id;
        $this->putJson("/routines/{$routineId}", [
            'name' => 'Vueltas',
            'sections' => [
                ['title' => 'S1', 'reps' => 2, 'items' => [
                    ['type' => 'exercise', 'name' => 'A', 'duration_seconds' => 10, 'gif_url' => null],
                ]],
                ['title' => 'S2', 'reps' => 3, 'items' => [
                    ['type' => 'exercise', 'name' => 'B', 'duration_seconds' => 5, 'gif_url' => null],
                ]],
            ],
        ])->assertOk();

        $json = $this->getJson("/routines/{$routineId}/json")->assertOk()->json();
        // S1,S2,S1,S2,S2 con total 10*2+5*3 = 35
        $this->assertSame(['A', 'B', 'A', 'B', 'B'], array_column($json['flat'], 'name'));
        $this->assertSame(35, $json['total_seconds']);
        $this->assertSame('S1 · vuelta 2/2', $json['flat'][2]['section']);
        $this->assertSame('S2 · vuelta 3/3', $json['flat'][4]['section']);
    }

    public function test_dashboard_hides_editor_until_selection(): void
    {
        $this->post('/login', ['username' => 'admin', 'password' => 'admin123']);
        // Sin routine_id no hay rutina preseleccionada: el editor queda oculto
        $dash = $this->get('/')->assertOk();
        $dash->assertSee('id="editor-empty"', false);
        $dash->assertSee('Biblioteca', false);
        $dash->assertSee('+ Agregar desde biblioteca', false);
        $dash->assertSee('id="lib-modal"', false);
        // Guard anti doble-submit presente
        $dash->assertSee('dataset.submitted', false);
        $dash->assertSee('withBtn', false);
        $dash->assertSee('t-video', false);
    }

    public function test_video_upload_rules_and_kind(): void
    {
        // Detección de tipo por extensión
        $this->assertSame('video', \App\Http\Controllers\GifUploadController::mediaKind('clip.mp4'));
        $this->assertSame('video', \App\Http\Controllers\GifUploadController::mediaKind('https://x/y.webm?t=1'));
        $this->assertSame('image', \App\Http\Controllers\GifUploadController::mediaKind('anim.gif'));
        $this->assertSame('image', \App\Http\Controllers\GifUploadController::mediaKind(null));

        $this->post('/login', ['username' => 'admin', 'password' => 'admin123']);

        // Archivo demasiado grande -> 422 sin tocar el bucket
        $res = $this->postJson('/uploads/gif', [
            'gif' => \Illuminate\Http\UploadedFile::fake()->create('big.mp4', 26000, 'video/mp4'),
        ])->assertStatus(422);
        $this->assertStringContainsString('25 MB', $res->json('message', json_encode($res->json())));

        // Tipo no permitido -> 422
        $this->postJson('/uploads/gif', [
            'gif' => \Illuminate\Http\UploadedFile::fake()->create('x.txt', 10, 'text/plain'),
        ])->assertStatus(422);

        // El timer informa el kind de cada elemento
        $this->post('/routines', ['name' => 'Mix']);
        $routineId = \App\Models\Routine::where('name', 'Mix')->first()->id;
        $this->putJson("/routines/{$routineId}", [
            'name' => 'Mix',
            'sections' => [[
                'title' => 'S1',
                'items' => [
                    ['type' => 'exercise', 'name' => 'Clip', 'duration_seconds' => 15, 'gif_url' => 'https://x/clip.mp4'],
                    ['type' => 'exercise', 'name' => 'Gif', 'duration_seconds' => 15, 'gif_url' => 'https://x/a.gif'],
                ],
            ]],
        ])->assertOk();
        $json = $this->getJson("/routines/{$routineId}/json")->assertOk()->json();
        $this->assertSame('video', $json['flat'][0]['kind']);
        $this->assertSame('image', $json['flat'][1]['kind']);
    }

    public function test_student_sees_only_teacher_routines_and_timer(): void
    {
        $this->post('/login', ['username' => 'admin', 'password' => 'admin123']);
        $adminId = auth()->id();

        // Rutina del profesor
        $this->post('/routines', ['name' => 'Profe']);
        $routineId = \App\Models\Routine::where('name', 'Profe')->first()->id;
        $this->putJson("/routines/{$routineId}", [
            'name' => 'Profe',
            'sections' => [[
                'title' => 'S1',
                'items' => [['type' => 'exercise', 'name' => 'A', 'duration_seconds' => 10, 'gif_url' => null]],
            ]],
        ])->assertOk();

        // Crear alumno
        $this->post('/students', ['username' => 'alumno1', 'password' => 'clave123'])
            ->assertRedirect();
        $this->assertDatabaseHas('users', ['username' => 'alumno1', 'role' => 'student', 'created_by' => $adminId]);
        $this->post('/logout');

        // El alumno entra y ve Timer + Configuración, no el resto
        $dash = $this->get('/')->assertRedirect('/login');
        $this->post('/login', ['username' => 'alumno1', 'password' => 'clave123'])->assertRedirect('/');
        $dash = $this->get('/')->assertOk();
        $dash->assertSee('data-tab="timer"', false);
        $dash->assertSee('data-tab="config"', false);
        $dash->assertDontSee('data-tab="rutinas"');
        $dash->assertDontSee('data-tab="biblioteca"');
        $dash->assertDontSee('data-tab="alumnos"');
        $dash->assertDontSee('id="panel-rutinas"');
        $dash->assertDontSee('id="panel-biblioteca"');

        // Puede ejecutar la rutina de su profesor...
        $this->getJson("/routines/{$routineId}/json")->assertOk();
        // ...pero no crear ni modificar nada
        $this->post('/routines', ['name' => 'Hack'])->assertForbidden();
        $this->putJson("/routines/{$routineId}", ['name' => 'Hack'])->assertForbidden();
        $this->deleteJson("/routines/{$routineId}")->assertForbidden();
        $this->postJson('/library', ['name' => 'X', 'duration_seconds' => 10])->assertForbidden();
        $this->put('/settings/username', ['username' => 'otro'])->assertForbidden();
        // ...pero sí cambiar su contraseña
        $this->put('/settings/password', [
            'current_password' => 'clave123',
            'password' => 'nueva456',
            'password_confirmation' => 'nueva456',
        ])->assertRedirect();
    }

    public function test_password_recovery_with_security_question(): void
    {
        $this->post('/login', ['username' => 'admin', 'password' => 'admin123']);

        // Configurar pregunta en Configuración
        $this->put('/settings/security', [
            'security_question' => '¿Color favorito?',
            'security_answer' => 'Azul',
            'security_answer_confirmation' => 'Azul',
        ])->assertRedirect();
        $this->assertDatabaseMissing('users', ['username' => 'admin', 'security_answer' => 'Azul']);
        $this->post('/logout');

        // Flujo de recuperación
        $this->get('/recuperar')->assertOk();
        $this->post('/recuperar', ['username' => 'admin'])->assertOk()
            ->assertSee('¿Color favorito?');
        $this->post('/recuperar/verificar', ['username' => 'admin', 'answer' => 'mal'])
            ->assertRedirect()->assertSessionHasErrors('answer');
        $this->post('/recuperar/verificar', ['username' => 'admin', 'answer' => 'AZUL'])
            ->assertRedirect(route('password.reset.form'));
        $this->get('/recuperar/nueva')->assertOk();
        $this->post('/recuperar/nueva', [
            'password' => 'recuperada1',
            'password_confirmation' => 'recuperada1',
        ])->assertRedirect(route('login'));

        // Entra con la nueva
        $this->post('/login', ['username' => 'admin', 'password' => 'recuperada1'])
            ->assertRedirect('/');
        $this->assertAuthenticated();
    }

    public function test_timer_repeat_mode_rounds_vs_frequency(): void
    {
        $this->post('/login', ['username' => 'admin', 'password' => 'admin123']);
        $this->post('/routines', ['name' => 'Modos']);
        $routineId = \App\Models\Routine::where('name', 'Modos')->first()->id;
        $payload = [
            'name' => 'Modos',
            'sections' => [
                ['title' => 'S1', 'reps' => 2, 'items' => [
                    ['type' => 'exercise', 'name' => 'A', 'duration_seconds' => 10, 'gif_url' => null],
                ]],
                ['title' => 'S2', 'reps' => 2, 'items' => [
                    ['type' => 'exercise', 'name' => 'B', 'duration_seconds' => 10, 'gif_url' => null],
                ]],
            ],
        ];
        $this->putJson("/routines/{$routineId}", $payload)->assertOk();

        // Por defecto: por vueltas S1,S2,S1,S2
        $json = $this->getJson("/routines/{$routineId}/json")->assertOk()->json();
        $this->assertSame(['A', 'B', 'A', 'B'], array_column($json['flat'], 'name'));
        $this->assertSame(40, $json['total_seconds']);

        // Cambio a frecuencia: S1,S1,S2,S2 (mismo total)
        $this->put('/settings/timer-mode', ['timer_repeat_mode' => 'frequency'])
            ->assertRedirect();
        $json = $this->getJson("/routines/{$routineId}/json")->assertOk()->json();
        $this->assertSame(['A', 'A', 'B', 'B'], array_column($json['flat'], 'name'));
        $this->assertSame(40, $json['total_seconds']);
        $this->assertSame('S1 · vuelta 2/2', $json['flat'][1]['section']);

        // Valor inválido
        $this->put('/settings/timer-mode', ['timer_repeat_mode' => 'otro'])
            ->assertSessionHasErrors('timer_repeat_mode');

        // El dashboard muestra los radios con el modo actual
        $this->get('/')->assertOk()->assertSee('Repetición del timer', false);
    }

    public function test_beep_volume_setting(): void
    {
        $this->post('/login', ['username' => 'admin', 'password' => 'admin123']);

        // Valor por defecto
        $this->assertSame(80, auth()->user()->beep_volume);

        $this->put('/settings/volume', ['beep_volume' => 30])->assertRedirect();
        $this->assertDatabaseHas('users', ['username' => 'admin', 'beep_volume' => 30]);

        // Fuera de rango
        $this->put('/settings/volume', ['beep_volume' => 150])
            ->assertSessionHasErrors('beep_volume');

        // El dashboard expone el volumen al JS y la tarjeta
        $this->get('/')->assertOk()
            ->assertSee('Volumen del bip', false)
            ->assertSee('BEEP_VOLUME', false);
    }
}
