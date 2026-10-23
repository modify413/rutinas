<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimerListFontSizeTest extends TestCase
{
    use RefreshDatabase;

    public function test_size_is_saved_for_current_user_and_applied_to_timer(): void
    {
        $user = User::factory()->create(['username' => 'alumno', 'role' => 'student']);
        $other = User::factory()->create(['username' => 'otro', 'role' => 'student']);

        $this->actingAs($user)->put('/settings/list-font-size', ['timer_list_font_size' => 28])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(28, $user->fresh()->timer_list_font_size);
        $this->assertSame(16, $other->fresh()->timer_list_font_size);
        $this->get('/')->assertOk()->assertSee('--timer-list-font-size:28px', false);
    }

    public function test_invalid_sizes_do_not_change_preference(): void
    {
        $user = User::factory()->create(['username' => 'alumno', 'role' => 'student']);
        $this->actingAs($user);

        foreach ([11, 41, 20.5, 'grande', null] as $size) {
            $this->put('/settings/list-font-size', ['timer_list_font_size' => $size])
                ->assertSessionHasErrors('timer_list_font_size');
        }

        $this->assertSame(16, $user->fresh()->timer_list_font_size);
    }

    public function test_guest_cannot_change_list_font_size(): void
    {
        $this->put('/settings/list-font-size', ['timer_list_font_size' => 28])
            ->assertRedirect('/login');
    }

    public function test_fullscreen_controls_preference_is_saved_and_can_be_disabled(): void
    {
        $user = User::factory()->create(['username' => 'alumno', 'role' => 'student']);
        $this->actingAs($user);

        $this->put('/settings/fullscreen-controls', ['timer_hide_controls' => 1])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue($user->fresh()->timer_hide_controls);
        $this->get('/')->assertOk()->assertSee('id="timer-fs" class="hide-bottom-controls"', false);

        $this->put('/settings/fullscreen-controls', ['timer_hide_controls' => 0])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertFalse($user->fresh()->timer_hide_controls);
        $this->get('/')->assertOk()->assertSee('id="timer-fs" class=""', false);

        $this->put('/settings/fullscreen-controls', ['timer_hide_controls' => 'invalid'])
            ->assertSessionHasErrors('timer_hide_controls');
        $this->assertFalse($user->fresh()->timer_hide_controls);
        $this->post('/logout');
        $this->put('/settings/fullscreen-controls', ['timer_hide_controls' => 1])->assertRedirect('/login');
    }
}
