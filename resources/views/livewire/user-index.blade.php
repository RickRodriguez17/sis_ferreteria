<div>
    <x-pagetitle title="Usuarios" icon="bi-people" section="Administración" subtitle="Administra accesos, roles y estado de los usuarios.">
        <x-slot:actions>
            @can('create', \App\Models\User::class)
                <button wire:click="create" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Nuevo usuario</button>
            @endcan
        </x-slot:actions>
    </x-pagetitle>

    @error('form')<div class="mb-4 rounded-lg bg-rose-50 p-3 text-sm text-rose-700">{{ $message }}</div>@enderror
    <div class="rounded-xl bg-white p-5 shadow-sm">
        <x-table-toolbar wire:model.live.debounce.300ms="search">
            <select wire:model.live="role" class="rounded-lg border-slate-300 text-sm">
                <option value="">Todos los roles</option>
                @foreach($roles as $availableRole)<option value="{{ $availableRole->name }}">{{ $availableRole->name }}</option>@endforeach
            </select>
            <select wire:model.live="status" class="rounded-lg border-slate-300 text-sm">
                <option value="">Todos los estados</option>
                <option value="active">Activos</option>
                <option value="inactive">Inactivos</option>
            </select>
        </x-table-toolbar>
        <div class="mt-5 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b text-xs uppercase text-slate-500"><tr><th class="px-3 py-3">Usuario</th><th class="px-3 py-3">Correo</th><th class="px-3 py-3">Rol(es)</th><th class="px-3 py-3">Estado</th><th class="px-3 py-3 text-right">Acciones</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $user)
                        <tr wire:key="user-{{ $user->id }}">
                            <td class="px-3 py-3 font-medium">{{ $user->name }}</td>
                            <td class="px-3 py-3">{{ $user->email }}</td>
                            <td class="px-3 py-3"><div class="flex flex-wrap gap-1">@forelse($user->roles as $userRole)<span class="rounded-full bg-indigo-100 px-2 py-1 text-xs text-indigo-700">{{ $userRole->name }}</span>@empty<span class="text-slate-400">Sin rol</span>@endforelse</div></td>
                            <td class="px-3 py-3"><span class="rounded-full px-2 py-1 text-xs {{ $user->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $user->is_active ? 'Activo' : 'Inactivo' }}</span></td>
                            <td class="px-3 py-3 text-right">
                                @can('update', $user)<button wire:click="edit({{ $user->id }})" class="text-indigo-600">Editar</button><button wire:click="toggle({{ $user->id }})" wire:confirm="{{ $user->is_active ? '¿Desactivar este usuario?' : '¿Activar este usuario?' }}" class="ml-3 text-amber-600">{{ $user->is_active ? 'Desactivar' : 'Activar' }}</button>@endcan
                                @can('delete', $user) @if(! $user->is(auth()->user()))<button wire:click="delete({{ $user->id }})" wire:confirm="¿Eliminar este usuario? Esta acción no se puede deshacer." class="ml-3 text-rose-600">Eliminar</button>@endif @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-3 py-12 text-center text-slate-500">No hay usuarios para los filtros seleccionados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $users->links() }}</div>
    </div>

    <x-modal name="user-record" maxWidth="xl">
        <div class="p-6">
            <h2 class="text-lg font-semibold">{{ $editingId ? 'Editar usuario' : 'Nuevo usuario' }}</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div><x-input-label value="Nombre"/><x-text-input wire:model="name" class="mt-1 w-full"/><x-input-error :messages="$errors->get('name')"/></div>
                <div><x-input-label value="Correo electrónico"/><x-text-input type="email" wire:model="email" class="mt-1 w-full"/><x-input-error :messages="$errors->get('email')"/></div>
                <div><x-input-label value="Rol"/><select wire:model="roleName" class="mt-1 w-full rounded-lg border-slate-300"><option value="">Seleccionar rol</option>@foreach($roles as $availableRole)<option value="{{ $availableRole->name }}">{{ $availableRole->name }}</option>@endforeach</select><x-input-error :messages="$errors->get('roleName')"/></div>
                <div><x-input-label value="Estado"/><label class="mt-3 flex items-center gap-2 text-sm"><input type="checkbox" wire:model="isActive" class="rounded border-slate-300 text-indigo-600"> Usuario activo</label><x-input-error :messages="$errors->get('isActive')"/></div>
                <div class="sm:col-span-2"><x-input-label for="user-password" :value="$editingId ? 'Nueva contraseña (opcional)' : 'Contraseña'"/><x-text-input id="user-password" type="password" wire:model="password" class="mt-1 w-full" autocomplete="new-password"/><p class="mt-1 text-xs text-slate-500">{{ $editingId ? 'Déjala vacía para conservar la contraseña actual.' : 'Mínimo 8 caracteres.' }}</p><x-input-error :messages="$errors->get('password')"/></div>
                <div class="sm:col-span-2"><x-input-label for="user-password-confirmation" value="Confirmar contraseña"/><x-text-input id="user-password-confirmation" type="password" wire:model="password_confirmation" class="mt-1 w-full" autocomplete="new-password"/></div>
            </div>
            <div class="mt-6 flex justify-end gap-3"><button wire:click="closeModal" class="rounded-lg border px-4 py-2">Cancelar</button><button wire:click="save" class="rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white">Guardar</button></div>
        </div>
    </x-modal>
</div>
