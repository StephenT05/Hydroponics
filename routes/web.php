<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/dashboard/live', [DashboardController::class, 'live'])->name('dashboard.live');

Route::get('/history', function () {
    return redirect('/dashboard#history');
});

Route::post('/upload-leaf', function (Request $request) {

    // Generate filename
    $filename = time() . '.jpg';

    // Save image
    $relativePath = 'leaf_images/' . $filename;

    $fullPath = storage_path('app/public/' . $relativePath);

    file_put_contents($fullPath, $request->getContent());

    // PYTHON COMMAND
    $command =
        "C:\\Users\\Stephen\\Documents\\Hydroponics\\Hydroponics\\ai\\venv\\Scripts\\python.exe "
        . "C:\\Users\\Stephen\\Documents\\Hydroponics\\Hydroponics\\ai\\predict.py "
        . escapeshellarg($fullPath);

    // RUN AI
    $output = shell_exec($command);

    // PARSE RESULT
    list($prediction, $confidence) = explode('|', trim($output));

    // SAVE TO DATABASE
    DB::table('predictions')->insert([
        'image' => $relativePath,
        'prediction' => $prediction,
        'confidence' => $confidence,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    // RETURN JSON RESPONSE
    return response()->json([
        'success' => true,
        'prediction' => $prediction,
        'confidence' => $confidence,
        'image' => $relativePath
    ]);
});

Route::post('/temperature', function (Request $request) {

    $temperature = $request->input('temperature');

    DB::table('temperature_readings')->insert([
        'temperature' => $temperature,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    return response()->json([
        'success' => true,
        'temperature' => $temperature
    ]);
});

Route::post('/tds', function (Request $request) {

    $tds = $request->input('tds');

    DB::table('tds_readings')->insert([
        'tds_value' => $tds,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    return response()->json([
        'success' => true,
        'tds' => $tds
    ]);
});