<?php

namespace App\Http\Controllers;

use App\Services\WeatherService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private const HIGH_TEMPERATURE_THRESHOLD = 28.0;

    private const LOW_TDS_THRESHOLD = 700.0;

    public function __construct(private WeatherService $weatherService) {}

    public function index(): View
    {
        return view('dashboard', $this->dashboardData());
    }

    public function live(): JsonResponse
    {
        return response()->json($this->dashboardData());
    }

    private function dashboardData(): array
    {
        $latestPrediction = DB::table('predictions')->orderByDesc('id')->first();
        $latestTemperature = DB::table('temperature_readings')->orderByDesc('id')->first();
        $latestTds = DB::table('tds_readings')->orderByDesc('id')->first();
        $latestWaterLevel = DB::table('water_level_readings')->orderByDesc('id')->first();
        $latestTdsFormatted = $this->formatTds($latestTds);
        $latestWaterLevelFormatted = $this->formatWaterLevel($latestWaterLevel);
        $weatherData = $this->weatherService->getCurrentWeather();
        $locationOverride = Cache::get('openweather:location') ?? null;
        $weatherLocation = [
            'lat' => $locationOverride['lat'] ?? config('openweather.latitude'),
            'lon' => $locationOverride['lon'] ?? config('openweather.longitude'),
        ];

        return [
            'latestPrediction' => $this->formatPrediction($latestPrediction),
            'latestTemperature' => $this->formatTemperature($latestTemperature),
            'latestTds' => $latestTdsFormatted,
            'latestWaterLevel' => $latestWaterLevelFormatted,
            'currentWeather' => $weatherData,
            'weatherLocation' => $weatherLocation,
            'warningStates' => [
                'highTemperature' => $latestTemperature !== null && (float) $latestTemperature->temperature >= self::HIGH_TEMPERATURE_THRESHOLD,
                'lowTds' => $this->isLowTdsWarning($latestTdsFormatted),
            ],
            'overviewCards' => $this->overviewCards($latestPrediction, $latestTemperature, $latestTds),
            'recentPredictions' => $this->recentPredictions(),
            'recentSensorLogs' => $this->recentSensorLogs(),
            'futureFeatures' => [
                [
                    'title' => 'Weather API sunlight estimation',
                    'description' => 'Predict outdoor light availability and adjust grow-light timing before deficiencies appear.',
                    'status' => 'Planned',
                ],
                [
                    'title' => 'Ollama AI recommendations',
                    'description' => 'Generate crop-specific advice from the latest sensor trends and image analysis.',
                    'status' => 'Planned',
                ],
                [
                    'title' => 'Pump automation',
                    'description' => 'Trigger dosing and circulation routines from safe rule-based control logic.',
                    'status' => 'Prototype',
                ],
                [
                    'title' => 'Alert system',
                    'description' => 'Send instant warnings for heat spikes, low nutrient levels, or AI deficiency hits.',
                    'status' => 'Prototype',
                ],
            ],
            'refreshInterval' => 45,
            'lastSyncedAt' => now()->format('M d, Y H:i'),
            'liveSyncedAt' => now()->toIso8601String(),
        ];
    }

    public function updateWeatherLocation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat' => 'required|numeric',
            'lon' => 'required|numeric',
        ]);

        Cache::put('openweather:location', ['lat' => (float) $data['lat'], 'lon' => (float) $data['lon']], now()->addYears(5));

        return response()->json(['ok' => true, 'location' => Cache::get('openweather:location')]);
    }

    private function overviewCards(mixed $latestPrediction, mixed $latestTemperature, mixed $latestTds): array
    {
        $predictionConfidence = $latestPrediction !== null ? round(((float) $latestPrediction->confidence) * 100, 1) : null;

        $latestTdsFormatted = $this->formatTds($latestTds);

        return [
            [
                'label' => 'AI analysis',
                'value' => $latestPrediction !== null ? $this->displayPredictionLabel((string) $latestPrediction->prediction) : 'Waiting for image',
                'meta' => $latestPrediction !== null ? 'Confidence '.number_format($predictionConfidence, 1).'%' : 'No plant image uploaded yet',
                'tone' => $latestPrediction !== null ? 'emerald' : 'slate',
            ],
            [
                'label' => 'Water temperature',
                'value' => $latestTemperature !== null ? number_format((float) $latestTemperature->temperature, 1).'°C' : 'No reading yet',
                'meta' => $latestTemperature !== null ? $this->formatElapsedTime(Carbon::parse($latestTemperature->created_at)) : 'Waiting for ESP8266 telemetry',
                'tone' => $latestTemperature !== null && (float) $latestTemperature->temperature >= self::HIGH_TEMPERATURE_THRESHOLD ? 'amber' : 'cyan',
            ],
            [
                'label' => 'TDS / EC',
                'value' => $latestTdsFormatted !== null ? ($latestTdsFormatted['uncalibrated'] ? ('Estimated Nutrient Level: '.$latestTdsFormatted['category']) : ($latestTdsFormatted['value'].' ppm')) : 'No reading yet',
                'meta' => $latestTdsFormatted !== null ? $this->formatElapsedTime(Carbon::parse($latestTdsFormatted['createdAt'])) : 'Awaiting hydroponic sensor data',
                'tone' => $latestTdsFormatted !== null && (! $latestTdsFormatted['uncalibrated'] ? ((int) $latestTdsFormatted['value'] <= self::LOW_TDS_THRESHOLD ? 'rose' : 'emerald') : 'emerald'),
            ],
            [
                'label' => 'Telemetry sync',
                'value' => $this->recordsCount('predictions') + $this->recordsCount('temperature_readings') + $this->recordsCount('tds_readings').' records',
                'meta' => 'Latest sync '.$this->formatElapsedTime(now()),
                'tone' => 'violet',
            ],
        ];
    }

    private function recentPredictions(): array
    {
        return DB::table('predictions')
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->map(function (object $prediction): array {
                $confidence = round(((float) $prediction->confidence) * 100, 1);

                return [
                    'id' => $prediction->id,
                    'image' => asset('storage/'.$prediction->image),
                    'prediction' => $this->displayPredictionLabel((string) $prediction->prediction),
                    'confidence' => number_format($confidence, 1).'%',
                    'confidenceValue' => $confidence,
                    'createdAt' => Carbon::parse($prediction->created_at)->format('M d, H:i'),
                ];
            })
            ->all();
    }

    private function recentSensorLogs(): array
    {
        $temperatureLogs = DB::table('temperature_readings')
            ->selectRaw("'Water temperature' as sensor, temperature as reading, created_at, id")
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(function (object $reading): array {
                return [
                    'sensor' => $reading->sensor,
                    'reading' => number_format((float) $reading->reading, 1).' °C',
                    'tone' => 'cyan',
                    'createdAt' => Carbon::parse($reading->created_at),
                    'timestamp' => Carbon::parse($reading->created_at)->format('M d, H:i'),
                ];
            });

        $tdsLogs = DB::table('tds_readings')
            ->selectRaw("'TDS / EC' as sensor, tds_value as reading, created_at, id")
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(function (object $reading): array {
                $formatted = $this->formatTds($reading);

                $display = $formatted !== null ? ($formatted['uncalibrated'] ? $formatted['category'] : ($formatted['value'].' ppm')) : number_format((float) $reading->reading, 0).' ppm';

                return [
                    'sensor' => $reading->sensor,
                    'reading' => $formatted !== null ? ($formatted['category'] ?? $formatted['value']) : $display,
                    'raw' => $formatted !== null ? $formatted['value'] : number_format((float) $reading->reading, 0),
                    'tone' => 'emerald',
                    'createdAt' => Carbon::parse($reading->created_at),
                    'timestamp' => Carbon::parse($reading->created_at)->format('M d, H:i'),
                ];
            });

        return $temperatureLogs
            ->merge($tdsLogs)
            ->sortByDesc(fn (array $reading): Carbon => $reading['createdAt'])
            ->values()
            ->all();
    }

    private function formatPrediction(mixed $prediction): ?array
    {
        if ($prediction === null) {
            return null;
        }

        $confidence = round(((float) $prediction->confidence) * 100, 1);

        return [
            'id' => $prediction->id,
            'image' => asset('storage/'.$prediction->image),
            'prediction' => $this->displayPredictionLabel((string) $prediction->prediction),
            'confidence' => number_format($confidence, 1).'%',
            'confidenceValue' => $confidence,
            'createdAt' => Carbon::parse($prediction->created_at)->format('M d, Y H:i'),
            'relativeTime' => $this->formatElapsedTime(Carbon::parse($prediction->created_at)),
        ];
    }

    private function formatTemperature(mixed $reading): ?array
    {
        if ($reading === null) {
            return null;
        }

        return [
            'value' => number_format((float) $reading->temperature, 1),
            'createdAt' => Carbon::parse($reading->created_at)->format('M d, Y H:i'),
            'relativeTime' => $this->formatElapsedTime(Carbon::parse($reading->created_at)),
        ];
    }

    private function formatTds(mixed $reading): ?array
    {
        if ($reading === null) {
            return null;
        }

        // Support being passed either a DB row (tds_value) or a mapped reading (reading)
        $raw = null;
        $createdAt = null;

        if (is_object($reading)) {
            if (property_exists($reading, 'tds_value')) {
                $raw = (float) $reading->tds_value;
            } elseif (property_exists($reading, 'reading')) {
                $raw = (float) $reading->reading;
            }

            $createdAt = property_exists($reading, 'created_at') ? $reading->created_at : null;
        } elseif (is_numeric($reading)) {
            $raw = (float) $reading;
        }

        if ($raw === null) {
            return null;
        }

        // Treat small numeric ranges (typical uncalibrated conductivity trend values) as uncalibrated
        $uncalibrated = $raw <= 30;

        if ($uncalibrated) {
            $category = $this->mapUncalibratedTds($raw);

            return [
                'value' => (string) number_format($raw, 0),
                'category' => $category,
                'uncalibrated' => true,
                'createdAt' => $createdAt ? Carbon::parse($createdAt)->format('M d, Y H:i') : now()->format('M d, Y H:i'),
                'relativeTime' => $createdAt ? $this->formatElapsedTime(Carbon::parse($createdAt)) : $this->formatElapsedTime(now()),
            ];
        }

        return [
            'value' => number_format($raw, 0),
            'category' => null,
            'uncalibrated' => false,
            'createdAt' => $createdAt ? Carbon::parse($createdAt)->format('M d, Y H:i') : now()->format('M d, Y H:i'),
            'relativeTime' => $createdAt ? $this->formatElapsedTime(Carbon::parse($createdAt)) : $this->formatElapsedTime(now()),
        ];
    }

    private function formatElapsedTime(Carbon $timestamp): string
    {
        $diffInSeconds = $timestamp->diffInSeconds(now());

        $days = intdiv($diffInSeconds, 86400);
        $remainingAfterDays = $diffInSeconds % 86400;
        $hours = intdiv($remainingAfterDays, 3600);
        $remainingAfterHours = $remainingAfterDays % 3600;
        $minutes = intdiv($remainingAfterHours, 60);

        return $days.'d '.$hours.'h '.$minutes.'m';
    }

    private function mapUncalibratedTds(float $value): string
    {
        // Map raw conductivity trend values to nutrient-level categories:
        // 0-8 => Low, 9-15 => Moderate, 16+ => High
        if ($value <= 8) {
            return 'Low';
        }

        if ($value <= 15) {
            return 'Moderate';
        }

        return 'High';
    }

    private function isLowTdsWarning(?array $latestTdsFormatted): bool
    {
        if ($latestTdsFormatted === null) {
            return false;
        }

        if ($latestTdsFormatted['uncalibrated']) {
            return $latestTdsFormatted['category'] === 'Low';
        }

        return (float) $latestTdsFormatted['value'] <= self::LOW_TDS_THRESHOLD;
    }

    private function recordsCount(string $table): int
    {
        return DB::table($table)->count();
    }

    private function displayPredictionLabel(string $label): string
    {
        $normalized = trim($label);

        if ($normalized === '') {
            return $normalized;
        }

        if (strtolower($normalized) === 'healthy') {
            return $normalized;
        }

        if (str_starts_with(strtolower($normalized), 'deficient ') || str_starts_with(strtolower($normalized), 'defficient ') || str_contains(strtolower($normalized), ' deficiency')) {
            return $normalized;
        }

        return 'Deficient '.$normalized;
    }

    private function formatWaterLevel(mixed $reading): ?array
    {
        if ($reading === null) {
            return null;
        }

        $percentage = (float) $reading->percentage;

        return [
            'percentage' => round($percentage, 1),
            'distanceCm' => number_format((float) $reading->distance_cm, 2),
            'createdAt' => Carbon::parse($reading->created_at)->format('M d, Y H:i'),
            'relativeTime' => $this->formatElapsedTime(Carbon::parse($reading->created_at)),
        ];
    }
}
