<?php

namespace App\Providers;

use App\Models\Animal;
use App\Models\IncidentReport;
use App\Policies\AnimalPolicy;
use App\Policies\IncidentReportPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Animal::class => AnimalPolicy::class,
        IncidentReport::class => IncidentReportPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}