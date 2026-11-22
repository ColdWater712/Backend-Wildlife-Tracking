<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AggregateReportController;
use App\Http\Controllers\Api\AiInsightController;
use App\Http\Controllers\Api\AiPredictionController;
use App\Http\Controllers\Api\AnimalController;
use App\Http\Controllers\Api\AnimalGeoZoneController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\GeoZoneController;
use App\Http\Controllers\Api\GpsReadingController;
use App\Http\Controllers\Api\IncidentReportController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SpeciesController;
use App\Http\Controllers\Api\TrackingDeviceController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WeatherDataController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Semua route didaftarkan dua kali: tanpa prefix (/api/...) yang dipakai
| frontend saat ini, dan dengan prefix versi (/api/v1/...) sesuai dokumen
| analisis kebutuhan. Otorisasi memakai permission dari tabel role_permissions.
|
| Permission: view_data, create_data, update_data, delete_data, manage_users,
| manage_roles, view_reports, manage_master_data, upload_media, verify_ai, export_data
*/

$routes = function () {
    // ---------------------------------------------------------------- Auth (publik)
    Route::middleware('throttle:20,1')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('auth/login', [AuthController::class, 'login']);
    });

    Route::middleware(['auth:sanctum', 'api.timeout', 'log.activity'])->group(function () {

        // ------------------------------------------------------------ Auth & profil
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::get('users/me', [UserController::class, 'me']);
        Route::patch('me', [UserController::class, 'updateProfile']);
        Route::patch('users/me', [UserController::class, 'updateProfile']);
        Route::put('me/password', [UserController::class, 'changePassword']);
        Route::put('users/me/password', [UserController::class, 'changePassword']);

        // ------------------------------------------------------------ Dashboard
        Route::middleware('permission:view_data')->group(function () {
            Route::get('dashboard', [DashboardController::class, 'index']);
            Route::get('dashboard/stats', [DashboardController::class, 'stats']);
            Route::get('dashboard/charts', [DashboardController::class, 'charts']);
        });

        // ------------------------------------------------------------ Users
        Route::middleware('permission:manage_users')->group(function () {
            Route::get('users', [UserController::class, 'index']);
            Route::post('users', [UserController::class, 'store']);
            Route::get('users/{id}', [UserController::class, 'show']);
            Route::put('users/{id}', [UserController::class, 'update']);
            Route::patch('users/{id}/status', [UserController::class, 'updateStatus']);
            Route::delete('users/{id}', [UserController::class, 'destroy']);
            Route::get('users/{id}/activity', [UserController::class, 'activity']);
            Route::get('users/{id}/tokens', [UserController::class, 'tokens']);
            Route::delete('users/{id}/tokens/{tokenId}', [UserController::class, 'revokeToken']);

            Route::get('activity-logs', [ActivityLogController::class, 'index']);
            Route::get('activity-logs/{id}', [ActivityLogController::class, 'show']);
        });

        // ------------------------------------------------------------ Roles & permissions
        Route::middleware('permission:manage_roles')->group(function () {
            Route::get('roles', [RoleController::class, 'index']);
            Route::post('roles', [RoleController::class, 'store']);
            Route::get('roles/{id}', [RoleController::class, 'show']);
            Route::put('roles/{id}', [RoleController::class, 'update']);
            Route::delete('roles/{id}', [RoleController::class, 'destroy']);
            Route::get('roles/{id}/permissions', [RoleController::class, 'permissions']);
            Route::put('roles/{id}/permissions', [RoleController::class, 'syncPermissions']);
            Route::post('roles/{id}/permissions/{permissionId}', [RoleController::class, 'assignPermission']);
            Route::delete('roles/{id}/permissions/{permissionId}', [RoleController::class, 'revokePermission']);

            Route::get('permissions', [PermissionController::class, 'index']);
            Route::get('permissions/{id}', [PermissionController::class, 'show']);
        });

        // ------------------------------------------------------------ Master data (baca)
        Route::middleware('permission:view_data')->group(function () {
            Route::get('species', [SpeciesController::class, 'index']);
            Route::get('species/{id}', [SpeciesController::class, 'show']);

            Route::get('animals', [AnimalController::class, 'index']);
            Route::get('animals/{id}', [AnimalController::class, 'show']);
            Route::get('animals/{animalId}/track', [AnimalController::class, 'track']);
            Route::get('animals/{animalId}/zone-visits', [AnimalController::class, 'zoneVisits']);
            Route::get('animals/{animalId}/incidents', [AnimalController::class, 'incidents']);
            Route::get('animals/{animalId}/ai-observations', [AnimalController::class, 'aiObservations']);

            foreach (['devices', 'tracking-devices'] as $prefix) {
                Route::get($prefix, [TrackingDeviceController::class, 'index']);
                Route::get("{$prefix}/{id}", [TrackingDeviceController::class, 'show']);
                Route::get("{$prefix}/{id}/latest-reading", [TrackingDeviceController::class, 'latestReading']);
                Route::get("{$prefix}/{id}/gps-readings", [TrackingDeviceController::class, 'gpsReadings']);
                Route::get("{$prefix}/{id}/battery-history", [TrackingDeviceController::class, 'batteryHistory']);
                Route::get("{$prefix}/{id}/captures", [TrackingDeviceController::class, 'captures']);
            }

            Route::get('gps-readings', [GpsReadingController::class, 'index']);
            Route::get('gps-readings/{id}', [GpsReadingController::class, 'show']);
            Route::get('gps-readings/{readingId}/weather', [WeatherDataController::class, 'byReading']);

            Route::get('geo-zones', [GeoZoneController::class, 'index']);
            Route::get('geo-zones/locate', [GeoZoneController::class, 'locate']);
            Route::get('geo-zones/{id}', [GeoZoneController::class, 'show']);
            Route::get('geo-zones/{id}/animals', [GeoZoneController::class, 'animals']);
            Route::get('geo-zones/{id}/visits', [GeoZoneController::class, 'visits']);
            Route::get('geo-zones/{id}/devices', [GeoZoneController::class, 'devices']);

            Route::get('animal-geo-zone-visits', [AnimalGeoZoneController::class, 'index']);
            Route::get('animal-geo-zone-visits/{id}', [AnimalGeoZoneController::class, 'show']);

            foreach (['incidents', 'incident-reports'] as $prefix) {
                Route::get($prefix, [IncidentReportController::class, 'index']);
                Route::get("{$prefix}/{id}", [IncidentReportController::class, 'show']);
                Route::get("{$prefix}/{id}/media", [IncidentReportController::class, 'media']);
            }

            Route::get('weather-data', [WeatherDataController::class, 'index']);
            Route::get('weather-data/{id}', [WeatherDataController::class, 'show']);
        });

        // ------------------------------------------------------------ Master data (kelola)
        Route::middleware('permission:manage_master_data')->group(function () {
            Route::post('species', [SpeciesController::class, 'store']);
            Route::put('species/{id}', [SpeciesController::class, 'update']);
            Route::delete('species/{id}', [SpeciesController::class, 'destroy']);

            Route::post('animals', [AnimalController::class, 'store']);
            Route::put('animals/{id}', [AnimalController::class, 'update']);
            Route::patch('animals/{id}/status', [AnimalController::class, 'updateStatus']);
            Route::delete('animals/{id}', [AnimalController::class, 'destroy']);

            foreach (['devices', 'tracking-devices'] as $prefix) {
                Route::post($prefix, [TrackingDeviceController::class, 'store']);
                Route::put("{$prefix}/{id}", [TrackingDeviceController::class, 'update']);
                Route::delete("{$prefix}/{id}", [TrackingDeviceController::class, 'destroy']);
                Route::patch("{$prefix}/{id}/assign-animal", [TrackingDeviceController::class, 'assignAnimal']);
                Route::delete("{$prefix}/{id}/assign-animal", [TrackingDeviceController::class, 'unassignAnimal']);
                Route::patch("{$prefix}/{id}/condition", [TrackingDeviceController::class, 'updateCondition']);
                Route::patch("{$prefix}/{id}/status", [TrackingDeviceController::class, 'updateStatus']);
            }

            Route::post('geo-zones', [GeoZoneController::class, 'store']);
            Route::put('geo-zones/{id}', [GeoZoneController::class, 'update']);
            Route::delete('geo-zones/{id}', [GeoZoneController::class, 'destroy']);
        });

        // ------------------------------------------------------------ Data lapangan & ingest perangkat
        Route::middleware('permission:create_data')->group(function () {
            foreach (['devices', 'tracking-devices'] as $prefix) {
                Route::patch("{$prefix}/{id}/telemetry", [TrackingDeviceController::class, 'telemetry']);
                Route::post("{$prefix}/{id}/captures", [TrackingDeviceController::class, 'uploadCapture']);
            }

            Route::post('gps-readings', [GpsReadingController::class, 'store']);
            Route::post('gps-readings/bulk', [GpsReadingController::class, 'bulk']);

            Route::post('animal-geo-zone-visits', [AnimalGeoZoneController::class, 'store']);
            Route::patch('animal-geo-zone-visits/{id}/exit', [AnimalGeoZoneController::class, 'exit']);

            foreach (['incidents', 'incident-reports'] as $prefix) {
                Route::post($prefix, [IncidentReportController::class, 'store']);
                Route::put("{$prefix}/{id}", [IncidentReportController::class, 'update']);
                Route::post("{$prefix}/{id}/media", [IncidentReportController::class, 'uploadMedia']);
            }

            Route::post('weather-data/ingest', [WeatherDataController::class, 'ingest']);
            Route::post('weather-data/sync', [WeatherDataController::class, 'sync']);
        });

        Route::middleware('role:administrator_sistem')->group(function () {
            foreach (['incidents', 'incident-reports'] as $prefix) {
                Route::patch("{$prefix}/{id}/sync-status", [IncidentReportController::class, 'updateSyncStatus']);
            }
        });

        Route::middleware('permission:delete_data')->group(function () {
            foreach (['incidents', 'incident-reports'] as $prefix) {
                Route::delete("{$prefix}/{id}", [IncidentReportController::class, 'destroy']);
            }
            Route::delete('ai-predictions/{id}', [AiPredictionController::class, 'destroy']);
            Route::delete('media-uploads/{id}', [AiPredictionController::class, 'destroyMedia']);
        });

        // ------------------------------------------------------------ Notifikasi (milik sendiri)
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::patch('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('notifications', [NotificationController::class, 'deleteRead']);
        Route::get('notifications/{id}', [NotificationController::class, 'show']);
        Route::patch('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::delete('notifications/{id}', [NotificationController::class, 'destroy']);

        // ------------------------------------------------------------ AI
        Route::middleware('permission:view_data')->group(function () {
            Route::get('ai-predictions', [AiPredictionController::class, 'index']);
            Route::get('ai-predictions/pending-verification', [AiPredictionController::class, 'pendingVerification']);
            Route::get('ai-predictions/{id}', [AiPredictionController::class, 'show']);

            Route::get('media-uploads', [AiPredictionController::class, 'mediaUploads']);
            Route::get('media-uploads/{id}', [AiPredictionController::class, 'showMedia']);
            Route::get('media-uploads/{id}/file', [AiPredictionController::class, 'file']);
            Route::get('media-uploads/{mediaId}/predictions', [AiPredictionController::class, 'mediaPredictions']);

            Route::get('ai/summary-report', [AiInsightController::class, 'summaryReport']);
            Route::get('ai/health', [AiInsightController::class, 'health']);
            Route::get('ai/threats', [AiInsightController::class, 'threats']);
            Route::get('ai/vocabulary', [AiInsightController::class, 'vocabulary']);
        });

        Route::middleware(['permission:upload_media', 'throttle:30,1'])->group(function () {
            Route::post('ai/upload', [AiPredictionController::class, 'upload']);
            Route::post('media-uploads', [AiPredictionController::class, 'upload']);
            Route::post('ai/detect-objects', [AiInsightController::class, 'detectObjects']);
            Route::post('media-uploads/{mediaId}/predictions', [AiPredictionController::class, 'triggerPrediction']);
        });

        Route::middleware('permission:verify_ai')->group(function () {
            Route::match(['post', 'patch'], 'ai-predictions/{id}/verify', [AiPredictionController::class, 'verify']);
            Route::post('ai-predictions/{id}/retry', [AiPredictionController::class, 'retry']);
        });

        // ------------------------------------------------------------ Laporan agregat
        Route::middleware('permission:view_reports')->group(function () {
            Route::get('aggregate-reports', [AggregateReportController::class, 'index']);
            Route::post('aggregate-reports/generate', [AggregateReportController::class, 'generate']);
            Route::get('aggregate-reports/{id}', [AggregateReportController::class, 'show']);
            Route::get('aggregate-reports/{id}/download', [AggregateReportController::class, 'download']);
        });
        Route::delete('aggregate-reports/{id}', [AggregateReportController::class, 'destroy'])->middleware('permission:delete_data');

        // ------------------------------------------------------------ Ekspor
        Route::middleware('permission:export_data')->prefix('export')->group(function () {
            Route::get('gps/csv', [ExportController::class, 'gpsCsv']);
            Route::get('gps/geojson', [ExportController::class, 'gpsGeoJson']);
            Route::get('animals/csv', [ExportController::class, 'animalsCsv']);
            Route::get('species/csv', [ExportController::class, 'speciesCsv']);
            Route::get('devices/csv', [ExportController::class, 'devicesCsv']);
            Route::get('incidents/csv', [ExportController::class, 'incidentsCsv']);
            Route::get('weather/csv', [ExportController::class, 'weatherCsv']);
            Route::get('ai-predictions/csv', [ExportController::class, 'aiPredictionsCsv']);
            Route::get('geo-zones/csv', [ExportController::class, 'zonesCsv']);
            Route::get('geo-zones/geojson', [ExportController::class, 'zonesGeoJson']);
            Route::get('zone-visits/csv', [ExportController::class, 'zoneVisitsCsv']);
            Route::get('aggregate-report/{id}', [ExportController::class, 'aggregateReport']);
        });
        Route::middleware('permission:manage_users')->prefix('export')->group(function () {
            Route::get('users/csv', [ExportController::class, 'usersCsv']);
            Route::get('activity-logs/csv', [ExportController::class, 'activityLogsCsv']);
        });
    });
};

$routes();
Route::prefix('v1')->group($routes);
