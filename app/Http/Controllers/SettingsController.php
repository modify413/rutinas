<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function updateUsername(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
        ]);

        $user->update(['username' => $data['username']]);

        return back()->with('status', 'Nombre de usuario actualizado.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [], [
            'current_password' => 'contraseña actual',
            'password' => 'nueva contraseña',
        ]);

        $request->user()->update([
            'password' => Hash::make($request->input('password')),
        ]);

        return back()->with('status', 'Contraseña actualizada.');
    }

    public function updateSecurity(Request $request)
    {
        $data = $request->validate([
            'security_question' => ['required', 'string', 'max:500'],
            'security_answer' => ['required', 'string', 'max:255', 'confirmed'],
        ], [], [
            'security_question' => 'pregunta de seguridad',
            'security_answer' => 'respuesta',
        ]);

        $request->user()->update([
            'security_question' => $data['security_question'],
            // Se normaliza antes de guardar: el cast 'hashed' la cifra.
            'security_answer' => User::normalizeAnswer($data['security_answer']),
        ]);

        return back()->with('status', 'Pregunta de seguridad guardada.');
    }

    public function updateTimerMode(Request $request)
    {
        $data = $request->validate([
            'timer_repeat_mode' => ['required', 'string', 'in:rounds,frequency'],
        ], [], [
            'timer_repeat_mode' => 'repetición del timer',
        ]);

        $request->user()->update(['timer_repeat_mode' => $data['timer_repeat_mode']]);

        return back()->with('status', 'Repetición del timer actualizada.');
    }

    public function updateVolume(Request $request)
    {
        $data = $request->validate([
            'beep_volume' => ['required', 'integer', 'min:0', 'max:300'],
        ], [], [
            'beep_volume' => 'volumen',
        ]);

        $request->user()->update(['beep_volume' => (int) $data['beep_volume']]);

        return back()->with('status', 'Volumen del bip actualizado.');
    }

    public function updateStyle(Request $request)
    {
        $data = $request->validate([
            'timer_style' => ['required', 'string', 'in:simple,lista,simple_lista_next'],
        ], [], [
            'timer_style' => 'estilo del timer',
        ]);

        $request->user()->update(['timer_style' => $data['timer_style']]);

        return back()->with('status', 'Estilo del timer actualizado.');
    }

    public function updateListFontSize(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'timer_list_font_size' => ['required', 'integer', 'min:12', 'max:40'],
        ], [], ['timer_list_font_size' => 'tamaño de letra de la lista']);

        $request->user()->update(['timer_list_font_size' => (int) $data['timer_list_font_size']]);

        return back()->with('status', 'Tamaño de letra de la lista actualizado.');
    }

    public function updateFullscreenControls(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'timer_hide_controls' => ['required', 'boolean'],
        ]);

        $request->user()->update(['timer_hide_controls' => (bool) $data['timer_hide_controls']]);

        return back()->with('status', 'Controles de pantalla completa actualizados.');
    }

    public function updateLogo(Request $request)
    {
        $request->validate([
            'logo' => [
                'required', 'file', 'max:2048',
                'mimetypes:image/png,image/jpeg,image/webp,image/gif,image/svg+xml',
            ],
        ], [
            'logo.required' => 'Elige un archivo de imagen.',
            'logo.max' => 'El logo supera los 2 MB.',
            'logo.mimetypes' => 'Formato no permitido. Usa PNG, JPG, WEBP, GIF o SVG.',
        ]);

        $file = $request->file('logo');
        $key = 'logos/logo-'.Str::uuid().'.'.$file->extension();

        try {
            $key = GifUploadController::storeMedia($file, $key);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['logo' => 'No se pudo guardar el logo.']);
        }

        // Borrar el anterior para no acumular archivos (sin romper si falla)
        try {
            if (($old = AppSetting::logoPath()) && $old !== $key) {
                GifUploadController::deleteMedia($old);
            }
        } catch (\Throwable) {
        }

        AppSetting::setLogo($key);

        return back()->with('status', 'Logo actualizado.');
    }
}
