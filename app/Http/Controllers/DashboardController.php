<?php

namespace App\Http\Controllers;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $routines = $user->routines()->with(['sections.items'])->orderBy('created_at')->get();
        $library = $user->libraryExercises()->orderBy('name')->get();

        return view('dashboard', compact('routines', 'user', 'library'));
    }
}
