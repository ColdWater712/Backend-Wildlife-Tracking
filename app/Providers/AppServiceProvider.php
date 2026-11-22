<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Parameter ID selalu numerik agar tidak bentrok dengan path statis
        // seperti /notifications/unread-count atau /ai-predictions/pending-verification.
        foreach (['id', 'animalId', 'deviceId', 'readingId', 'mediaId', 'permissionId', 'tokenId'] as $param) {
            Route::pattern($param, '[0-9]+');
        }
    }
}
