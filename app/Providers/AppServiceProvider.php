<?php

namespace App\Providers;

use App\Models\Audit;
use App\Models\User;
use App\Policies\RolePolicy;
use App\Support\PasswordRules;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
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
        Password::defaults(fn (): Password => PasswordRules::strong());

        RateLimiter::for('password-reset', function (Request $request): Limit {
            return Limit::perMinute(6)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

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
