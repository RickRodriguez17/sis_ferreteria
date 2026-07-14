<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Administrador', 'email' => 'admin@construir.local', 'role' => 'Administrador'],
            ['name' => 'Gerente', 'email' => 'gerente@construir.local', 'role' => 'Gerente'],
            ['name' => 'Cajera', 'email' => 'cajera@construir.local', 'role' => 'Cajero'],
        ];
        foreach ($users as $data) {
            $user = User::updateOrCreate(['email' => $data['email']], ['name' => $data['name'], 'password' => Hash::make('password')]);
            $user->syncRoles([$data['role']]);
        }
    }
}
