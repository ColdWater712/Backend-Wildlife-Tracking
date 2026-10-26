<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SpeciesController;
use App\Http\Controllers\Api\AnimalController;
use App\Http\Controllers\Api\TrackingDeviceController;
use App\Http\Controllers\Api\GPSReadingController;
use App\Http\Controllers\Api\GeoZoneController;
use App\Http\Controllers\Api\IncidentReportController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Semua route API untuk Wildlife Tracking System.
| Base URL: http://localhost:8000/api
|
*/

// ============================================================================
// PUBLIC ROUTES (Tidak perlu autentikasi)
// ============================================================================

Route::post('/login', [AuthController::class, 'login']);

// ============================================================================
// PROTECTED ROUTES (Wajib autentikasi dengan Bearer Token)
// ============================================================================

Route::middleware('auth:sanctum')->group(function () {

    // ------------------------------------------------------------------------
    // AUTHENTICATION
    // ------------------------------------------------------------------------
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // ------------------------------------------------------------------------
    // MASTER DATA
    // ------------------------------------------------------------------------
    Route::get('/species', [SpeciesController::class, 'index']);
    Route::get('/animals', [AnimalController::class, 'index']);

    // ------------------------------------------------------------------------
    // DEVICES & GPS READINGS
    // ------------------------------------------------------------------------
    Route::get('/devices', [TrackingDeviceController::class, 'index']);
    Route::get('/devices/{id}', [TrackingDeviceController::class, 'show']);

    Route::get('/gps-readings', [GPSReadingController::class, 'index']);
    Route::get('/gps-readings/{animal_id}', [GPSReadingController::class, 'byAnimal']);
    Route::post('/gps-readings', [GPSReadingController::class, 'store']); // Ingest GPS data

    // ------------------------------------------------------------------------
    // GEO ZONES
    // ------------------------------------------------------------------------
    Route::get('/geo-zones', [GeoZoneController::class, 'index']);
    Route::get('/geo-zones/{id}', [GeoZoneController::class, 'show']);

    // ------------------------------------------------------------------------
    // INCIDENT REPORTS (Full CRUD)
    // ------------------------------------------------------------------------
    Route::get('/incidents', [IncidentReportController::class, 'index']);
    Route::get('/incidents/{id}', [IncidentReportController::class, 'show']);
    Route::post('/incidents', [IncidentReportController::class, 'store']);
    Route::put('/incidents/{id}', [IncidentReportController::class, 'update']);
    Route::delete('/incidents/{id}', [IncidentReportController::class, 'destroy']);

    // ------------------------------------------------------------------------
    // NOTIFICATIONS
    // ------------------------------------------------------------------------
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread', [NotificationController::class, 'unread']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

    // ------------------------------------------------------------------------
    // DASHBOARD
    // ------------------------------------------------------------------------
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // ------------------------------------------------------------------------
    // EXPORT
    // ------------------------------------------------------------------------
    Route::get('/export/gps/csv', [ExportController::class, 'csv']);
    Route::get('/export/gps/geojson', [ExportController::class, 'geojson']);
});