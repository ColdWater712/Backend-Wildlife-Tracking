<?php

namespace App\Policies;

use App\Models\Animal;
use App\Models\User;

class AnimalPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Animal $animal): bool { return true; }
    public function create(User $user): bool { return $user->role === 'administrator_sistem'; }
    public function update(User $user, Animal $animal): bool { return $user->role === 'administrator_sistem'; }
    public function delete(User $user, Animal $animal): bool { return $user->role === 'administrator_sistem'; }
}