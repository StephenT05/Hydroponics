<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OllamaController;
use App\Http\Controllers\SensorController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard/live', [DashboardController::class, 'live'])->name('dashboard.live');

Route::get('/history', function () {
    return redirect('/dashboard#history');
});

// Sensor endpoints
Route::post('/upload-leaf', [SensorController::class, 'uploadLeaf']);
Route::post('/temperature', [SensorController::class, 'temperature']);
Route::post('/tds', [SensorController::class, 'tds']);
Route::post('/water-level', [SensorController::class, 'waterLevel']);
// Update OpenWeather location (lat/lon) from dashboard
Route::post('/settings/weather-location', [DashboardController::class, 'updateWeatherLocation']);

// Ollama local model run endpoint
Route::post('/ollama/run', [OllamaController::class, 'run']);
