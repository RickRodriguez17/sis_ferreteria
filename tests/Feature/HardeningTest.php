<?php

namespace Tests\Feature;

use App\Livewire\UserIndex;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\TestCase;

class HardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_user_creation_rejects_a_weak_password_in_spanish(): void
    {
        $admin = User::where('email', 'admin@construir.local')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(UserIndex::class)
            ->call('create')
            ->set('name', 'Usuario débil')
            ->set('email', 'debil@example.test')
            ->set('roleName', 'Cajero')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('save')
            ->assertHasErrors(['password'])
            ->assertSee('debe contener');
    }

    public function test_user_creation_accepts_a_strong_password(): void
    {
        $admin = User::where('email', 'admin@construir.local')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(UserIndex::class)
            ->call('create')
            ->set('name', 'Usuario fuerte')
            ->set('email', 'fuerte@example.test')
            ->set('roleName', 'Cajero')
            ->set('password', 'Fuerte123!')
            ->set('password_confirmation', 'Fuerte123!')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'fuerte@example.test']);
    }

    public function test_backup_command_fails_cleanly_for_sqlite_test_configuration(): void
    {
        $this->artisan('backup:database')
            ->expectsOutput('El respaldo de base de datos requiere una conexión MySQL o MariaDB.')
            ->assertExitCode(1);
    }

    public function test_local_demo_login_still_works(): void
    {
        $this->assertTrue(Auth::attempt([
            'email' => 'admin@construir.local',
            'password' => 'password',
            'is_active' => true,
        ]));
    }
}
