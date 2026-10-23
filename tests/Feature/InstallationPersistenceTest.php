<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InstallationPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_startup_preserves_renamed_administrator_and_password(): void
    {
        $this->seed(AdminUserSeeder::class);
        $admin = User::where('username', 'admin')->firstOrFail();
        $admin->update(['username' => 'profesor', 'password' => Hash::make('mi-clave-nueva')]);

        $this->seed(AdminUserSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $this->post('/login', ['username' => 'profesor', 'password' => 'mi-clave-nueva'])
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($admin);
    }
}
