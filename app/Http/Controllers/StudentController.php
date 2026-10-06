<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $student = User::create([
            'name' => $data['username'],
            'username' => $data['username'],
            'email' => $data['username'].'@alumnos.local',
            'password' => Hash::make($data['password']),
            'role' => 'student',
            'created_by' => $request->user()->id,
        ]);

        if ($request->expectsJson()) {
            return response()->json($student->makeHidden(['password']), 201);
        }

        return redirect()->route('dashboard', ['tab' => 'alumnos'])
            ->with('status', "Cuenta de alumno '{$student->username}' creada.");
    }

    public function resetPassword(Request $request, User $student)
    {
        abort_unless($student->created_by === $request->user()->id, 403);

        $data = $request->validate([
            'password' => ['required', 'string', 'min:6'],
        ]);

        $student->update(['password' => Hash::make($data['password'])]);

        return back()->with('status', "Contraseña de '{$student->username}' actualizada.");
    }

    public function destroy(Request $request, User $student)
    {
        abort_unless($student->created_by === $request->user()->id, 403);
        $student->delete();

        return back()->with('status', "Cuenta de '{$student->username}' eliminada.");
    }
}
