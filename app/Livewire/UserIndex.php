<?php

namespace App\Livewire;

use App\Livewire\Traits\WithTableState;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class UserIndex extends Component
{
    use WithTableState;

    public string $role = '';

    public string $status = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $roleName = 'Cajero';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $isActive = true;

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);
    }

    public function create(): void
    {
        Gate::authorize('create', User::class);
        $this->resetForm();
        $this->showModal = true;
        $this->dispatch('open-modal', 'user-record');
    }

    public function edit(int $id): void
    {
        $user = User::findOrFail($id);
        Gate::authorize('update', $user);
        $this->editingId = $id;
        $this->fill([
            'name' => $user->name,
            'email' => $user->email,
            'roleName' => $user->getRoleNames()->first() ?? 'Cajero',
            'password' => '',
            'isActive' => (bool) $user->is_active,
        ]);
        $this->showModal = true;
        $this->dispatch('open-modal', 'user-record');
    }

    public function save(): void
    {
        $isEditing = $this->editingId !== null;
        $user = $isEditing ? User::findOrFail($this->editingId) : null;
        Gate::authorize($isEditing ? 'update' : 'create', $user ?? User::class);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$this->editingId],
            'roleName' => ['required', 'in:Administrador,Gerente,Cajero'],
            'password' => [$isEditing ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'isActive' => ['boolean'],
        ], [], [
            'name' => 'nombre',
            'email' => 'correo electrónico',
            'roleName' => 'rol',
            'password' => 'contraseña',
            'isActive' => 'estado',
        ]);

        if ($isEditing) {
            $error = $this->integrityError($user, $this->roleName, $this->isActive);
            if ($error !== null) {
                $this->rejectAction($error);

                return;
            }

            $user->fill([
                'name' => $this->name,
                'email' => $this->email,
                'is_active' => $this->isActive,
            ]);
            if ($this->password !== '') {
                $user->password = Hash::make($this->password);
            }
            $user->save();
            $user->syncRoles([$this->roleName]);
            $message = 'Usuario actualizado correctamente.';
        } else {
            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'is_active' => $this->isActive,
            ]);
            $user->assignRole($this->roleName);
            $message = 'Usuario creado correctamente.';
        }

        $this->closeModal();
        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function toggle(int $id): void
    {
        $user = User::findOrFail($id);
        Gate::authorize('update', $user);

        $nextStatus = ! (bool) $user->is_active;
        $error = $this->integrityError($user, $user->getRoleNames()->first() ?? '', $nextStatus);
        if ($error !== null) {
            $this->rejectAction($error);

            return;
        }

        $user->update(['is_active' => $nextStatus]);
        $this->dispatch('toast', message: $nextStatus ? 'Usuario activado correctamente.' : 'Usuario desactivado correctamente.', type: 'success');
    }

    public function delete(int $id): void
    {
        $user = User::findOrFail($id);
        Gate::authorize('delete', $user);

        $error = $this->integrityError($user, '', false, true);
        if ($error !== null) {
            $this->rejectAction($error);

            return;
        }

        $user->delete();
        $this->dispatch('toast', message: 'Usuario eliminado correctamente.', type: 'success');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->dispatch('close-modal', 'user-record');
        $this->resetForm();
    }

    public function updated($property): void
    {
        if (in_array($property, ['role', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $users = User::query()
            ->with('roles:id,name')
            ->when($this->search !== '', fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', '%'.$this->search.'%')->orWhere('email', 'like', '%'.$this->search.'%')))
            ->when($this->role !== '', fn ($query) => $query->role($this->role))
            ->when($this->status !== '', fn ($query) => $query->where('is_active', $this->status === 'active'))
            ->orderBy($this->sortBy === 'name' ? 'name' : $this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.user-index', [
            'users' => $users,
            'roles' => Role::query()->whereIn('name', ['Administrador', 'Gerente', 'Cajero'])->orderBy('name')->get(['id', 'name']),
        ])->layout('layouts.app');
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'email', 'password', 'password_confirmation']);
        $this->roleName = 'Cajero';
        $this->isActive = true;
        $this->resetValidation();
    }

    private function integrityError(User $user, string $role, bool $isActive, bool $deleting = false): ?string
    {
        if ($user->is(auth()->user()) && (! $isActive || $deleting)) {
            return 'No puedes desactivar ni eliminar tu propio usuario.';
        }

        $isAdmin = $user->hasRole('Administrador');
        if ($isAdmin && $role !== '' && $role !== 'Administrador') {
            return 'No se puede quitar el rol Administrador de un usuario administrador.';
        }

        $removesAdminAccess = $isAdmin && ($deleting || ! $isActive || $role !== 'Administrador');
        $activeAdmins = User::role('Administrador')->where('is_active', true)->count();
        if ($removesAdminAccess && $activeAdmins <= 1) {
            return 'No puedes quitar el acceso del último administrador activo.';
        }

        return null;
    }

    private function rejectAction(string $message): void
    {
        $this->addError('form', $message);
        $this->dispatch('alert', type: 'error', title: 'Operación no permitida', message: $message);
    }
}
