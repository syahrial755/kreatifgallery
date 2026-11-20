<?php

namespace App\Policies;

use App\Models\Portofolio;
use App\Models\User;

class PortofolioPolicy
{
    public function manage(User $user, Portofolio $portofolio): bool
    {
        return $user->is_active && $user->isKreator() && $portofolio->user_id === $user->id;
    }
}
