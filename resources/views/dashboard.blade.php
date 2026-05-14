<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Hydroponics AI') }} Dashboard</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            .dark-surface {
                background-color: rgba(2, 6, 23, 0.92);
            }

            #overview,
            section.space-y-4 > article,
            #ai-monitor > article,
            #history > article,
            #overview .w-full.space-y-4 > div,
            #overview .space-y-3 > div,
            #ai-monitor .space-y-4.rounded,
            #ai-monitor .grid.grid-cols-1 > div,
            #ai-monitor .overflow-hidden.rounded,
            #ai-monitor .mt-5.space-y-4 > div {
                background-color: rgba(255, 255, 255, 0.08) !important;
                border-color: rgba(255, 255, 255, 0.2) !important;
            }
        </style>
    </head>
    <body
        data-live-url="{{ route('dashboard.live') }}"
        data-live-interval="{{ $refreshInterval }}"
        class="dark-surface min-h-screen text-slate-100 antialiased"
        style="background-image: url('https://www.transparenttextures.com/patterns/cartographer.png'); background-repeat: repeat;"
    >
        <div class="relative flex min-h-screen justify-center">
            <main class="w-full px-6 py-6">
                <div class="mx-auto w-full md:w-[60%] space-y-6">
                    <section id="overview" class="overflow-hidden rounded border border-white/10 bg-slate-900/85">
                        <div class="dashboard-grid px-5 py-5 sm:px-6 lg:px-8">
                            <div class="space-y-6">
                                <div class="space-y-4">
                                    
                                    <div>
                                        <h2 class="text-3xl font-semibold tracking-tight text-white sm:text-4xl">Hydrolink AI</h2>
                                        <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300 sm:text-base">
                                            Track temperature, raw conductivity trend, and warning states up front. Happy gardening
                                        </p>
                                    </div>

                                    <div class="space-y-4">
                                        <div class="w-full space-y-4">
                                            <div class="rounded border border-white/10 bg-slate-900/80 p-6">
                                                <p class="text-xs uppercase tracking-[0.28em] text-rose-200/70">Temperature</p>
                                                <p class="mt-3 text-3xl font-semibold text-white">{{ $latestTemperature['value'] ?? '--.-' }}<span class="ml-1 text-xl text-rose-200">°C</span></p>
                                                <p class="mt-2 text-sm text-slate-400">{{ $latestTemperature['relativeTime'] ?? 'No reading yet' }}</p>
                                            </div>

                                            <div class="rounded border border-white/10 bg-slate-900/80 p-6">
                                                <p class="text-xs uppercase tracking-[0.28em] text-amber-200/70">Conductivity</p>
                                                <p class="mt-3 text-3xl font-semibold text-white">{{ $latestTds['uncalibrated'] ?? false ? ($latestTds['category'] ?? 'No reading yet') : ($latestTds['value'] ?? '--') }}</p>
                                                <p class="mt-2 text-sm text-slate-400">{{ $latestTds['uncalibrated'] ?? false ? 'Estimated nutrient level' : 'ppm' }}</p>
                                            </div>

                                            <div class="rounded border border-white/10 bg-slate-900/80 p-6">
                                                <p class="text-xs uppercase tracking-[0.28em] text-fuchsia-200/70">Warnings</p>
                                                <p class="mt-3 text-3xl font-semibold text-white">{{ ($warningStates['highTemperature'] ? 1 : 0) + ($warningStates['lowTds'] ? 1 : 0) }}</p>
                                                <p class="mt-2 text-sm text-slate-400">Active alert(s)</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    @foreach ($overviewCards as $card)
                                        <div class="rounded border border-white/10 bg-slate-900/80 p-4">
                                            <p class="text-xs uppercase tracking-[0.28em] text-slate-400">{{ $card['label'] }}</p>
                                            <p class="overview-value mt-3 text-lg font-semibold text-white">{{ $card['value'] }}</p>
                                            <p class="overview-meta mt-2 text-sm text-slate-400">{{ $card['meta'] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="space-y-4">
                        <article class="rounded border border-white/10 bg-slate-900/85 p-5">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.28em] text-cyan-200/70">Water temperature</p>
                                    <h3 class="mt-2 text-4xl font-semibold text-white"><span id="latest-temperature-value-card">{{ $latestTemperature['value'] ?? '--.-' }}</span><span class="ml-1 text-cyan-200">°C</span></h3>
                                </div>
                                <div class="rounded-2xl bg-cyan-400/15 p-3 text-cyan-200">
                                    <svg viewBox="0 0 24 24" fill="none" class="h-6 w-6" stroke="currentColor" stroke-width="1.8">
                                        <path d="M10 14.5V5a2 2 0 1 1 4 0v9.5a4 4 0 1 1-4 0Z" />
                                    </svg>
                                </div>
                            </div>

                            <div class="mt-5 flex items-end justify-between gap-4">
                                <div>
                                    <p class="text-sm text-slate-400">Current reading</p>
                                    <p id="latest-temperature-relative-time-card" class="mt-1 text-lg font-semibold text-white">{{ $latestTemperature['relativeTime'] ?? 'No reading yet' }}</p>
                                </div>
                                <div class="text-right text-xs text-slate-400">
                                    <p id="latest-temperature-created-at-card">{{ $latestTemperature['createdAt'] ?? 'No data' }}</p>
                                    <p class="mt-1 text-cyan-200">Live sensor feed</p>
                                </div>
                            </div>

                            <div class="mt-5 h-2 overflow-hidden rounded-full bg-white/10">
                                <div class="h-full rounded-full bg-gradient-to-r from-cyan-300 via-emerald-300 to-lime-300" style="width: 100%;"></div>
                            </div>

                            <div id="temperature-warning" class="mt-5 flex items-center gap-2 text-sm {{ $warningStates['highTemperature'] ? 'text-amber-200' : 'text-emerald-200' }}">
                                <span id="temperature-warning-dot" class="h-2 w-2 rounded-full {{ $warningStates['highTemperature'] ? 'bg-amber-300' : 'bg-emerald-300' }}"></span>
                                <span id="temperature-warning-text">{{ $warningStates['highTemperature'] ? 'High temperature warning' : 'Temperature within range' }}</span>
                            </div>
                        </article>

                        <article class="rounded border border-white/10 bg-slate-900/85 p-5">
                            <p class="text-xs uppercase tracking-[0.28em] text-emerald-200/70">Conductivity trend</p>
                            <div class="mt-4 flex items-end justify-between gap-4">
                                <div>
                                    @if (!empty($latestTds) && ($latestTds['uncalibrated'] ?? false))
                                        <p class="text-xs text-slate-400">Estimated Nutrient Level</p>
                                        <p class="text-4xl font-semibold text-white"><span id="latest-tds-value">{{ $latestTds['category'] ?? 'No reading yet' }}</span></p>
                                        <p id="latest-tds-raw" class="mt-1 text-xs text-slate-400">raw data {{ $latestTds['value'] }}</p>
                                        <p id="latest-tds-relative-time" class="mt-2 text-sm text-slate-400">{{ $latestTds['relativeTime'] ?? 'No reading yet' }}</p>
                                    @else
                                        <p class="text-4xl font-semibold text-white"><span id="latest-tds-value">{{ $latestTds['value'] ?? '--' }}</span><span class="ml-1 text-xl text-emerald-200">ppm</span></p>
                                        <p id="latest-tds-relative-time" class="mt-2 text-sm text-slate-400">{{ $latestTds['relativeTime'] ?? 'No reading yet' }}</p>
                                    @endif
                                </div>
                                <div class="rounded-2xl bg-emerald-400/15 p-3 text-emerald-200">
                                    <svg viewBox="0 0 24 24" fill="none" class="h-6 w-6" stroke="currentColor" stroke-width="1.8">
                                        <path d="M4 18h16" />
                                        <path d="M7 18V9" />
                                        <path d="M12 18V6" />
                                        <path d="M17 18v-4" />
                                    </svg>
                                </div>
                            </div>
                            @php
                                $tdsUncalibrated = $latestTds['uncalibrated'] ?? false;
                                $tdsCategory = $latestTds['category'] ?? null;
                                $tdsLowState = $tdsUncalibrated ? ($tdsCategory === 'Low') : ($warningStates['lowTds'] ?? false);
                            @endphp
                            <div id="tds-warning" class="mt-5 flex items-center gap-2 text-sm {{ $tdsLowState ? 'text-rose-200' : 'text-emerald-200' }}">
                                <span id="tds-warning-dot" class="h-2 w-2 rounded-full {{ $tdsLowState ? 'bg-rose-300' : 'bg-emerald-300' }}"></span>
                                <span id="tds-warning-text">{{ $tdsUncalibrated ? ($tdsCategory ?? 'Nutrient level unknown') : ($warningStates['lowTds'] ? 'Low nutrient warning' : 'Nutrient level acceptable') }}</span>
                            </div>
                        </article>

                        <article class="rounded border border-white/10 bg-slate-900/85 p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.28em] text-blue-200/70">Water level</p>
                                    <p class="mt-2 text-sm text-slate-400">Tank capacity</p>
                                    <p class="mt-3 text-4xl font-semibold text-white"><span id="latest-water-level-percentage">{{ $latestWaterLevel['percentage'] ?? '--' }}</span><span class="ml-1 text-xl text-blue-200">%</span></p>
                                    <p id="latest-water-distance" class="mt-3 text-sm text-slate-400">{{ $latestWaterLevel['distanceCm'] ?? '--' }} cm</p>
                                    <div class="mt-4 text-xs text-slate-500">
                                        <p id="latest-water-created-at">{{ $latestWaterLevel['createdAt'] ?? 'No data' }}</p>
                                        <p id="latest-water-relative-time" class="mt-1 text-blue-200">{{ $latestWaterLevel['relativeTime'] ?? 'No reading yet' }}</p>
                                    </div>
                                </div>

                                <!-- Vertical tank on the right -->
                                <div class="relative h-40 w-20 overflow-hidden rounded-lg border-2 border-blue-400/40 bg-slate-950/60">
                                    <div id="water-fill" class="absolute bottom-0 w-full bg-gradient-to-t from-blue-500/50 via-blue-400/40 to-transparent transition-all duration-500 ease-out" style="height: {{ $latestWaterLevel['percentage'] ?? 0 }}%;"></div>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span id="water-level-text" class="text-xs font-bold text-blue-200 drop-shadow-lg">{{ $latestWaterLevel['percentage'] ?? '—' }}%</span>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <article class="rounded border border-white/10 bg-slate-900/85 p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.28em] text-orange-200/70">Current weather</p>
                                    <p class="mt-3 text-4xl font-semibold text-white"><span id="weather-temperature">{{ $currentWeather['temperature'] ?? '--' }}</span><span class="ml-1 text-xl text-orange-200">°C</span></p>
                                    <p id="weather-description" class="mt-2 text-sm text-slate-400 capitalize">{{ $currentWeather['description'] ?? 'No data' }}</p>
                                    <div class="mt-4 space-y-2 text-sm text-slate-300">
                                        <div class="flex justify-between">
                                            <span class="text-slate-400">Humidity</span>
                                            <span id="weather-humidity" class="font-semibold">{{ $currentWeather['humidity'] ?? '--' }}%</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-400">Wind</span>
                                            <span id="weather-wind" class="font-semibold">{{ $currentWeather['wind_speed'] ?? '--' }} m/s</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-400">Clouds</span>
                                            <span id="weather-clouds" class="font-semibold">{{ $currentWeather['clouds'] ?? '--' }}%</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="rounded-2xl bg-orange-400/15 p-3 text-orange-200">
                                    @php
                                        $iconCode = $currentWeather['icon'] ?? null;
                                        $weatherMain = strtolower($currentWeather['main'] ?? 'clear');
                                    @endphp

                                    @if ($iconCode)
                                        <img src="https://openweathermap.org/img/wn/{{ $iconCode }}@2x.png" alt="{{ $currentWeather['description'] ?? 'weather' }}" class="h-8 w-8" />
                                    @else
                                        @php
                                            if (str_contains($weatherMain, 'clear') || str_contains($weatherMain, 'sun')) {
                                                $cdnIcon = 'sun';
                                            } elseif (str_contains($weatherMain, 'cloud')) {
                                                $cdnIcon = 'cloud';
                                            } elseif (str_contains($weatherMain, 'rain') || str_contains($weatherMain, 'drizzle')) {
                                                $cdnIcon = 'cloud-rain';
                                            } elseif (str_contains($weatherMain, 'thunder') || str_contains($weatherMain, 'storm')) {
                                                $cdnIcon = 'zap';
                                            } elseif (str_contains($weatherMain, 'snow')) {
                                                $cdnIcon = 'cloud-snow';
                                            } else {
                                                $cdnIcon = 'sun';
                                            }
                                        @endphp

                                        <img src="https://unpkg.com/feather-icons/dist/icons/{{ $cdnIcon }}.svg" alt="{{ $currentWeather['description'] ?? 'weather' }}" class="h-8 w-8" />
                                    @endif
                                </div>
                            </div>
                        </article>

                        <article class="rounded border border-white/10 bg-slate-900/85 p-5">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.28em] text-indigo-200/70">Ollama control</p>
                                    <h3 class="mt-2 text-xl font-semibold text-white">Send current sensor snapshot</h3>
                                    <p class="mt-2 text-sm text-slate-400">One click sends the latest dashboard readings to the local model.</p>
                                </div>
                                <div class="rounded-2xl bg-indigo-400/15 p-3 text-indigo-200">
                                    <svg viewBox="0 0 24 24" fill="none" class="h-6 w-6" stroke="currentColor" stroke-width="1.8">
                                        <path d="M12 2v20" />
                                        <path d="M5 9l7-7 7 7" />
                                    </svg>
                                </div>
                            </div>

                            <div class="mt-5 flex flex-wrap items-center gap-3">
                                <button id="ollama-run-btn" type="button" class="rounded bg-indigo-600/90 px-4 py-2 text-sm font-semibold text-white">Send to Ollama</button>
                                <span class="text-sm text-slate-400">Uses the latest water temperature, TDS, water level, and weather data.</span>
                            </div>

                            <div id="ollama-output" class="mt-4 rounded border border-white/10 bg-slate-900/80 p-4 text-sm whitespace-pre-wrap text-slate-300">Waiting for a sensor snapshot...</div>
                        </article>

                        <article class="rounded border border-white/10 bg-slate-900/85 p-5">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.28em] text-emerald-200/70">Latest AI prediction</p>
                                    <h3 id="latest-prediction-title" class="mt-2 text-xl font-semibold text-white">{{ $latestPrediction['prediction'] ?? 'No prediction yet' }}</h3>
                                </div>
                                <div class="rounded-2xl bg-emerald-400/15 p-3 text-emerald-200">
                                    <svg viewBox="0 0 24 24" fill="none" class="h-6 w-6" stroke="currentColor" stroke-width="1.8">
                                        <path d="M12 2 3 7v10l9 5 9-5V7l-9-5Z" />
                                        <path d="M12 12V2" />
                                        <path d="m3 7 9 5 9-5" />
                                    </svg>
                                </div>
                            </div>

                            <div class="mt-5 flex items-end justify-between gap-4">
                                <div>
                                    <p class="text-sm text-slate-400">Confidence</p>
                                    <p id="latest-prediction-confidence" class="mt-1 text-4xl font-semibold text-white">{{ $latestPrediction['confidence'] ?? '0.0%' }}</p>
                                </div>
                                <div class="text-right text-xs text-slate-400">
                                    <p id="latest-prediction-created-at">{{ $latestPrediction['createdAt'] ?? 'No data' }}</p>
                                    <p id="latest-prediction-relative-time" class="mt-1 text-emerald-200">{{ $latestPrediction['relativeTime'] ?? 'Waiting for upload' }}</p>
                                </div>
                            </div>

                            <div class="mt-5 h-2 overflow-hidden rounded-full bg-white/10">
                                <div id="latest-prediction-progress" class="h-full rounded-full bg-gradient-to-r from-emerald-300 via-cyan-300 to-lime-300" style="width: {{ $latestPrediction['confidenceValue'] ?? 0 }}%;"></div>
                            </div>
                        </article>

                        <article class="rounded border border-white/10 bg-slate-900/85 p-5">
                            <p class="text-xs uppercase tracking-[0.28em] text-violet-200/70">Telemetry volume</p>
                            <div class="mt-4 flex items-end justify-between gap-4">
                                <div>
                                    <p id="telemetry-volume-value" class="text-4xl font-semibold text-white">{{ $overviewCards[3]['value'] }}</p>
                                    <p id="telemetry-volume-meta" class="mt-2 text-sm text-slate-400">{{ $overviewCards[3]['meta'] }}</p>
                                </div>
                                <div class="rounded-2xl bg-violet-400/15 p-3 text-violet-200">
                                    <svg viewBox="0 0 24 24" fill="none" class="h-6 w-6" stroke="currentColor" stroke-width="1.8">
                                        <path d="M4 6h16v12H4z" />
                                        <path d="M8 10h8" />
                                        <path d="M8 14h5" />
                                    </svg>
                                </div>
                            </div>
                            <div class="mt-5 flex items-center gap-2 text-sm text-violet-200">
                                <span id="telemetry-volume-dot" class="h-2 w-2 rounded-full bg-violet-300"></span>
                                SQLite-backed records
                            </div>
                        </article>
                    </section>

                    <section id="ai-monitor" class="space-y-6">
                        <article class="rounded border border-white/10 bg-slate-900/85 p-5">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.28em] text-cyan-200/70">Latest uploaded plant image</p>
                                    <h3 class="mt-2 text-xl font-semibold text-white">Vision input from ESP32-CAM</h3>
                                </div>
                                <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-slate-300">Auto captured</span>
                            </div>

                            @if ($latestPrediction !== null)
                                <div class="mt-5 space-y-5">
                                    <div class="overflow-hidden rounded border border-white/10 bg-slate-900/80">
                                        <img id="latest-prediction-image" src="{{ $latestPrediction['image'] }}" alt="Latest plant upload" class="h-full w-full object-cover">
                                    </div>

                                    <div class="space-y-4 rounded border border-white/10 bg-slate-900/80 p-5">
                                        <div>
                                            <p class="text-xs uppercase tracking-[0.28em] text-emerald-200/70">Diagnosis</p>
                                            <p id="latest-prediction-diagnosis" class="mt-2 text-2xl font-semibold text-white">{{ $latestPrediction['prediction'] }}</p>
                                        </div>

                                        <div>
                                            <div class="flex items-center justify-between text-sm text-slate-400">
                                                <span>Confidence</span>
                                                <span id="latest-prediction-confidence-inline">{{ $latestPrediction['confidence'] }}</span>
                                            </div>
                                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-white/10">
                                                <div id="latest-prediction-inline-progress" class="h-full rounded-full bg-gradient-to-r from-cyan-300 via-emerald-300 to-lime-300" style="width: {{ $latestPrediction['confidenceValue'] }}%;"></div>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 gap-3 text-sm text-slate-300">
                                            <div class="rounded border border-white/10 bg-slate-900/80 p-3">
                                                <p class="text-slate-500">Uploaded</p>
                                                <p id="latest-prediction-uploaded-at" class="mt-1 text-white">{{ $latestPrediction['createdAt'] }}</p>
                                            </div>
                                            <div class="rounded border border-white/10 bg-slate-900/80 p-3">
                                                <p class="text-slate-500">Freshness</p>
                                                <p id="latest-prediction-freshness" class="mt-1 text-white">{{ $latestPrediction['relativeTime'] }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="mt-5 rounded-3xl border border-dashed border-white/15 bg-slate-950/60 p-10 text-center text-slate-400">
                                    <p class="text-lg font-medium text-white">No plant image uploaded yet</p>
                                    <p class="mt-2">Once the ESP32-CAM posts an image, the AI prediction card will render here with confidence and timeline details.</p>
                                </div>
                            @endif
                        </article>

                        <!-- Trend modules card removed -->
                    </section>

                    <section id="history" class="space-y-6">
                        <article class="overflow-hidden rounded border border-white/10 bg-slate-900/85">
                            <div class="flex items-center justify-between gap-4 border-b border-white/10 px-5 py-4 sm:px-6">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.28em] text-emerald-200/70">Recent prediction history</p>
                                    <h3 class="mt-2 text-xl font-semibold text-white">Latest AI classifications</h3>
                                </div>
                                <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-slate-300">{{ count($recentPredictions) }} rows</span>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-white/10 text-left text-sm">
                                    <thead class="bg-white/5 text-slate-400">
                                        <tr>
                                            <th class="px-5 py-3 font-medium sm:px-6">Image</th>
                                            <th class="px-5 py-3 font-medium sm:px-6">Prediction</th>
                                            <th class="px-5 py-3 font-medium sm:px-6">Confidence</th>
                                            <th class="px-5 py-3 font-medium sm:px-6">Recorded</th>
                                        </tr>
                                    </thead>
                                    <tbody id="recent-predictions-body" class="divide-y divide-white/10">
                                        @forelse ($recentPredictions as $prediction)
                                            <tr class="hover:bg-white/5">
                                                <td class="px-5 py-4 sm:px-6">
                                                    <img src="{{ $prediction['image'] }}" alt="Prediction {{ $prediction['id'] }}" class="h-14 w-20 rounded-xl object-cover ring-1 ring-white/10">
                                                </td>
                                                <td class="px-5 py-4 sm:px-6">
                                                    <div class="font-medium text-white">{{ $prediction['prediction'] }}</div>
                                                    <div class="mt-1 text-slate-500">Prediction #{{ $prediction['id'] }}</div>
                                                </td>
                                                <td class="px-5 py-4 sm:px-6 text-emerald-200">{{ $prediction['confidence'] }}</td>
                                                <td class="px-5 py-4 sm:px-6 text-slate-400">{{ $prediction['createdAt'] }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="px-5 py-8 text-center text-slate-400 sm:px-6">No predictions have been stored yet.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </article>

                        <article id="sensors" class="overflow-hidden rounded border border-white/10 bg-slate-900/85">
                            <div class="border-b border-white/10 px-5 py-4 sm:px-6">
                                <p class="text-xs uppercase tracking-[0.28em] text-cyan-200/70">Recent sensor logs</p>
                                <h3 class="mt-2 text-xl font-semibold text-white">Temperature and TDS stream</h3>
                            </div>

                            <div id="recent-sensor-logs" class="divide-y divide-white/10">
                                @forelse ($recentSensorLogs as $log)
                                    <div class="flex items-center justify-between gap-4 px-5 py-4 sm:px-6">
                                        <div>
                                            <p class="font-medium text-white">{{ $log['sensor'] }}</p>
                                            <p class="mt-1 text-sm text-slate-400">{{ $log['timestamp'] }}</p>
                                        </div>
                                        @if(isset($log['raw']))
                                            <div class="text-right text-sm">
                                                <div class="text-sm text-emerald-200">Estimated Nutrient Level</div>
                                                <div class="mt-1 font-semibold text-white">{{ $log['reading'] }}</div>
                                                <div class="mt-1 text-xs text-slate-400">raw data {{ $log['raw'] }}</div>
                                            </div>
                                        @else
                                            <div class="text-sm font-semibold {{ $log['tone'] === 'cyan' ? 'text-cyan-200' : 'text-emerald-200' }}">{{ $log['reading'] }}</div>
                                        @endif
                                    </div>
                                @empty
                                    <div class="px-5 py-10 text-center text-slate-400 sm:px-6">No sensor logs have been stored yet.</div>
                                @endforelse
                            </div>
                        </article>
                    </section>

                </div>
            </main>
        </div>
    </body>
</html>