<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Hydroponics AI') }} Dashboard</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body
        data-live-url="{{ route('dashboard.live') }}"
        data-live-interval="{{ $refreshInterval }}"
        class="min-h-screen bg-slate-950 text-slate-100 antialiased"
    >
        <div class="pointer-events-none fixed inset-0 overflow-hidden">
            <div class="absolute -left-24 top-0 h-96 w-96 rounded-full bg-emerald-500/20 blur-3xl"></div>
            <div class="absolute right-0 top-20 h-80 w-80 rounded-full bg-cyan-400/20 blur-3xl"></div>
            <div class="absolute bottom-0 left-1/3 h-72 w-72 rounded-full bg-lime-400/10 blur-3xl"></div>
        </div>

        <div class="relative flex min-h-screen">
            <aside class="hidden w-72 shrink-0 border-r border-white/10 bg-slate-950/80 px-6 py-8 backdrop-blur xl:block">
                <div class="mb-8 flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-400 via-cyan-400 to-lime-300 text-slate-950 shadow-lg shadow-emerald-400/20">
                        <svg viewBox="0 0 24 24" fill="none" class="h-6 w-6" stroke="currentColor" stroke-width="1.8">
                            <path d="M12 3c4.97 0 9 4.03 9 9s-4.03 9-9 9-9-4.03-9-9 4.03-9 9-9Z" />
                            <path d="M12 7v10" />
                            <path d="M8.5 10.5c1.5-1 3-1.5 3.5-1.5s2 .5 3.5 1.5" />
                            <path d="M8.5 13.5c1.5 1 3 1.5 3.5 1.5s2-.5 3.5-1.5" />
                        </svg>
                    </div>
                </div>

                <nav class="space-y-2 text-sm font-medium">
                    <a href="#overview" class="flex items-center gap-3 rounded-2xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-emerald-200 transition hover:border-emerald-300/40 hover:bg-emerald-400/15">
                        <span class="h-2 w-2 rounded-full bg-emerald-300"></span>
                        Overview
                    </a>
                    <a href="#ai-monitor" class="flex items-center gap-3 rounded-2xl border border-white/5 px-4 py-3 text-slate-300 transition hover:border-cyan-300/30 hover:bg-white/5">
                        <span class="h-2 w-2 rounded-full bg-cyan-300"></span>
                        AI Prediction
                    </a>
                    <a href="#sensors" class="flex items-center gap-3 rounded-2xl border border-white/5 px-4 py-3 text-slate-300 transition hover:border-cyan-300/30 hover:bg-white/5">
                        <span class="h-2 w-2 rounded-full bg-lime-300"></span>
                        Sensor Logs
                    </a>
                    <a href="#future" class="flex items-center gap-3 rounded-2xl border border-white/5 px-4 py-3 text-slate-300 transition hover:border-violet-300/30 hover:bg-white/5">
                        <span class="h-2 w-2 rounded-full bg-violet-300"></span>
                        Roadmap
                    </a>
                </nav>

                        <div class="mt-10 rounded-3xl border border-white/10 bg-white/5 p-5 shadow-2xl shadow-black/30">
                    <p class="text-xs uppercase tracking-[0.3em] text-cyan-200/70">System pulse</p>
                    <div class="mt-4 space-y-3 text-sm text-slate-300">
                        <div class="flex items-center justify-between gap-3">
                            <span>Auto refresh</span>
                                    <span class="rounded-full border border-emerald-400/30 bg-emerald-400/10 px-3 py-1 text-emerald-200">live</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span>Last sync</span>
                                    <span id="last-synced-at" class="text-slate-100">{{ $lastSyncedAt }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span>Camera stream</span>
                            <span class="text-emerald-200">Connected</span>
                        </div>
                    </div>
                </div>
            </aside>

            <main class="flex-1 px-4 py-4 sm:px-6 lg:px-8 lg:py-6">
                <div class="mx-auto max-w-7xl space-y-6">
                    <section id="overview" class="overflow-hidden rounded-3xl border border-white/10 bg-slate-900/70 shadow-2xl shadow-black/30 backdrop-blur">
                        <div class="dashboard-grid px-5 py-5 sm:px-6 lg:px-8">
                            <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr] xl:items-start">
                                <div class="space-y-4">
                                    <div class="inline-flex items-center gap-2 rounded-full border border-cyan-400/20 bg-cyan-400/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.32em] text-cyan-200">
                                        <span class="h-2 w-2 rounded-full bg-cyan-300"></span>
                                        Sensor telemetry live
                                    </div>
                                    <div>
                                        <h2 class="text-3xl font-semibold tracking-tight text-white sm:text-4xl">Water quality first. AI second.</h2>
                                        <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300 sm:text-base">
                                            Track temperature, raw conductivity trend, and warning states up front so the dashboard behaves like a monitoring panel, not a landing page.
                                        </p>
                                    </div>

                                    <div class="grid gap-3 sm:grid-cols-3">
                                        <div class="rounded-2xl border border-cyan-400/15 bg-cyan-400/10 p-4">
                                            <p class="text-xs uppercase tracking-[0.28em] text-cyan-200/70">Temperature</p>
                                            <p class="mt-3 text-2xl font-semibold text-white">{{ $latestTemperature['value'] ?? '--.-' }}<span class="ml-1 text-lg text-cyan-200">°C</span></p>
                                            <p class="mt-2 text-sm text-slate-400">{{ $latestTemperature['relativeTime'] ?? 'No reading yet' }}</p>
                                        </div>
                                        <div class="rounded-2xl border border-emerald-400/15 bg-emerald-400/10 p-4">
                                            <p class="text-xs uppercase tracking-[0.28em] text-emerald-200/70">Conductivity</p>
                                            <p class="mt-3 text-2xl font-semibold text-white">{{ $latestTds['uncalibrated'] ?? false ? ($latestTds['category'] ?? 'No reading yet') : ($latestTds['value'] ?? '--') }}</p>
                                            <p class="mt-2 text-sm text-slate-400">{{ $latestTds['uncalibrated'] ?? false ? 'Estimated nutrient level' : 'ppm' }}</p>
                                        </div>
                                        <div class="rounded-2xl border border-violet-400/15 bg-violet-400/10 p-4">
                                            <p class="text-xs uppercase tracking-[0.28em] text-violet-200/70">Warnings</p>
                                            <p class="mt-3 text-2xl font-semibold text-white">{{ ($warningStates['highTemperature'] ? 1 : 0) + ($warningStates['lowTds'] ? 1 : 0) }}</p>
                                            <p class="mt-2 text-sm text-slate-400">Active alert(s)</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid gap-3 sm:grid-cols-2">
                                    @foreach ($overviewCards as $card)
                                        <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4 shadow-lg shadow-black/20">
                                            <p class="text-xs uppercase tracking-[0.28em] text-slate-400">{{ $card['label'] }}</p>
                                            <p class="overview-value mt-3 text-lg font-semibold text-white">{{ $card['value'] }}</p>
                                            <p class="overview-meta mt-2 text-sm text-slate-400">{{ $card['meta'] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <article class="rounded-3xl border border-cyan-400/20 bg-gradient-to-br from-cyan-500/20 via-slate-950 to-slate-900 p-5 shadow-2xl shadow-cyan-950/30 sm:col-span-2 xl:col-span-1">
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

                        <article class="rounded-3xl border border-emerald-400/20 bg-gradient-to-br from-emerald-500/20 via-slate-950 to-slate-900 p-5 shadow-2xl shadow-emerald-950/30 sm:col-span-2 xl:col-span-1">
                            <p class="text-xs uppercase tracking-[0.28em] text-emerald-200/70">Conductivity trend</p>
                            <div class="mt-4 flex items-end justify-between gap-4">
                                <div>
                                    @if (!empty($latestTds) && ($latestTds['uncalibrated'] ?? false))
                                        <p class="text-xs text-slate-400">Estimated Nutrient Level</p>
                                        <p class="text-4xl font-semibold text-white"><span id="latest-tds-value">{{ $latestTds['category'] }}</span></p>
                                        <p id="latest-tds-raw" class="mt-1 text-xs text-slate-400">raw {{ $latestTds['value'] }}</p>
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
                            <div id="tds-warning" class="mt-5 flex items-center gap-2 text-sm {{ $warningStates['lowTds'] ? 'text-rose-200' : 'text-emerald-200' }}">
                                <span id="tds-warning-dot" class="h-2 w-2 rounded-full {{ $warningStates['lowTds'] ? 'bg-rose-300' : 'bg-emerald-300' }}"></span>
                                <span id="tds-warning-text">{{ $warningStates['lowTds'] ? 'Low nutrient warning' : 'Nutrient level acceptable' }}</span>
                            </div>
                        </article>

                        <article class="rounded-3xl border border-white/10 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-5 shadow-2xl shadow-black/30 sm:col-span-2 xl:col-span-1">
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

                        <article class="rounded-3xl border border-violet-400/20 bg-gradient-to-br from-violet-500/20 via-slate-950 to-slate-900 p-5 shadow-2xl shadow-violet-950/30">
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

                    <section id="ai-monitor" class="grid gap-6 xl:grid-cols-[1.3fr_0.9fr]">
                        <article class="rounded-3xl border border-white/10 bg-slate-900/70 p-5 shadow-2xl shadow-black/30 backdrop-blur">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.28em] text-cyan-200/70">Latest uploaded plant image</p>
                                    <h3 class="mt-2 text-xl font-semibold text-white">Vision input from ESP32-CAM</h3>
                                </div>
                                <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-slate-300">Auto captured</span>
                            </div>

                            @if ($latestPrediction !== null)
                                <div class="mt-5 grid gap-5 lg:grid-cols-[1.1fr_0.9fr]">
                                    <div class="overflow-hidden rounded-2xl border border-white/10 bg-slate-950/60">
                                        <img id="latest-prediction-image" src="{{ $latestPrediction['image'] }}" alt="Latest plant upload" class="h-full w-full object-cover">
                                    </div>

                                    <div class="space-y-4 rounded-2xl border border-white/10 bg-white/5 p-5">
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

                                        <div class="grid grid-cols-2 gap-3 text-sm text-slate-300">
                                            <div class="rounded-2xl border border-white/10 bg-slate-950/50 p-3">
                                                <p class="text-slate-500">Uploaded</p>
                                                <p id="latest-prediction-uploaded-at" class="mt-1 text-white">{{ $latestPrediction['createdAt'] }}</p>
                                            </div>
                                            <div class="rounded-2xl border border-white/10 bg-slate-950/50 p-3">
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

                        <article class="rounded-3xl border border-white/10 bg-slate-900/70 p-5 shadow-2xl shadow-black/30 backdrop-blur">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.28em] text-violet-200/70">Chart placeholders</p>
                                    <h3 class="mt-2 text-xl font-semibold text-white">Trend modules</h3>
                                </div>
                                <span class="rounded-full border border-violet-400/20 bg-violet-400/10 px-3 py-1 text-xs text-violet-200">Demo ready</span>
                            </div>

                            <div class="mt-5 space-y-4">
                                <div class="rounded-3xl border border-white/10 bg-gradient-to-br from-slate-950 via-slate-900 to-cyan-950/60 p-5">
                                    <div class="flex items-center justify-between text-sm text-slate-400">
                                        <span>AI prediction trend</span>
                                        <span>Last 24h</span>
                                    </div>
                                    <div class="mt-4 flex h-36 items-end gap-2">
                                        @foreach ([32, 52, 28, 74, 56, 84, 48] as $bar)
                                            <div class="flex-1 rounded-t-2xl bg-gradient-to-t from-cyan-400/40 to-emerald-300/80" style="height: {{ $bar }}%;"></div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="rounded-3xl border border-white/10 bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-950/60 p-5">
                                    <div class="flex items-center justify-between text-sm text-slate-400">
                                        <span>Water quality trend</span>
                                        <span>Correlation view</span>
                                    </div>
                                    <div class="mt-4 grid h-36 grid-cols-12 items-end gap-2">
                                        @foreach ([18, 24, 34, 28, 48, 36, 42, 54, 68, 58, 76, 64] as $bar)
                                            <div class="rounded-t-2xl bg-gradient-to-t from-lime-300/40 to-emerald-300/80" style="height: {{ $bar }}%;"></div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </article>
                    </section>

                    <section id="history" class="grid gap-6 xl:grid-cols-[1.25fr_0.75fr]">
                        <article class="overflow-hidden rounded-3xl border border-white/10 bg-slate-900/70 shadow-2xl shadow-black/30 backdrop-blur">
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

                        <article id="sensors" class="overflow-hidden rounded-3xl border border-white/10 bg-slate-900/70 shadow-2xl shadow-black/30 backdrop-blur">
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
                                            <span class="rounded-full border border-white/10 px-3 py-1 text-sm {{ $log['tone'] === 'cyan' ? 'bg-cyan-400/10 text-cyan-200' : 'bg-emerald-400/10 text-emerald-200' }}">
                                                <div class="text-sm">Estimated Nutrient Level</div>
                                                <div class="mt-1 font-semibold text-white">{{ $log['reading'] }}</div>
                                                <div class="mt-1 text-xs text-slate-400">raw {{ $log['raw'] }}</div>
                                            </span>
                                        @else
                                            <span class="rounded-full border border-white/10 px-3 py-1 text-sm {{ $log['tone'] === 'cyan' ? 'bg-cyan-400/10 text-cyan-200' : 'bg-emerald-400/10 text-emerald-200' }}">{{ $log['reading'] }}</span>
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