<?php

namespace Tests\Feature;

use App\Livewire\UserIndex;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_non_administrator_cannot_access_user_management(): void
    {
        $cashier = User::where('email', 'cajera@construir.local')->firstOrFail();

        $this->actingAs($cashier)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_administrator_can_create_user_with_role(): void
    {
        $admin = User::where('email', 'admin@construir.local')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(UserIndex::class)
            ->call('create')
            ->set('name', 'Vendedor nuevo')
            ->set('email', 'vendedor.nuevo@construir.local')
            ->set('roleName', 'Gerente')
            ->set('password', 'Fuerte123!')
            ->set('password_confirmation', 'Fuerte123!')
            ->call('save')
            ->assertHasNoErrors();

        $user = User::where('email', 'vendedor.nuevo@construir.local')->firstOrFail();
        $this->assertTrue($user->hasRole('Gerente'));
        $this->assertTrue(Hash::check('Fuerte123!', $user->password));
        $this->assertTrue((bool) $user->is_active);
    }

    public function test_last_administrator_cannot_be_deactivated_or_deleted(): void
    {
        $admin = User::where('email', 'admin@construir.local')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(UserIndex::class)
            ->call('toggle', $admin->id)
            ->assertHasErrors('form')
            ->call('delete', $admin->id)
            ->assertHasErrors('form');

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'is_active' => true, 'deleted_at' => null]);
    }

    public function test_inactive_user_cannot_authenticate(): void
    {
        Auth::logout();
        $user = User::factory()->create(['email' => 'inactive@construir.local', 'is_active' => false]);

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasErrors('form.email')
            ->assertNoRedirect();

        $this->assertGuest();
    }
}
