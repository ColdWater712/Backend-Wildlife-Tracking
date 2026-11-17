<?php

namespace App\Policies;

use App\Models\IncidentReport;
use App\Models\User;

class IncidentReportPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['konservasionis_lapangan', 'peneliti_ekologi', 'administrator_sistem', 'pengambil_kebijakan']);
    }

    public function view(User $user, IncidentReport $report): bool
    {
        return $user->role === 'administrator_sistem' || $report->user_id === $user->user_id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['konservasionis_lapangan', 'peneliti_ekologi', 'administrator_sistem']);
    }

    public function update(User $user, IncidentReport $report): bool
    {
        return $user->role === 'administrator_sistem' || $report->user_id === $user->user_id;
    }

    public function delete(User $user, IncidentReport $report): bool
    {
        return $user->role === 'administrator_sistem' || $report->user_id === $user->user_id;
    }
}