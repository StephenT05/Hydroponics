<?php

return [
    // OpenWeather API configuration
    'api_key' => env('OPENWEATHER_API_KEY', ''),
    // Default to Batac City, Ilocos Norte (Philippines). Can be overridden via env or dashboard.
    'latitude' => env('OPENWEATHER_LATITUDE', 18.03),
    'longitude' => env('OPENWEATHER_LONGITUDE', 120.53),
    'units' => env('OPENWEATHER_UNITS', 'metric'), // metric, imperial, standard
    'cache_minutes' => env('OPENWEATHER_CACHE_MINUTES', 10),
    // Whether to verify SSL certs when contacting the API. Set to false only for local dev if needed.
    'verify' => env('OPENWEATHER_VERIFY_SSL', true),
];
