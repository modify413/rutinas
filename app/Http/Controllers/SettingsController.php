<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
            'security_answer' => \App\Models\User::normalizeAnswer($data['security_answer']),
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
    {        $data = $request->validate([
            'timer_style' => ['required', 'string', 'in:simple,lista,simple_lista_next'],
        ], [], [
            'timer_style' => 'estilo del timer',
        ]);

        $request->user()->update(['timer_style' => $data['timer_style']]);

        return back()->with('status', 'Estilo del timer actualizado.');
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

        // Disco local (sin internet): misma carpeta que el resto de medios.
        if (config('media.disk') === 'local') {
            try {
                $stored = \Illuminate\Support\Facades\Storage::disk('uploads')->putFile('logos', $file);
                if (! $stored) {
                    throw new \RuntimeException('write');
                }
                $key = 'local/'.$stored;
            } catch (\Throwable $e) {
                report($e);

                return back()->withErrors(['logo' => 'No se pudo guardar el logo.']);
            }

            try {
                if (($old = \App\Models\AppSetting::logoPath()) && $old !== $key
                    && \App\Http\Controllers\GifUploadController::isLocalPath($old)) {
                    \Illuminate\Support\Facades\Storage::disk('uploads')->delete(substr($old, strlen('local/')));
                }
            } catch (\Throwable) {
            }

            \App\Models\AppSetting::setLogo($key);

            return back()->with('status', 'Logo actualizado.');
        }

        $key = 'logos/logo-'.\Illuminate\Support\Str::uuid().'.'.$file->getClientOriginalExtension();

        try {
            \App\Http\Controllers\GifUploadController::client()->putObject([
                'Bucket' => \App\Http\Controllers\GifUploadController::bucket(),
                'Key' => $key,
                'Body' => fopen($file->getRealPath(), 'rb'),
                'ContentType' => $file->getMimeType(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['logo' => 'No se pudo subir el logo al bucket.']);
        }

        // Borrar el anterior para no acumular archivos (sin romper si falla)
        try {
            if (($old = \App\Models\AppSetting::logoPath()) && $old !== $key) {
                \App\Http\Controllers\GifUploadController::client()->deleteObject([
                    'Bucket' => \App\Http\Controllers\GifUploadController::bucket(),
                    'Key' => $old,
                ]);
            }
        } catch (\Throwable) {
        }

        \App\Models\AppSetting::setLogo($key);

        return back()->with('status', 'Logo actualizado.');
    }
}
