<?php

namespace App\Services;

use App\Models\WeatherReading;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    private const API_URL = 'https://api.openweathermap.org/data/2.5/weather';

    public function getCurrentWeather(): ?array
    {
        $apiKey = config('openweather.api_key');
        $latitude = config('openweather.latitude');
        $longitude = config('openweather.longitude');
        $units = config('openweather.units');

        // Allow an in-app override (saved from dashboard) to take precedence
        $override = Cache::get('openweather:location');
        if (is_array($override) && isset($override['lat'], $override['lon'])) {
            $latitude = (float) $override['lat'];
            $longitude = (float) $override['lon'];
        }

        if (! $apiKey) {
            return null;
        }

        $cacheMinutes = (int) config('openweather.cache_minutes', 10);

        // Build params: prefer lat/lon, fall back to city 'Batac'
        $useLatLon = ($latitude !== 0.0 || $longitude !== 0.0);
        $defaultCity = 'Batac';

        $cacheKeySuffix = $useLatLon ? sprintf('%s,%s', round($latitude, 4), round($longitude, 4)) : $defaultCity;
        $cacheKey = 'weather:current:'.$cacheKeySuffix;

        return Cache::remember($cacheKey, now()->addMinutes($cacheMinutes), function () use ($apiKey, $latitude, $longitude, $units, $useLatLon, $defaultCity) {
            try {
                $verify = filter_var(config('openweather.verify', true), FILTER_VALIDATE_BOOLEAN);

                $client = Http::withOptions(['verify' => $verify]);

                $params = [
                    'appid' => $apiKey,
                    'units' => $units,
                ];

                if ($useLatLon) {
                    $params['lat'] = $latitude;
                    $params['lon'] = $longitude;
                } else {
                    $params['q'] = $defaultCity;
                }

                $response = $client->get(self::API_URL, $params)->throw();

                $data = $response->json();

                $payload = [
                    'temperature' => (float) ($data['main']['temp'] ?? 0),
                    'feels_like' => (float) ($data['main']['feels_like'] ?? 0),
                    'humidity' => (int) ($data['main']['humidity'] ?? 0),
                    'pressure' => (int) ($data['main']['pressure'] ?? 0),
                    'description' => (string) ($data['weather'][0]['description'] ?? 'Unknown'),
                    'main' => (string) ($data['weather'][0]['main'] ?? 'Unknown'),
                    'icon' => (string) ($data['weather'][0]['icon'] ?? null),
                    'wind_speed' => (float) ($data['wind']['speed'] ?? 0),
                    'clouds' => (int) ($data['clouds']['all'] ?? 0),
                    'sunrise' => (int) ($data['sys']['sunrise'] ?? 0),
                    'sunset' => (int) ($data['sys']['sunset'] ?? 0),
                ];

                // Persist a weather reading for the requested location
                try {
                    WeatherReading::create([
                        'lat' => $useLatLon ? $latitude : null,
                        'lon' => $useLatLon ? $longitude : null,
                        'city' => $data['name'] ?? null,
                        'temperature' => $payload['temperature'],
                        'feels_like' => $payload['feels_like'],
                        'humidity' => $payload['humidity'],
                        'pressure' => $payload['pressure'],
                        'description' => $payload['description'],
                        'main' => $payload['main'],
                        'icon' => $payload['icon'] ?? null,
                        'wind_speed' => $payload['wind_speed'],
                        'clouds' => $payload['clouds'],
                        'sunrise' => $payload['sunrise'],
                        'sunset' => $payload['sunset'],
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to save weather reading: '.$e->getMessage());
                }

                return $payload;
            } catch (\Exception $e) {
                Log::error('WeatherService error: '.$e->getMessage());

                return null;
            }
        });
    }
}
