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
}
