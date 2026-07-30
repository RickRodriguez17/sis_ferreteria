<?php

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;

class SettingPolicy
{
    public function update(User $user, Setting|string|null $setting = null): bool
    {
        return $user->can('settings.update');
    }
}
