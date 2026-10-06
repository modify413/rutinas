<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PasswordRecoveryController extends Controller
{
    public function showForgot()
    {
        return view('auth.forgot');
    }

    public function askQuestion(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
        ]);

        $user = User::where('username', $data['username'])->first();

        if (! $user || ! $user->security_question || ! $user->security_answer) {
            return back()->withErrors([
                'username' => 'No hay pregunta de seguridad configurada para ese usuario.',
            ])->onlyInput('username');
        }

        return view('auth.verify', ['username' => $user->username, 'question' => $user->security_question]);
    }

    public function verifyAnswer(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'answer' => ['required', 'string'],
        ]);

        $user = User::where('username', $data['username'])->first();

        if (! $user || ! $user->security_answer
            || ! Hash::check(User::normalizeAnswer($data['answer']), $user->security_answer)) {
            return back()->withErrors(['answer' => 'Respuesta incorrecta.'])->onlyInput('username');
        }

        $request->session()->put('recovery_user_id', $user->id);

        return redirect()->route('password.reset.form');
    }

    public function showReset(Request $request)
    {
        if (! $request->session()->has('recovery_user_id')) {
            return redirect()->route('password.forgot');
        }

        return view('auth.reset');
    }

    public function reset(Request $request)
    {
        $userId = $request->session()->get('recovery_user_id');
        if (! $userId) {
            return redirect()->route('password.forgot');
        }

        $data = $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = User::findOrFail($userId);
        $user->update(['password' => Hash::make($data['password'])]);
        $request->session()->forget('recovery_user_id');

        return redirect()->route('login')->with('status', 'Contraseña actualizada. Ya puedes entrar.');
    }
}
