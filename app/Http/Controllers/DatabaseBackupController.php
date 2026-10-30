<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatabaseBackupController extends Controller
{
    private const TABLES = ['users', 'routines', 'sections', 'routine_items', 'library_exercises', 'app_settings'];

    public function export(): StreamedResponse
    {
        $backup = DB::transaction(function (): array {
            $tables = [];
            foreach (self::TABLES as $table) {
                $tables[$table] = DB::table($table)->get()->map(fn ($row) => (array) $row)->all();
            }

            return ['format' => 'rutinas-backup', 'version' => 1, 'tables' => $tables];
        });

        return response()->streamDownload(function () use ($backup): void {
            echo json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }, 'rutinas-'.now()->format('Y-m-d-His').'.json', [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'backup' => ['required', 'file', 'max:25600'],
            'replace_data' => ['accepted'],
        ]);

        try {
            $backup = json_decode($request->file('backup')->get(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ValidationException::withMessages(['backup' => 'El archivo no contiene JSON válido.']);
        }

        $this->validateBackup($backup);

        try {
            DB::transaction(function () use ($backup): void {
                foreach (array_reverse(self::TABLES) as $table) {
                    DB::table($table)->delete();
                }
                $parents = [];
                foreach (self::TABLES as $table) {
                    foreach ($backup['tables'][$table] as $row) {
                        if ($table === 'users') {
                            $parents[$row['id']] = $row['created_by'];
                            $row['created_by'] = null;
                            $row['remember_token'] = null;
                        }
                        DB::table($table)->insert($row);
                    }
                    if ($table === 'users') {
                        foreach ($parents as $id => $parent) {
                            DB::table('users')->where('id', $id)->update(['created_by' => $parent]);
                        }
                    }
                }
                DB::table('sessions')->delete();
                DB::table('password_reset_tokens')->delete();
                if (DB::getDriverName() === 'pgsql') {
                    foreach (array_diff(self::TABLES, ['app_settings']) as $table) {
                        DB::select("SELECT setval(pg_get_serial_sequence(?, 'id'), COALESCE(MAX(id), 1), MAX(id) IS NOT NULL) FROM \"{$table}\"", [$table]);
                    }
                }
            });
        } catch (\Throwable $e) {
            report($e);
            throw ValidationException::withMessages(['backup' => 'No se pudo importar: los datos no son compatibles. La base anterior se conservó.']);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Base importada. Ingresá con un usuario del respaldo.');
    }

    private function validateBackup(mixed $backup): void
    {
        $invalid = fn () => throw ValidationException::withMessages(['backup' => 'El respaldo es incompatible o está incompleto.']);
        if (! is_array($backup) || ($backup['format'] ?? null) !== 'rutinas-backup' || ($backup['version'] ?? null) !== 1
            || ! is_array($backup['tables'] ?? null)) {
            $invalid();
        }
        $names = array_keys($backup['tables']);
        sort($names);
        $expected = self::TABLES;
        sort($expected);
        if ($names !== $expected) {
            $invalid();
        }
        foreach (self::TABLES as $table) {
            if (! is_array($backup['tables'][$table]) || ! array_is_list($backup['tables'][$table])) {
                $invalid();
            }
            $columns = Schema::getColumnListing($table);
            sort($columns);
            foreach ($backup['tables'][$table] as $row) {
                if (! is_array($row)) {
                    $invalid();
                }
                $keys = array_keys($row);
                sort($keys);
                if ($keys !== $columns) {
                    $invalid();
                }
                foreach ($row as $value) {
                    if (! is_scalar($value) && $value !== null) {
                        $invalid();
                    }
                }
            }
        }
        if (! collect($backup['tables']['users'])->contains(fn ($user) => ($user['role'] ?? null) === 'admin' && filled($user['username']) && filled($user['password']))) {
            $invalid();
        }
    }
}
