<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SensorController extends Controller
{
    public function uploadLeaf(Request $request): JsonResponse
    {
        // Generate filename
        $filename = time().'.jpg';

        // Save image
        $relativePath = 'leaf_images/'.$filename;
        $fullPath = storage_path('app/public/'.$relativePath);

        file_put_contents($fullPath, $request->getContent());

        // Build Python command using Laravel path helpers
        $pythonExe = base_path('ai/venv/Scripts/python.exe');
        $predictScript = base_path('ai/predict.py');
        $command = $pythonExe.' '.$predictScript.' '.escapeshellarg($fullPath);

        // Run AI prediction
        $output = shell_exec($command);

        // Parse result
        [$prediction, $confidence] = explode('|', trim($output));

        // Save to database
        DB::table('predictions')->insert([
            'image' => $relativePath,
            'prediction' => $prediction,
            'confidence' => $confidence,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Return JSON response
        return response()->json([
            'success' => true,
            'prediction' => $prediction,
            'confidence' => $confidence,
            'image' => $relativePath,
        ]);
    }

    public function temperature(Request $request): JsonResponse
    {
        $temperature = $request->input('temperature');

        DB::table('temperature_readings')->insert([
            'temperature' => $temperature,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'temperature' => $temperature,
        ]);
    }

    public function tds(Request $request): JsonResponse
    {
        $tds = $request->input('tds');

        DB::table('tds_readings')->insert([
            'tds_value' => $tds,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'tds' => $tds,
        ]);
    }

    public function waterLevel(Request $request): JsonResponse
    {
        // Raw distance reading in cm from HC-SR04 sensor
        $distanceCm = (float) $request->input('distance_cm');

        // Get tank configuration
        $tankHeightCm = config('hydroponics.tank_height_cm');
        $sensorDeadzoneCm = config('hydroponics.sensor_deadzone_cm');
        $usableHeightCm = $tankHeightCm - $sensorDeadzoneCm;

        // Clamp raw reading to valid range
        $clampedDistance = max($sensorDeadzoneCm, min($tankHeightCm, $distanceCm));

        // Calculate percentage: 0% = empty, 100% = full
        $percentageFull = (($tankHeightCm - $clampedDistance) / $usableHeightCm) * 100;
        $percentageFull = max(0, min(100, $percentageFull));

        // Store raw distance and calculated percentage
        DB::table('water_level_readings')->insert([
            'distance_cm' => $distanceCm,
            'percentage' => $percentageFull,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'distance_cm' => $distanceCm,
            'percentage' => round($percentageFull, 2),
            'tank_height_cm' => $tankHeightCm,
        ]);
    }

    /**
     * Calculate trimmed mean of readings to filter outliers.
     * Removes a configured percentage of highest and lowest values.
     */
    private function trimmedMean(array $values): float
    {
        if (empty($values)) {
            return 0;
        }

        sort($values);
        $trimPercent = config('hydroponics.trimmed_mean_percent', 10) / 100;
        $trimCount = (int) floor(count($values) * $trimPercent);

        // Remove $trimCount values from each end
        $trimmed = array_slice($values, $trimCount, count($values) - (2 * $trimCount));

        return count($trimmed) > 0 ? array_sum($trimmed) / count($trimmed) : 0;
    }
}
