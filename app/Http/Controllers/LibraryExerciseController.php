<?php

namespace App\Http\Controllers;

use App\Models\LibraryExercise;
use Illuminate\Http\Request;

class LibraryExerciseController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'duration_seconds' => ['required', 'integer', 'min:1', 'max:86400'],
            'gif_url' => ['nullable', 'string', 'max:2048'],
            'gif_path' => ['nullable', 'string', 'max:1024'],
        ]);

        $gif = trim((string) ($data['gif_url'] ?? ''));
        $gifPath = trim((string) ($data['gif_path'] ?? ''));

        $item = $request->user()->libraryExercises()->create([
            'name' => $data['name'],
            'duration_seconds' => (int) $data['duration_seconds'],
            'gif_url' => $gifPath ? null : ($gif !== '' ? $gif : null),
            'gif_path' => $gifPath !== '' ? $gifPath : null,
        ]);

        if ($request->expectsJson()) {
            return response()->json($item, 201);
        }

        return back();
    }

    public function update(Request $request, LibraryExercise $libraryExercise)
    {
        abort_unless($libraryExercise->user_id === auth()->id(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'duration_seconds' => ['required', 'integer', 'min:1', 'max:86400'],
            'gif_url' => ['nullable', 'string', 'max:2048'],
            'gif_path' => ['nullable', 'string', 'max:1024'],
        ]);

        $gif = trim((string) ($data['gif_url'] ?? ''));
        $gifPath = trim((string) ($data['gif_path'] ?? ''));

        $libraryExercise->update([
            'name' => $data['name'],
            'duration_seconds' => (int) $data['duration_seconds'],
            'gif_url' => $gifPath ? null : ($gif !== '' ? $gif : null),
            'gif_path' => $gifPath !== '' ? $gifPath : null,
        ]);

        if ($request->expectsJson()) {
            return response()->json($libraryExercise->fresh());
        }

        return back();
    }

    public function destroy(LibraryExercise $libraryExercise)
    {
        abort_unless($libraryExercise->user_id === auth()->id(), 403);
        $libraryExercise->delete();

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back();
    }
}
