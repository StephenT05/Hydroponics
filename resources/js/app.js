document.addEventListener('DOMContentLoaded', () => {
	const liveUrl = document.body.dataset.liveUrl;
	const intervalSeconds = Number(document.body.dataset.liveInterval ?? 0);
	let lastDashboardPayload = null;

	if (!liveUrl || !Number.isFinite(intervalSeconds) || intervalSeconds < 10) {
		return;
	}

	const recentPredictionsBody = document.getElementById('recent-predictions-body');
	const recentSensorLogs = document.getElementById('recent-sensor-logs');

	const setText = (selector, value) => {
		const element = document.getElementById(selector);

		if (element) {
			element.textContent = value;
		}
	};

	const setClassState = (selector, addClasses, removeClasses) => {
		const element = document.getElementById(selector);

		if (!element) {
			return;
		}

		element.classList.remove(...removeClasses);
		element.classList.add(...addClasses);
	};

	const renderPredictions = (predictions) => {
		if (!recentPredictionsBody) {
			return;
		}

		if (!predictions.length) {
			recentPredictionsBody.innerHTML = '<tr><td colspan="4" class="px-5 py-8 text-center text-slate-400 sm:px-6">No predictions have been stored yet.</td></tr>';
			return;
		}

		recentPredictionsBody.innerHTML = predictions.map((prediction) => `
			<tr class="hover:bg-white/5">
				<td class="px-5 py-4 sm:px-6">
					<img src="${prediction.image}" alt="Prediction ${prediction.id}" class="h-14 w-20 rounded-xl object-cover ring-1 ring-white/10">
				</td>
				<td class="px-5 py-4 sm:px-6">
					<div class="font-medium text-white">${prediction.prediction}</div>
					<div class="mt-1 text-slate-500">Prediction #${prediction.id}</div>
				</td>
				<td class="px-5 py-4 sm:px-6 text-emerald-200">${prediction.confidence}</td>
				<td class="px-5 py-4 sm:px-6 text-slate-400">${prediction.createdAt}</td>
			</tr>
		`).join('');
	};

	const renderSensorLogs = (logs) => {
		if (!recentSensorLogs) {
			return;
		}

		if (!logs.length) {
			recentSensorLogs.innerHTML = '<div class="px-5 py-10 text-center text-slate-400 sm:px-6">No sensor logs have been stored yet.</div>';
			return;
		}

		recentSensorLogs.innerHTML = logs.map((log) => `
			<div class="flex items-center justify-between gap-4 px-5 py-4 sm:px-6">
				<div>
					<p class="font-medium text-white">${log.sensor}</p>
					<p class="mt-1 text-sm text-slate-400">${log.timestamp}</p>
				</div>
					${log.raw ? `
						<div class="text-right text-sm">
							<div class="text-sm text-emerald-200">Estimated Nutrient Level</div>
							<div class="mt-1 font-semibold text-white">${log.reading}</div>
							<div class="mt-1 text-xs text-slate-400">raw data ${log.raw}</div>
							</div>
						` : `
						<div class="text-sm font-semibold ${log.tone === 'cyan' ? 'text-cyan-200' : 'text-emerald-200'}">${log.reading}</div>
						`}
			</div>
		`).join('');
	};

	const syncDashboard = async () => {
		try {
			const response = await fetch(liveUrl, {
				headers: {
					Accept: 'application/json',
					'X-Requested-With': 'XMLHttpRequest',
				},
			});

			if (!response.ok) {
				return;
			}

			const payload = await response.json();
			lastDashboardPayload = payload;

			setText('last-synced-at', payload.lastSyncedAt ?? '—');
			setText('latest-prediction-title', payload.latestPrediction?.prediction ?? 'No prediction yet');
			setText('latest-prediction-confidence', payload.latestPrediction?.confidence ?? '0.0%');
			setText('latest-prediction-created-at', payload.latestPrediction?.createdAt ?? 'No data');
			setText('latest-prediction-relative-time', payload.latestPrediction?.relativeTime ?? 'Waiting for upload');
			setText('latest-prediction-diagnosis', payload.latestPrediction?.prediction ?? 'No prediction yet');
			setText('latest-prediction-confidence-inline', payload.latestPrediction?.confidence ?? '0.0%');
			setText('latest-prediction-uploaded-at', payload.latestPrediction?.createdAt ?? 'No data');
			setText('latest-prediction-freshness', payload.latestPrediction?.relativeTime ?? 'Waiting for upload');
			setText('latest-temperature-value-card', payload.latestTemperature?.value ?? '--.-');
			setText('latest-temperature-relative-time-card', payload.latestTemperature?.relativeTime ?? 'No reading yet');
			setText('latest-temperature-created-at-card', payload.latestTemperature?.createdAt ?? 'No data');
			const tdsUncalibrated = Boolean(payload.latestTds?.uncalibrated);
			const tdsCategory = payload.latestTds?.category ?? null;
			const tdsValue = payload.latestTds?.value ?? '--';

			if (tdsUncalibrated) {
				// Show the category (Low/Moderate/High) for uncalibrated readings
				setText('latest-tds-value', tdsCategory ?? 'No reading yet');
				setText('latest-tds-raw', `raw data ${tdsValue}`);
				setText('latest-tds-unit', '');
			} else {
				setText('latest-tds-value', tdsValue);
				setText('latest-tds-raw', '');
				setText('latest-tds-unit', 'ppm');
			}

			setText('latest-tds-relative-time', payload.latestTds?.relativeTime ?? 'No reading yet');
			setText('telemetry-volume-value', payload.overviewCards?.[3]?.value ?? '0 records');
			setText('telemetry-volume-meta', payload.overviewCards?.[3]?.meta ?? 'Latest sync just now');

			// Water level updates
			const waterPercentage = Number(payload.latestWaterLevel?.percentage ?? 0);
			setText('latest-water-level-percentage', (waterPercentage).toFixed(1));
			setText('water-level-text', `${(waterPercentage).toFixed(1)}% full`);
			setText('latest-water-distance', `${payload.latestWaterLevel?.distanceCm ?? '--'} cm`);
			setText('latest-water-created-at', payload.latestWaterLevel?.createdAt ?? 'No data');
			setText('latest-water-relative-time', payload.latestWaterLevel?.relativeTime ?? 'No reading yet');

			// Weather updates
			if (payload.currentWeather) {
				setText('weather-temperature', payload.currentWeather.temperature?.toFixed(1) ?? '--');
				setText('weather-description', payload.currentWeather.description ?? 'No data');
				setText('weather-humidity', `${payload.currentWeather.humidity ?? '--'}%`);
				setText('weather-wind', `${(payload.currentWeather.wind_speed ?? 0).toFixed(1)} m/s`);
				setText('weather-clouds', `${payload.currentWeather.clouds ?? '--'}%`);
			}

			const waterFillElement = document.getElementById('water-fill');
			if (waterFillElement) {
				waterFillElement.style.height = `${Math.max(0, Math.min(100, waterPercentage))}%`;
			}

			const predictionConfidence = Number(payload.latestPrediction?.confidenceValue ?? 0);
			const predictionProgress = document.getElementById('latest-prediction-progress');
			const inlinePredictionProgress = document.getElementById('latest-prediction-inline-progress');

			if (predictionProgress) {
				predictionProgress.style.width = `${predictionConfidence}%`;
			}

			if (inlinePredictionProgress) {
				inlinePredictionProgress.style.width = `${predictionConfidence}%`;
			}

			const latestPredictionImage = document.getElementById('latest-prediction-image');

			if (latestPredictionImage && payload.latestPrediction?.image) {
				latestPredictionImage.src = payload.latestPrediction.image;
			}

			const temperatureWarningText = document.getElementById('temperature-warning-text');
			const temperatureWarningDot = document.getElementById('temperature-warning-dot');
			const tdsWarningText = document.getElementById('tds-warning-text');
			const tdsWarningDot = document.getElementById('tds-warning-dot');

			if (temperatureWarningText && temperatureWarningDot) {
				const hot = Boolean(payload.warningStates?.highTemperature);
				temperatureWarningText.textContent = hot ? 'High temperature warning' : 'Temperature within range';
				temperatureWarningDot.className = hot ? 'h-2 w-2 rounded-full bg-amber-300' : 'h-2 w-2 rounded-full bg-emerald-300';
				setClassState('temperature-warning', hot ? ['text-amber-200'] : ['text-emerald-200'], hot ? ['text-emerald-200'] : ['text-amber-200']);
			}

			if (tdsWarningText && tdsWarningDot) {
				const tdsUncalibratedLocal = Boolean(payload.latestTds?.uncalibrated);
				const tdsCategoryLocal = payload.latestTds?.category ?? null;
				const lowTds = tdsUncalibratedLocal ? (tdsCategoryLocal === 'Low') : Boolean(payload.warningStates?.lowTds);
				tdsWarningText.textContent = tdsUncalibratedLocal ? (tdsCategoryLocal ?? 'Nutrient level unknown') : (lowTds ? 'Low nutrient warning' : 'Nutrient level acceptable');
				tdsWarningDot.className = lowTds ? 'h-2 w-2 rounded-full bg-rose-300' : 'h-2 w-2 rounded-full bg-emerald-300';
				setClassState('tds-warning', lowTds ? ['text-rose-200'] : ['text-emerald-200'], lowTds ? ['text-emerald-200'] : ['text-rose-200']);
			}

			const overviewValues = document.querySelectorAll('.overview-value');
			const overviewMetas = document.querySelectorAll('.overview-meta');

			if (overviewValues.length >= 4 && overviewMetas.length >= 4) {
				overviewValues[0].textContent = payload.overviewCards?.[0]?.value ?? 'Waiting for image';
				overviewMetas[0].textContent = payload.overviewCards?.[0]?.meta ?? 'No plant image uploaded yet';
				overviewValues[1].textContent = payload.overviewCards?.[1]?.value ?? 'No reading yet';
				overviewMetas[1].textContent = payload.overviewCards?.[1]?.meta ?? 'Waiting for ESP8266 telemetry';
				overviewValues[2].textContent = payload.overviewCards?.[2]?.value ?? 'No reading yet';
				overviewMetas[2].textContent = payload.overviewCards?.[2]?.meta ?? 'Awaiting hydroponic sensor data';
				overviewValues[3].textContent = payload.overviewCards?.[3]?.value ?? '0 records';
				overviewMetas[3].textContent = payload.overviewCards?.[3]?.meta ?? 'Latest sync just now';
			}

			renderPredictions(payload.recentPredictions ?? []);
			renderSensorLogs(payload.recentSensorLogs ?? []);
		} catch (error) {
			console.error('Dashboard live sync failed', error);
		}
	};

	syncDashboard();
	window.setInterval(syncDashboard, intervalSeconds * 1000);

		// Handle weather location form submission
		const weatherForm = document.getElementById('weather-location-form');

		if (weatherForm) {
			weatherForm.addEventListener('submit', async (e) => {
				e.preventDefault();

				const lat = document.getElementById('weather-lat')?.value;
				const lon = document.getElementById('weather-lon')?.value;
				const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

				if (!lat || !lon) {
					return;
				}

				try {
					const res = await fetch('/settings/weather-location', {
						method: 'POST',
						headers: {
							'Content-Type': 'application/json',
							'X-CSRF-TOKEN': token || '',
							'X-Requested-With': 'XMLHttpRequest',
						},
						body: JSON.stringify({ lat, lon }),
					});

					if (res.ok) {
						console.log('Weather location saved');
						// trigger immediate refresh
						syncDashboard();
					} else {
						console.error('Failed to save weather location');
					}
				} catch (err) {
					console.error('Error saving weather location', err);
				}
			});
		}

	// Ollama run button handler
	const ollamaBtn = document.getElementById('ollama-run-btn');
	if (ollamaBtn) {
		ollamaBtn.addEventListener('click', async (e) => {
			e.preventDefault();

			const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
			const out = document.getElementById('ollama-output');
			const originalLabel = ollamaBtn.textContent;
			const sensorData = {
				waterTemperature: lastDashboardPayload?.latestTemperature?.value ?? document.getElementById('latest-temperature-value-card')?.textContent?.trim() ?? '',
				tds: lastDashboardPayload?.latestTds?.uncalibrated ? lastDashboardPayload?.latestTds?.category : lastDashboardPayload?.latestTds?.value ?? '',
				waterLevel: lastDashboardPayload?.latestWaterLevel?.percentage ?? document.getElementById('latest-water-level-percentage')?.textContent?.trim() ?? '',
				weatherTemperature: lastDashboardPayload?.currentWeather?.temperature ?? document.getElementById('weather-temperature')?.textContent?.trim() ?? '',
				weatherDescription: lastDashboardPayload?.currentWeather?.description ?? document.getElementById('weather-description')?.textContent?.trim() ?? '',
			};

			ollamaBtn.disabled = true;
			ollamaBtn.textContent = 'Sending...';
			if (out) {
				out.textContent = 'Sending the latest sensor snapshot to Ollama...';
			}

			try {
				const res = await fetch('/ollama/run', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-CSRF-TOKEN': token || '',
						'X-Requested-With': 'XMLHttpRequest',
					},
					body: JSON.stringify({ sensorData }),
				});

				const json = await res.json();

				if (!res.ok) {
					if (out) {
						out.textContent = json.error || 'Ollama run failed';
					}

					return;
				}

				if (out) {
					out.textContent = json.output ?? 'No output';
				}
			} catch (err) {
				if (out) {
					out.textContent = 'Error: ' + (err.message || String(err));
				}
			} finally {
				ollamaBtn.disabled = false;
				ollamaBtn.textContent = originalLabel;
			}
		});
	}
});
