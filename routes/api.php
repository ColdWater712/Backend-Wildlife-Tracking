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

// Public Routes
Route::post('/login', [AuthController::class, 'login']);

// Protected Routes
Route::middleware(['auth:sanctum', 'api.timeout', 'log.activity'])->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::patch('/me', [UserController::class, 'updateProfile']);
    Route::put('/me/password', [UserController::class, 'changePassword']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Users
    Route::middleware('role:administrator_sistem')->group(function () {
        Route::apiResource('users', UserController::class)->except('index', 'show');
        Route::get('users/{id}/tokens', [UserController::class, 'getUserTokens']);
        Route::delete('users/{id}/tokens/{tokenId}', [UserController::class, 'revokeUserToken']);
    });
    Route::get('users', [UserController::class, 'index']);
    Route::get('users/{id}', [UserController::class, 'show']);

    // Roles & Permissions
    Route::middleware('role:administrator_sistem')->group(function () {
        Route::get('roles/{id}/permissions', [RoleController::class, 'getPermissions']);
        Route::put('roles/{id}/permissions', [RoleController::class, 'syncPermissions']);
        Route::post('roles/{id}/permissions/{permissionId}', [RoleController::class, 'assignPermission']);
        Route::delete('roles/{id}/permissions/{permissionId}', [RoleController::class, 'revokePermission']);
        Route::apiResource('roles', RoleController::class);
        Route::apiResource('permissions', PermissionController::class);
    });

    // Species
    Route::get('species', [SpeciesController::class, 'index']);
    Route::get('species/{id}', [SpeciesController::class, 'show']);
    Route::middleware('role:administrator_sistem')->group(function () {
        Route::post('species', [SpeciesController::class, 'store']);
        Route::put('species/{id}', [SpeciesController::class, 'update']);
        Route::delete('species/{id}', [SpeciesController::class, 'destroy']);
    });

    // Animals
    Route::get('animals', [AnimalController::class, 'index']);
    Route::get('animals/{id}', [AnimalController::class, 'show']);
    Route::get('animals/{animalId}/track', [GpsReadingController::class, 'byAnimal']);
    Route::get('animals/{animalId}/zone-visits', [AnimalGeoZoneController::class, 'index']);
    Route::middleware('role:administrator_sistem')->group(function () {
        Route::post('animals', [AnimalController::class, 'store']);
        Route::put('animals/{id}', [AnimalController::class, 'update']);
        Route::patch('animals/{id}/status', [AnimalController::class, 'updateStatus']);
        Route::delete('animals/{id}', [AnimalController::class, 'destroy']);
    });

    // Tracking Devices
    Route::get('devices', [TrackingDeviceController::class, 'index']);
    Route::get('devices/{id}', [TrackingDeviceController::class, 'show']);
    Route::get('devices/{id}/latest-reading', [TrackingDeviceController::class, 'latestReading']);
    Route::get('devices/{deviceId}/gps-readings', [GpsReadingController::class, 'byDevice']);
    Route::middleware('role:administrator_sistem')->group(function () {
        Route::post('devices', [TrackingDeviceController::class, 'store']);
        Route::put('devices/{id}', [TrackingDeviceController::class, 'update']);
        Route::patch('devices/{id}/assign-animal', [TrackingDeviceController::class, 'assignAnimal']);
        Route::delete('devices/{id}/assign-animal', [TrackingDeviceController::class, 'unassignAnimal']);
        Route::patch('devices/{id}/condition', [TrackingDeviceController::class, 'updateCondition']);
        Route::patch('devices/{id}/status', [TrackingDeviceController::class, 'updateStatus']);
        Route::delete('devices/{id}', [TrackingDeviceController::class, 'destroy']);
    });

    // GPS Readings
    Route::get('gps-readings', [GpsReadingController::class, 'index']);
    Route::get('gps-readings/{id}', [GpsReadingController::class, 'show']);
    Route::get('gps-readings/{readingId}/weather', [WeatherDataController::class, 'byReading']);
    Route::post('gps-readings', [GpsReadingController::class, 'store']);
    Route::post('gps-readings/bulk', [GpsReadingController::class, 'bulk']);
    Route::middleware('role:administrator_sistem')->group(function () {
        Route::put('gps-readings/{id}', [GpsReadingController::class, 'update']);
        Route::delete('gps-readings/{id}', [GpsReadingController::class, 'destroy']);
    });

    // Geo Zones
    Route::get('geo-zones', [GeoZoneController::class, 'index']);
    Route::get('geo-zones/{id}', [GeoZoneController::class, 'show']);
    Route::get('geo-zones/{id}/animals', [GeoZoneController::class, 'animals']);
    Route::get('geo-zones/{id}/visits', [GeoZoneController::class, 'visits']);
    Route::middleware('role:administrator_sistem')->group(function () {
        Route::post('geo-zones', [GeoZoneController::class, 'store']);
        Route::put('geo-zones/{id}', [GeoZoneController::class, 'update']);
        Route::delete('geo-zones/{id}', [GeoZoneController::class, 'destroy']);
    });

    // Animal Geo Zone Visits
    Route::get('animal-geo-zone-visits', [AnimalGeoZoneController::class, 'index']);
    Route::get('animal-geo-zone-visits/{id}', [AnimalGeoZoneController::class, 'show']);
    Route::post('animal-geo-zone-visits', [AnimalGeoZoneController::class, 'store']);
    Route::patch('animal-geo-zone-visits/{id}/exit', [AnimalGeoZoneController::class, 'exit']);
    Route::middleware('role:administrator_sistem')->group(function () {
        Route::put('animal-geo-zone-visits/{id}', [AnimalGeoZoneController::class, 'update']);
        Route::delete('animal-geo-zone-visits/{id}', [AnimalGeoZoneController::class, 'destroy']);
    });

    // Incidents
    Route::get('incidents', [IncidentReportController::class, 'index']);
    Route::get('incidents/{id}', [IncidentReportController::class, 'show']);
    Route::get('incidents/{id}/media', [IncidentReportController::class, 'media']);
    Route::middleware('role:konservasionis_lapangan,peneliti_ekologi,administrator_sistem')->group(function () {
        Route::post('incidents', [IncidentReportController::class, 'store']);
        Route::put('incidents/{id}', [IncidentReportController::class, 'update']);
        Route::patch('incidents/{id}/sync-status', [IncidentReportController::class, 'updateSyncStatus']);
        Route::delete('incidents/{id}', [IncidentReportController::class, 'destroy']);
    });

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/{id}', [NotificationController::class, 'show']);
    Route::patch('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('notifications/{id}', [NotificationController::class, 'destroy']);
    Route::delete('notifications', [NotificationController::class, 'deleteRead']);

    // Weather Data
    Route::get('weather-data', [WeatherDataController::class, 'index']);
    Route::get('weather-data/{id}', [WeatherDataController::class, 'show']);
    Route::post('weather-data/ingest', [WeatherDataController::class, 'ingest']);
    Route::middleware('role:administrator_sistem')->group(function () {
        Route::post('weather-data', [WeatherDataController::class, 'store']);
        Route::put('weather-data/{id}', [WeatherDataController::class, 'update']);
        Route::delete('weather-data/{id}', [WeatherDataController::class, 'destroy']);
    });

    // Aggregate Reports
    Route::get('aggregate-reports', [AggregateReportController::class, 'index']);
    Route::get('aggregate-reports/{id}', [AggregateReportController::class, 'show']);
    Route::get('aggregate-reports/{id}/download', [AggregateReportController::class, 'download']);
    Route::middleware('role:administrator_sistem')->group(function () {
        Route::post('aggregate-reports', [AggregateReportController::class, 'store']);
        Route::post('aggregate-reports/generate', [AggregateReportController::class, 'generate']);
        Route::put('aggregate-reports/{id}', [AggregateReportController::class, 'update']);
        Route::delete('aggregate-reports/{id}', [AggregateReportController::class, 'destroy']);
    });

    // Activity Log
    Route::get('activity-logs', [ActivityLogController::class, 'index']);
    Route::get('activity-logs/{id}', [ActivityLogController::class, 'show']);
    Route::middleware('role:administrator_sistem')->group(function () {
        Route::post('activity-logs', [ActivityLogController::class, 'store']);
        Route::put('activity-logs/{id}', [ActivityLogController::class, 'update']);
        Route::delete('activity-logs/{id}', [ActivityLogController::class, 'destroy']);
    });

    // AI
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
    Route::middleware('role:administrator_sistem')->group(function () {
        Route::put('ai-predictions/{id}', [AiPredictionController::class, 'update']);
        Route::delete('ai-predictions/{id}', [AiPredictionController::class, 'destroy']);
        Route::delete('media-uploads/{id}', [AiPredictionController::class, 'destroyMedia']);
    });

    // Export
    Route::get('export/gps/csv', [ExportController::class, 'exportGpsCsv']);
    Route::get('export/gps/geojson', [ExportController::class, 'exportGpsGeoJson']);
    Route::get('export/animals/csv', [ExportController::class, 'exportAnimalsCsv']);
    Route::get('export/incidents/csv', [ExportController::class, 'exportIncidentsCsv']);
    Route::get('export/devices/csv', [ExportController::class, 'exportDevicesCsv']);
});