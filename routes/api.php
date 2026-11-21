<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AggregateReportController;
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

// ==========================================
// PUBLIC ROUTES
// ==========================================
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// ==========================================
// PROTECTED ROUTES (Semua butuh auth)
// ==========================================
Route::middleware(['auth:sanctum', 'api.timeout', 'log.activity'])->group(function () {

    // --- Auth & Profile ---
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::patch('/me', [UserController::class, 'updateProfile']);
    Route::put('/me/password', [UserController::class, 'changePassword']);

    // --- Dashboard ---
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/charts', [DashboardController::class, 'charts']);

    // ==========================================
    // USERS (Admin only untuk write)
    // ==========================================
    Route::get('users', [UserController::class, 'index']);
    Route::get('users/{id}', [UserController::class, 'show']);
    Route::get('users/{id}/activity', [UserController::class, 'userActivity']);
    
    Route::middleware('role:administrator_sistem')->group(function () {
        Route::post('users', [UserController::class, 'store']);
        Route::put('users/{id}', [UserController::class, 'update']);
        Route::patch('users/{id}/status', [UserController::class, 'updateStatus']);
        Route::delete('users/{id}', [UserController::class, 'destroy']);
        Route::get('users/{id}/tokens', [UserController::class, 'getUserTokens']);
        Route::delete('users/{id}/tokens/{tokenId}', [UserController::class, 'revokeUserToken']);
    });

    // ==========================================
    // ROLES & PERMISSIONS (Admin only)
    // ==========================================
    Route::middleware('role:administrator_sistem')->group(function () {
        Route::apiResource('roles', RoleController::class);
        Route::apiResource('permissions', PermissionController::class);
        Route::get('roles/{id}/permissions', [RoleController::class, 'getPermissions']);
        Route::put('roles/{id}/permissions', [RoleController::class, 'syncPermissions']);
        Route::post('roles/{id}/permissions/{permissionId}', [RoleController::class, 'assignPermission']);
        Route::delete('roles/{id}/permissions/{permissionId}', [RoleController::class, 'revokePermission']);
    });

    // ==========================================
    // SPECIES (Read all, Write admin)
    // ==========================================
    Route::get('species', [SpeciesController::class, 'index']);
    Route::get('species/{id}', [SpeciesController::class, 'show']);
    
    Route::middleware('role:administrator_sistem')->group(function () {
        Route::post('species', [SpeciesController::class, 'store']);
        Route::put('species/{id}', [SpeciesController::class, 'update']);
        Route::delete('species/{id}', [SpeciesController::class, 'destroy']);
    });

    // ==========================================
    // ANIMALS (Read all, Write admin)
    // ==========================================
    Route::get('animals', [AnimalController::class, 'index']);
    Route::get('animals/{id}', [AnimalController::class, 'show']);
    Route::get('animals/{animalId}/track', [GpsReadingController::class, 'byAnimal']);
    Route::get('animals/{animalId}/zone-visits', [AnimalGeoZoneController::class, 'index']);
    Route::get('animals/{animalId}/incidents', [IncidentReportController::class, 'byAnimal']);
    
    Route::middleware('role:administrator_sistem')->group(function () {
        Route::post('animals', [AnimalController::class, 'store']);
        Route::put('animals/{id}', [AnimalController::class, 'update']);
        Route::patch('animals/{id}/status', [AnimalController::class, 'updateStatus']);
        Route::delete('animals/{id}', [AnimalController::class, 'destroy']);
    });

    // ==========================================
    // TRACKING DEVICES
    // ==========================================
    Route::get('devices', [TrackingDeviceController::class, 'index']);
    Route::get('devices/{id}', [TrackingDeviceController::class, 'show']);
    Route::get('devices/{id}/latest-reading', [TrackingDeviceController::class, 'latestReading']);
    Route::get('devices/{deviceId}/gps-readings', [GpsReadingController::class, 'byDevice']);
    Route::get('devices/{id}/battery-history', [TrackingDeviceController::class, 'batteryHistory']);
    
    Route::middleware('role:administrator_sistem')->group(function () {
        Route::post('devices', [TrackingDeviceController::class, 'store']);
        Route::put('devices/{id}', [TrackingDeviceController::class, 'update']);
        Route::patch('devices/{id}/assign-animal', [TrackingDeviceController::class, 'assignAnimal']);
        Route::delete('devices/{id}/assign-animal', [TrackingDeviceController::class, 'unassignAnimal']);
        Route::patch('devices/{id}/condition', [TrackingDeviceController::class, 'updateCondition']);
        Route::patch('devices/{id}/status', [TrackingDeviceController::class, 'updateStatus']);
        Route::delete('devices/{id}', [TrackingDeviceController::class, 'destroy']);
    });

    // ==========================================
    // GPS READINGS
    // ==========================================
    Route::get('gps-readings', [GpsReadingController::class, 'index']);
    Route::get('gps-readings/{id}', [GpsReadingController::class, 'show']);
    Route::get('gps-readings/{readingId}/weather', [WeatherDataController::class, 'byReading']);
    Route::post('gps-readings', [GpsReadingController::class, 'store']);
    Route::post('gps-readings/bulk', [GpsReadingController::class, 'bulk']);
    
    Route::middleware('role:administrator_sistem')->group(function () {
        Route::put('gps-readings/{id}', [GpsReadingController::class, 'update']);
        Route::delete('gps-readings/{id}', [GpsReadingController::class, 'destroy']);
    });

    // ==========================================
    // GEO ZONES
    // ==========================================
    Route::get('geo-zones', [GeoZoneController::class, 'index']);
    Route::get('geo-zones/{id}', [GeoZoneController::class, 'show']);
    Route::get('geo-zones/{id}/animals', [GeoZoneController::class, 'animals']);
    Route::get('geo-zones/{id}/visits', [GeoZoneController::class, 'visits']);
    
    Route::middleware('role:administrator_sistem')->group(function () {
        Route::post('geo-zones', [GeoZoneController::class, 'store']);
        Route::put('geo-zones/{id}', [GeoZoneController::class, 'update']);
        Route::delete('geo-zones/{id}', [GeoZoneController::class, 'destroy']);
    });

    // ==========================================
    // ANIMAL GEO ZONE VISITS
    // ==========================================
    Route::get('animal-geo-zone-visits', [AnimalGeoZoneController::class, 'index']);
    Route::get('animal-geo-zone-visits/{id}', [AnimalGeoZoneController::class, 'show']);
    Route::post('animal-geo-zone-visits', [AnimalGeoZoneController::class, 'store']);
    Route::patch('animal-geo-zone-visits/{id}/exit', [AnimalGeoZoneController::class, 'exit']);
    
    Route::middleware('role:administrator_sistem')->group(function () {
        Route::put('animal-geo-zone-visits/{id}', [AnimalGeoZoneController::class, 'update']);
        Route::delete('animal-geo-zone-visits/{id}', [AnimalGeoZoneController::class, 'destroy']);
    });

    // ==========================================
    // INCIDENTS
    // ==========================================
    Route::get('incidents', [IncidentReportController::class, 'index']);
    Route::get('incidents/{id}', [IncidentReportController::class, 'show']);
    Route::get('incidents/{id}/media', [IncidentReportController::class, 'media']);
    
    Route::middleware('role:konservasionis_lapangan,peneliti_ekologi,administrator_sistem')->group(function () {
        Route::post('incidents', [IncidentReportController::class, 'store']);
        Route::post('incidents/{id}/media', [IncidentReportController::class, 'uploadMedia']);
        Route::put('incidents/{id}', [IncidentReportController::class, 'update']);
        Route::patch('incidents/{id}/sync-status', [IncidentReportController::class, 'updateSyncStatus']);
        Route::delete('incidents/{id}', [IncidentReportController::class, 'destroy']);
    });

    // ==========================================
    // NOTIFICATIONS
    // ==========================================
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/{id}', [NotificationController::class, 'show']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('notifications/{id}', [NotificationController::class, 'destroy']);
    Route::delete('notifications', [NotificationController::class, 'deleteRead']);

    // ==========================================
    // WEATHER DATA
    // ==========================================
    Route::get('weather-data', [WeatherDataController::class, 'index']);
    Route::get('weather-data/{id}', [WeatherDataController::class, 'show']);
    Route::post('weather-data/ingest', [WeatherDataController::class, 'ingest']);
    
    Route::middleware('role:administrator_sistem')->group(function () {
        Route::post('weather-data', [WeatherDataController::class, 'store']);
        Route::put('weather-data/{id}', [WeatherDataController::class, 'update']);
        Route::delete('weather-data/{id}', [WeatherDataController::class, 'destroy']);
    });

    // ==========================================
    // AGGREGATE REPORTS
    // ==========================================
    Route::get('aggregate-reports', [AggregateReportController::class, 'index']);
    Route::get('aggregate-reports/{id}', [AggregateReportController::class, 'show']);
    Route::get('aggregate-reports/{id}/download', [AggregateReportController::class, 'download']);
    
    Route::middleware('role:administrator_sistem')->group(function () {
        Route::post('aggregate-reports', [AggregateReportController::class, 'store']);
        Route::post('aggregate-reports/generate', [AggregateReportController::class, 'generate']);
        Route::put('aggregate-reports/{id}', [AggregateReportController::class, 'update']);
        Route::delete('aggregate-reports/{id}', [AggregateReportController::class, 'destroy']);
    });

    // ==========================================
    // ACTIVITY LOGS (Read all, Write admin)
    // ==========================================
    Route::get('activity-logs', [ActivityLogController::class, 'index']);
    Route::get('activity-logs/{id}', [ActivityLogController::class, 'show']);
    
    Route::middleware('role:administrator_sistem')->group(function () {
        Route::post('activity-logs', [ActivityLogController::class, 'store']);
        Route::put('activity-logs/{id}', [ActivityLogController::class, 'update']);
        Route::delete('activity-logs/{id}', [ActivityLogController::class, 'destroy']);
    });

    // ==========================================
    // AI PREDICTIONS & MEDIA (Semua bisa akses)
    // ==========================================
    Route::get('ai-predictions', [AiPredictionController::class, 'index']);
    Route::get('ai-predictions/pending-verification', [AiPredictionController::class, 'pendingVerification']);
    Route::get('ai-predictions/{id}', [AiPredictionController::class, 'show']);
    
    Route::post('ai/upload', [AiPredictionController::class, 'upload']);
    Route::post('ai-predictions/{id}/verify', [AiPredictionController::class, 'verify']);
    Route::post('ai-predictions/{id}/retry', [AiPredictionController::class, 'retry']);
    
    Route::get('media-uploads', [AiPredictionController::class, 'mediaUploads']);
    Route::get('media-uploads/{id}', [AiPredictionController::class, 'showMedia']);
    Route::get('media-uploads/{id}/file', [AiPredictionController::class, 'file']);
    Route::post('media-uploads/{id}/predictions', [AiPredictionController::class, 'triggerPrediction']);
    Route::get('media-uploads/{id}/predictions', [AiPredictionController::class, 'getMediaPredictions']);
    
    // AI Summary, Health Check, Detect Objects
    Route::get('ai/summary-report', [AiPredictionController::class, 'generateSummaryReport']);
    Route::get('ai/health', [AiPredictionController::class, 'aiHealthCheck']);
    Route::post('ai/detect-objects', [AiPredictionController::class, 'detectObjects']);

    Route::middleware('role:administrator_sistem')->group(function () {
        Route::put('ai-predictions/{id}', [AiPredictionController::class, 'update']);
        Route::delete('ai-predictions/{id}', [AiPredictionController::class, 'destroy']);
        Route::delete('media-uploads/{id}', [AiPredictionController::class, 'destroyMedia']);
    });

    // ==========================================
    // EXPORT ENDPOINTS
    // ==========================================
    Route::get('export/gps/csv', [ExportController::class, 'exportGpsCsv']);
    Route::get('export/gps/geojson', [ExportController::class, 'exportGpsGeoJson']);
    Route::get('export/animals/csv', [ExportController::class, 'exportAnimalsCsv']);
    Route::get('export/incidents/csv', [ExportController::class, 'exportIncidentsCsv']);
    Route::get('export/devices/csv', [ExportController::class, 'exportDevicesCsv']);
    Route::get('export/species/csv', [ExportController::class, 'exportSpeciesCsv']);
    Route::get('export/weather/csv', [ExportController::class, 'exportWeatherCsv']);
    Route::get('export/ai-predictions/csv', [ExportController::class, 'exportAiPredictionsCsv']);
    Route::get('export/aggregate-report/{id}', [ExportController::class, 'exportAggregateReport']);
});