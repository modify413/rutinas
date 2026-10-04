<?php

namespace App\Http\Controllers;

use App\Models\Routine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoutineController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $routine = auth()->user()->routines()->create(['name' => $data['name']]);

        if ($request->expectsJson()) {
            return response()->json($routine, 201);
        }

        return redirect()->route('dashboard', ['routine_id' => $routine->id, 'tab' => 'rutinas'])
            ->with('status', 'Rutina creada.');
    }

    public function update(Request $request, Routine $routine)
    {
        $this->authorizeRoutine($routine);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sections' => ['sometimes', 'array'],
            'sections.*.id' => ['nullable', 'integer'],
            'sections.*.title' => ['nullable', 'string', 'max:255'],
            'sections.*.items' => ['sometimes', 'array'],
            'sections.*.items.*.id' => ['nullable', 'integer'],
            'sections.*.items.*.type' => ['required_with:sections.*.items', 'in:exercise,rest'],
            'sections.*.items.*.name' => ['required_with:sections.*.items', 'string', 'max:255'],
            'sections.*.items.*.duration_seconds' => ['required_with:sections.*.items', 'integer', 'min:1', 'max:86400'],
            'sections.*.items.*.gif_url' => ['nullable', 'string', 'max:2048'],
        ]);

        DB::transaction(function () use ($routine, $data) {
            $routine->update(['name' => $data['name']]);

            $sectionsInput = $data['sections'] ?? [];
            $keepSectionIds = [];
            foreach ($sectionsInput as $index => $sectionInput) {
                $section = null;
                if (! empty($sectionInput['id'])) {
                    $section = $routine->sections()->where('id', $sectionInput['id'])->first();
                }
                if (! $section) {
                    $section = $routine->sections()->create([
                        'title' => $sectionInput['title'] ?? ('Sección '.($index + 1)),
                        'position' => $index,
                    ]);
                } else {
                    $section->update([
                        'title' => $sectionInput['title'] ?? $section->title,
                        'position' => $index,
                    ]);
                }
                $keepSectionIds[] = $section->id;

                $keepItemIds = [];
                foreach (($sectionInput['items'] ?? []) as $itemIndex => $itemInput) {
                    $gif = $itemInput['gif_url'] ?? null;
                    $gif = is_string($gif) ? trim($gif) : null;
                    if ($gif === '') {
                        $gif = null;
                    }
                    // Solo los ejercicios pueden tener GIF
                    if (($itemInput['type'] ?? 'exercise') === 'rest') {
                        $gif = null;
                    }

                    $payload = [
                        'type' => $itemInput['type'],
                        'name' => $itemInput['name'],
                        'duration_seconds' => (int) $itemInput['duration_seconds'],
                        'gif_url' => $gif,
                        'position' => $itemIndex,
                    ];

                    if (! empty($itemInput['id'])) {
                        $item = $section->items()->where('id', $itemInput['id'])->first();
                        if ($item) {
                            $item->update($payload);
                            $keepItemIds[] = $item->id;
                            continue;
                        }
                    }
                    $created = $section->items()->create($payload);
                    $keepItemIds[] = $created->id;
                }
                // Eliminar items que ya no están
                $section->items()->whereNotIn('id', $keepItemIds)->delete();
            }
            // Eliminar secciones que ya no están
            $routine->sections()->whereNotIn('id', $keepSectionIds)->delete();
        });

        $routine->load(['sections.items']);

        if ($request->expectsJson()) {
            return response()->json($routine);
        }

        return redirect()->route('dashboard', ['routine_id' => $routine->id, 'tab' => 'rutinas'])
            ->with('status', 'Rutina guardada.');
    }

    public function destroy(Routine $routine)
    {
        $this->authorizeRoutine($routine);
        $routine->delete();

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('dashboard', ['tab' => 'rutinas'])->with('status', 'Rutina eliminada.');
    }

    public function json(Routine $routine)
    {
        $this->authorizeRoutine($routine);
        $routine->load(['sections.items']);

        $flat = [];
        foreach ($routine->sections as $section) {
            foreach ($section->items as $item) {
                $flat[] = [
                    'id' => $item->id,
                    'section' => $section->title,
                    'type' => $item->type,
                    'name' => $item->name,
                    'duration_seconds' => (int) $item->duration_seconds,
                    'gif_url' => $item->gif_url,
                ];
            }
        }

        return response()->json([
            'id' => $routine->id,
            'name' => $routine->name,
            'sections' => $routine->sections->map(fn ($s) => [
                'id' => $s->id,
                'title' => $s->title,
                'items' => $s->items->map(fn ($i) => [
                    'id' => $i->id,
                    'type' => $i->type,
                    'name' => $i->name,
                    'duration_seconds' => (int) $i->duration_seconds,
                    'gif_url' => $i->gif_url,
                ])->values(),
            ])->values(),
            'flat' => $flat,
            'total_seconds' => collect($flat)->sum('duration_seconds'),
        ]);
    }

    private function authorizeRoutine(Routine $routine): void
    {
        abort_unless($routine->user_id === auth()->id(), 403);
    }
}
