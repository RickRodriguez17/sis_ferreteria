<?php

namespace App\Providers;

use App\Models\Audit;
use App\Models\User;
use App\Policies\RolePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role as SpatieRole;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function (?User $user): ?bool {
            return $user?->hasRole('Administrador') ? true : null;
        });
        Gate::policy(SpatieRole::class, RolePolicy::class);
        foreach (['created', 'updated', 'deleted'] as $event) {
            SpatieRole::{$event}(function (SpatieRole $role) use ($event): void {
                Audit::create([
                    'user_id' => auth()->id(),
                    'event' => "role.{$event}",
                    'auditable_type' => $role->getMorphClass(),
                    'auditable_id' => $role->getKey(),
                    'old_values' => $event === 'updated' ? $role->getOriginal() : ($event === 'deleted' ? $role->getOriginal() : []),
                    'new_values' => $event === 'deleted' ? [] : $role->getAttributes(),
                ]);
            });
        }
    }
}
