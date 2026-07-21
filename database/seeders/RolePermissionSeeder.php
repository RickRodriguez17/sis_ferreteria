<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $resources = ['products', 'categories', 'brands', 'units', 'attributes', 'suppliers', 'customers', 'purchases', 'receptions', 'sales', 'quotations', 'credits', 'payments', 'inventory', 'users', 'roles'];
        $permissions = [];
        foreach ($resources as $resource) {
            foreach (['view', 'create', 'update', 'delete'] as $action) {
                $permissions[] = "{$resource}.{$action}";
            }
        }
        $permissions = array_merge($permissions, ['credits.cancel', 'prices.update', 'inventory.adjust', 'inventory.transfer', 'cash.open', 'cash.close', 'cash.movement', 'reports.view', 'audit.view', 'settings.update']);
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $all = Permission::all();
        Role::findOrCreate('Administrador')->syncPermissions($all);
        Role::findOrCreate('Gerente')->syncPermissions($all->whereNotIn('name', ['settings.update', 'users.delete', 'roles.delete']));
        Role::findOrCreate('Vendedor')->syncPermissions(Permission::whereIn('name', ['products.view', 'inventory.view', 'customers.view', 'customers.create', 'customers.update', 'sales.view', 'sales.create', 'quotations.view', 'quotations.create', 'credits.view', 'payments.view', 'payments.create', 'reports.view'])->get());
        Role::findOrCreate('Almacenero')->syncPermissions(Permission::whereIn('name', ['products.view', 'products.create', 'products.update', 'inventory.view', 'inventory.adjust', 'inventory.transfer', 'purchases.view', 'suppliers.view', 'receptions.view', 'receptions.create', 'receptions.update', 'receptions.delete'])->get());
        Role::findOrCreate('Cajero')->syncPermissions(Permission::whereIn('name', ['products.view', 'inventory.view', 'sales.view', 'sales.create', 'credits.view', 'payments.view', 'payments.create', 'cash.open', 'cash.close', 'cash.movement'])->get());
    }
}
