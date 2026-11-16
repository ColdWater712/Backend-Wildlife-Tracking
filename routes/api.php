<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SpeciesController;
use App\Http\Controllers\Api\AnimalController;
use App\Http\Controllers\Api\TrackingDeviceController;
use App\Http\Controllers\Api\GpsReadingController;
use App\Http\Controllers\Api\GeoZoneController;
use App\Http\Controllers\Api\IncidentReportController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\AiPredictionController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {
    
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', function (\Illuminate\Http\Request $request) {
        return response()->json(['success' => true, 'data' => $request->user()]);
    });

    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::get('/species', [SpeciesController::class, 'index']);
    Route::get('/species/{id}', [SpeciesController::class, 'show']);

    Route::get('/animals', [AnimalController::class, 'index']);
    Route::post('/animals', [AnimalController::class, 'store']);

    Route::get('/devices', [TrackingDeviceController::class, 'index']);
    Route::get('/devices/{id}', [TrackingDeviceController::class, 'show']);

    Route::get('/gps-readings', [GpsReadingController::class, 'index']);
    Route::post('/gps-readings', [GpsReadingController::class, 'store']);

    Route::get('/geo-zones', [GeoZoneController::class, 'index']);
    Route::post('/geo-zones', [GeoZoneController::class, 'store']);

    Route::get('/incidents', [IncidentReportController::class, 'index']);
    Route::post('/incidents', [IncidentReportController::class, 'store']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

  
    Route::get('/ai-predictions', [AiPredictionController::class, 'index']);
    Route::post('/ai-predictions/{id}/verify', [AiPredictionController::class, 'verify']);
});