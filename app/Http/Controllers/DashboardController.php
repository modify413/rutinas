<?php

namespace App\Http\Controllers;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $routines = $user->visibleRoutines()->with(['sections.items'])->orderBy('created_at')->get();
        $library = $user->isAdmin()
            ? $user->libraryExercises()->orderBy('name')->get()
            : collect();
        $students = $user->isAdmin()
            ? $user->students()->orderBy('username')->get()
            : collect();

        return view('dashboard', compact('routines', 'user', 'library', 'students'));
    }
}
