<?php
use App\Http\Controllers\Api\MonitoringController;
use App\Http\Controllers\Api\NotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Notification endpoints
    Route::post('/notifications', [NotificationController::class, 'store']);
    Route::get('/notifications/rate-limit/{userId}', [NotificationController::class, 'rateLimitStatus']);
    Route::post('/notifications/{id}/retry', [NotificationController::class, 'retry']);
    
    // Monitoring endpoints
    Route::get('/monitoring/notifications', [MonitoringController::class, 'recent']);
    Route::get('/monitoring/summary', [MonitoringController::class, 'summary']);
    Route::get('/monitoring/summary/{tenantId}', [MonitoringController::class, 'summary']);
});