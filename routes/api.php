<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;

// Import Controllers Asli Proyek Kamu
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

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ============================================================================
// 1. ROUTE LOGIN (Closure sederhana untuk testing RBAC)
// ============================================================================
Route::post('/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $user = User::where('email', $request->email)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    return response()->json([
        'message' => 'Login berhasil',
        'token' => $user->createToken('auth_token')->plainTextToken,
        'user' => $user->name,
        'role' => $user->getRoleNames()->first(),
    ]);
});

// ============================================================================
// 2. ROUTE PUBLIK (Tidak perlu login)
// ============================================================================
Route::get('/public-species', function () {
    return response()->json(['message' => 'Ini data publik species']);
});

// ============================================================================
// 3. ROUTE BUTUH LOGIN & RBAC (Testing)
// ============================================================================
Route::middleware('auth:sanctum')->group(function () {
    
    // Profile user yang sedang login
    Route::get('/my-profile', function (Request $request) {
        $user = $request->user();
        return response()->json([
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    });

    // --- ROUTE KHUSUS ADMIN ---
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', function () {
            return response()->json([
                'message' => 'Welcome Admin! Anda punya akses penuh.',
                'user' => auth()->user()->name,
                'roles' => auth()->user()->getRoleNames(),
                'permissions' => auth()->user()->getAllPermissions()->pluck('name'),
            ]);
        });

        Route::get('/admin/users', function () {
            return response()->json([
                'message' => 'List semua users (admin only)',
                'users' => User::all(['id', 'name', 'email']),
            ]);
        });
    });

    // --- ROUTE KHUSUS STAFF ---
    Route::middleware('role:staff')->group(function () {
        Route::get('/staff/species', function () {
            return response()->json(['message' => 'Kelola species (staff & admin)']);
        });

        Route::get('/staff/tracking-devices', function () {
            return response()->json(['message' => 'Kelola tracking devices (staff & admin)']);
        });
    });

    // ============================================================================
    // 4. ROUTE ASLI PROYEK KAMU (CONTROLLERS)
    // ============================================================================
    
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/species', [SpeciesController::class, 'index']);
    Route::get('/animals', [AnimalController::class, 'index']);

    Route::get('/devices', [TrackingDeviceController::class, 'index']);
    Route::get('/devices/{id}', [TrackingDeviceController::class, 'show']);

    Route::get('/gps-readings', [GPSReadingController::class, 'index']);
    Route::get('/gps-readings/{animal_id}', [GPSReadingController::class, 'byAnimal']);
    Route::post('/gps-readings', [GPSReadingController::class, 'store']);

    Route::get('/geo-zones', [GeoZoneController::class, 'index']);
    Route::get('/geo-zones/{id}', [GeoZoneController::class, 'show']);

    Route::get('/incidents', [IncidentReportController::class, 'index']);
    Route::get('/incidents/{id}', [IncidentReportController::class, 'show']);
    Route::post('/incidents', [IncidentReportController::class, 'store']);
    Route::put('/incidents/{id}', [IncidentReportController::class, 'update']);
    Route::delete('/incidents/{id}', [IncidentReportController::class, 'destroy']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread', [NotificationController::class, 'unread']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::get('/export/gps/csv', [ExportController::class, 'csv']);
    Route::get('/export/gps/geojson', [ExportController::class, 'geojson']);
});