<?php

namespace App\Policies;

use App\Models\Karya;
use App\Models\User;

class KaryaPolicy
{
    /** Kreator hanya boleh mengubah/menghapus karyanya sendiri. */
    public function update(User $user, Karya $karya): bool
    {
        return $user->is_active && $user->isKreator() && $karya->user_id === $user->id;
    }

    public function delete(User $user, Karya $karya): bool
    {
        return $this->update($user, $karya);
    }

    /** Pemilik dan admin boleh melihat karya yang belum tayang. */
    public function view(?User $user, Karya $karya): bool
    {
        if ($karya->isPublik()) {
            return true;
        }

        return $user && ($user->isAdmin() || $karya->user_id === $user->id);
    }

    public function moderate(User $user, Karya $karya): bool
    {
        return $user->is_active && $user->isAdmin();
    }
}
